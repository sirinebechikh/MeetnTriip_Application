<?php

namespace App\Controller\gestion_de_depence;

use App\Entity\gestion_de_depence\Posts;
use App\Repository\gestion_de_depence\PostsRepository;
use App\Repository\gestion_de_depence\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\gestion_de_depence\Comment;  // Add this import
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use App\Entity\gestion_de_depence\CommentReaction;
use Knp\Component\Pager\PaginatorInterface;
use Snipe\BanBuilder\CensorWords;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
// Add this correct use statement with other use statements at the top
use App\Entity\gestion_de_depence\PostReaction;
use Dompdf\Dompdf;  // Add this line
use Dompdf\Options;
// Also remove the duplicate Route annotation for index
#[Route('/forum')]
class PostController extends AbstractController
{
    #[Route('/', name: 'forum_index', methods: ['GET'])]
    public function index(
        PostsRepository $postsRepository, 
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $query = $postsRepository->createQueryBuilder('p')
            ->addSelect('u')
            ->join('p.user', 'u')
            ->andWhere('p.approvalStatus = :status')
            ->setParameter('status', 'approved')  // Add this filter
            ->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            5 // Number of posts per page
        );
    
        // Get reaction counts for each post
        $reactionCounts = [];
        foreach ($pagination as $post) {
            $reactionCounts[$post->getIdPost()] = [
                'likes' => $entityManager->getRepository(PostReaction::class)->count([
                    'idPost' => $post->getIdPost(),
                    'reactionType' => 'like'
                ]),
                'dislikes' => $entityManager->getRepository(PostReaction::class)->count([
                    'idPost' => $post->getIdPost(),
                    'reactionType' => 'dislike'
                ])
            ];
        }
    
        return $this->render('gestion_de_depence/forum/index.html.twig', [
            'posts' => $pagination,
            'reactionCounts' => $reactionCounts
        ]);
    }

    #[Route('/new', name: 'forum_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $post = new Posts();
        $form = $this->createFormBuilder($post)
            ->add('content')
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Sponsor' => 'sponsor',
                    'Event' => 'event',
                    'Travel' => 'travel'
                ]
            ])
            ->add('mediaFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => 'image/*,video/*',
                    'data-show-upload' => 'true',
                    'data-show-caption' => 'true'
                ]
            ])
            ->getForm();
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Add content censorship
            $censor = new CensorWords;
            $langs = array('fr', 'it', 'en-us', 'en-uk', 'es');
            $censor->setDictionary($langs);
            $censor->setReplaceChar("*");
            
            // Censor post content
            $string = $censor->censorString($post->getContent());
            $post->setContent($string['clean']);

            $mediaFile = $form->get('mediaFile')->getData();

            if ($mediaFile) {
                $originalFilename = pathinfo($mediaFile->getClientOriginalName(), PATHINFO_FILENAME);
                $newFilename = $originalFilename.'-'.uniqid().'.'.$mediaFile->guessExtension();
                $mediaType = strpos($mediaFile->getMimeType(), 'image/') === 0 ? 'image' : 'video';

                try {
                    $mediaFile->move(
                        $this->getParameter('forum_media_directory'),
                        $newFilename
                    );
                    
                    $post->setMediaPath($newFilename);
                    $post->setMediaType($mediaType);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload media file');
                }
            }

            $post->setUser($this->getUser());
            $entityManager->persist($post);
            $entityManager->flush();
    
            return $this->redirectToRoute('forum_index');
        }
    
        return $this->render('gestion_de_depence/forum/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id_post}/edit', name: 'forum_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id_post, EntityManagerInterface $entityManager): Response
    {
        $post = $entityManager->getRepository(Posts::class)->find($id_post);
        
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        $form = $this->createFormBuilder($post)
            ->add('content')
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Sponsor' => 'sponsor',
                    'Event' => 'event',
                    'Travel' => 'travel'
                ]
            ])
            ->getForm();
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Add content censorship
            $censor = new CensorWords;
            $langs = array('fr', 'it', 'en-us', 'en-uk', 'es');
            $censor->setDictionary($langs);
            $censor->setReplaceChar("*");
            
            // Censor post content
            $string = $censor->censorString($post->getContent());
            $post->setContent($string['clean']);

            $entityManager->flush();
            return $this->redirectToRoute('forum_index');
        }

        return $this->render('gestion_de_depence/forum/edit.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id_post}', name: 'forum_delete', methods: ['POST'])]
    public function delete(Request $request, int $id_post, EntityManagerInterface $entityManager): Response
    {
        $post = $entityManager->getRepository(Posts::class)->find($id_post);
        
        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        if ($this->isCsrfTokenValid('delete'.$post->getIdPost(), $request->request->get('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('forum_index');
    }

    #[Route('/post/{id_post}/comments', name: 'post_comments', methods: ['GET'])]
    public function comments(
        int $id_post, 
        CommentRepository $commentRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
        EntityManagerInterface $entityManager
    ): Response {
        $comments = $commentRepository->findBy(['id_post' => $id_post]);
        
        $data = [];
        foreach ($comments as $comment) {
            // Get reaction counts for this comment
            $likesCount = $entityManager->getRepository(CommentReaction::class)->count([
                'comment' => $comment,
                'reactionType' => 'like'
            ]);
            
            $dislikesCount = $entityManager->getRepository(CommentReaction::class)->count([
                'comment' => $comment,
                'reactionType' => 'dislike'
            ]);
            
            $data[] = [
                'id_comment' => $comment->getIdComment(),
                'content' => $comment->getContent(),
                'id_user' => $comment->getIdUser(),
                'csrf_token' => $csrfTokenManager->getToken('delete-comment'.$comment->getIdComment())->getValue(),
                'likes' => $likesCount,
                'dislikes' => $dislikesCount
            ];
        }
        
        return $this->json($data);
    }

    #[Route('/post/{id_post}/add-comment', name: 'post_add_comment', methods: ['POST'])]
    public function addComment(Request $request, int $id_post, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        if (!$this->isCsrfTokenValid('comment', $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }
    
        $content = $request->request->get('content');
        
        // Add content censorship
        $censor = new CensorWords;
        $langs = array('fr', 'it', 'en-us', 'en-uk', 'es');
        $censor->setDictionary($langs);
        $censor->setReplaceChar("*");
        $string = $censor->censorString($content);
        $censoredContent = $string['clean'];
    
        if (empty(trim($censoredContent))) {
            return new JsonResponse(['error' => 'Comment cannot be empty'], 400);
        }
        
        if (strlen($content) > 255) {
            return new JsonResponse(['error' => 'Comment cannot exceed 255 characters'], 400);
        }

        $comment = new Comment();
        $comment->setIdPost($id_post);
        $comment->setIdUser($this->getUser()->getId());
        $comment->setContent($censoredContent);
    
        $entityManager->persist($comment);
        $entityManager->flush();
    
        return new JsonResponse([
            'id_comment' => $comment->getIdComment(),
            'content' => $comment->getContent(),
            'id_user' => $comment->getIdUser(),
            'id_post' => $comment->getIdPost()
        ]);
    }

    #[Route('/post/{id_comment}/delete-comment', name: 'post_delete_comment', methods: ['POST'])]
    public function deleteComment(
        Request $request,
        int $id_comment,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $comment = $entityManager->getRepository(Comment::class)->find($id_comment);
        if (!$comment) {
            return new JsonResponse(['error' => 'Comment not found'], 404);
        }
    
        // Verify user owns the comment
        if ($comment->getIdUser() !== $this->getUser()->getId()) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }
    
        // CSRF validation
        $tokenId = 'delete-comment'.$id_comment;
        if (!$this->isCsrfTokenValid($tokenId, $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 403);
        }
    
        $entityManager->remove($comment);
        $entityManager->flush();
    
        return new JsonResponse(['success' => true]);
    }

    #[Route('/post/{id_comment}/update-comment', name: 'post_update_comment', methods: ['POST'])]
    public function updateComment(
        Request $request,
        int $id_comment,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $comment = $entityManager->getRepository(Comment::class)->find($id_comment);
        if (!$comment) {
            return new JsonResponse(['error' => 'Comment not found'], 404);
        }
    
        if ($comment->getIdUser() !== $this->getUser()->getId()) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }
    
        $content = $request->request->get('content');
    
        // Add content censorship
        $censor = new CensorWords;
        $langs = array('fr', 'it', 'en-us', 'en-uk', 'es');
        $censor->setDictionary($langs);
        $censor->setReplaceChar("*");
        $string = $censor->censorString($content);
        $censoredContent = $string['clean'];
    
        if (empty(trim($censoredContent))) {
            return new JsonResponse(['error' => 'Comment cannot be empty'], 400);
        }
        
        if (strlen($content) > 255) {
            return new JsonResponse(['error' => 'Comment cannot exceed 255 characters'], 400);
        }
    
        $comment->setContent($censoredContent);
        $entityManager->flush();
    
        return new JsonResponse([
            'success' => true,
            'newContent' => $content
        ]);
    }

    // Add this new method to the PostController class
    #[Route('/generate-post-content/{type}', name: 'generate_post_content', methods: ['GET'])]
    public function generateContent(string $type): JsonResponse
    {
        try {
            $httpClient = HttpClient::create(); // Now using fully qualified class name
            $apiKey = 'AIzaSyB9T0YNuLwOYN1LN98fXWKktReSl_pENMU';
    
            $prompt = match($type) {
                'sponsor' => "Write a professional business sponsorship post discussing sponsorship opportunities. Focus on:",
                'event' => "Create an engaging business event announcement highlighting key event details. Include:",
                'travel' => "Compose a business travel experience post sharing insights about corporate travel. Cover:",
                default => throw new \Exception('Invalid post type')
            };
    
            $response = $httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent', [
                'query' => ['key' => $apiKey],
                'headers' => ['Content-Type' => 'application/json'],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt." Use formal business language and keep it under 3 paragraphs. Return only the generated text without any formatting."
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
    
            // Add status code check first
            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $content = $response->getContent(false); // Get raw content
                throw new \Exception("API returned status $statusCode. Response: " . substr($content, 0, 200));
            }
    
            // Then parse as array
            $data = $response->toArray();
            
            if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                throw new \Exception('Invalid API response structure');
            }
    
            $content = $data['candidates'][0]['content']['parts'][0]['text'];
            return new JsonResponse(['content' => trim($content)]);
    
        } catch (\Exception $e) {
            error_log("Generation error: " . $e->getMessage());
            return new JsonResponse(
                ['error' => 'Generation failed: ' . $e->getMessage()],
                500
            );
        }
    }

    #[Route('/comment/{id_comment}/react/{type}', name: 'comment_react', methods: ['POST'])]
    public function reactToComment(
        Request $request,
        int $id_comment,
        string $type,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        try {
            // Validate CSRF token
            if (!$this->isCsrfTokenValid('react', $request->headers->get('X-CSRF-TOKEN'))) {
                return new JsonResponse(['error' => 'Invalid CSRF token'], 403);
            }

            $user = $this->getUser();
            
            if (!in_array($type, ['like', 'dislike'])) {
                return new JsonResponse(['error' => 'Invalid reaction type'], 400);
            }
        
            $comment = $entityManager->getRepository(Comment::class)->find($id_comment);
            if (!$comment) {
                return new JsonResponse(['error' => 'Comment not found'], 404);
            }
        
            $existingReaction = $entityManager->getRepository(CommentReaction::class)->findOneBy([
                'comment' => $comment,
                'user' => $user
            ]);
        
            if ($existingReaction) {
                if ($existingReaction->getReactionType() === $type) {
                    $entityManager->remove($existingReaction);
                } else {
                    $existingReaction->setReactionType($type);
                }
            } else {
                $reaction = new CommentReaction();
                $reaction->setComment($comment);
                $reaction->setUser($user);
                $reaction->setReactionType($type);
                $entityManager->persist($reaction);
            }
        
            $entityManager->flush();
        
            $likesCount = $entityManager->getRepository(CommentReaction::class)->count([
                'comment' => $comment,
                'reactionType' => 'like'
            ]);
            $dislikesCount = $entityManager->getRepository(CommentReaction::class)->count([
                'comment' => $comment,
                'reactionType' => 'dislike'
            ]);
        
            return new JsonResponse([
                'likes' => $likesCount,
                'dislikes' => $dislikesCount
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/post/{id_post}/react/{type}', name: 'post_react', methods: ['POST'])]
    public function reactToPost(
        Request $request,
        int $id_post,
        string $type,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        try {
            // Validate CSRF token
            if (!$this->isCsrfTokenValid('react', $request->headers->get('X-CSRF-TOKEN'))) {
                return new JsonResponse(['error' => 'Invalid CSRF token'], 403);
            }
        
            $user = $this->getUser();
            
            if (!in_array($type, ['like', 'dislike'])) {
                return new JsonResponse(['error' => 'Invalid reaction type'], 400);
            }
        
        // Check if post exists
        $post = $entityManager->getRepository(Posts::class)->find($id_post);
        if (!$post) {
            return new JsonResponse(['error' => 'Post not found'], 404);
        }
    
        // Check for existing reaction
        $existingReaction = $entityManager->getRepository(PostReaction::class)->findOneBy([
            'idPost' => $id_post,
            'idUser' => $user->getId()
        ]);
    
        if ($existingReaction) {
            if ($existingReaction->getReactionType() === $type) {
                $entityManager->remove($existingReaction);
            } else {
                $existingReaction->setReactionType($type);
            }
        } else {
            $reaction = new PostReaction();
            $reaction->setIdPost($id_post);
            $reaction->setIdUser($user->getId());
            $reaction->setReactionType($type);
            $entityManager->persist($reaction);
        }
    
        $entityManager->flush();
    
        // Get updated counts
        $likesCount = $entityManager->getRepository(PostReaction::class)->count([
            'idPost' => $id_post,
            'reactionType' => 'like'
        ]);
        $dislikesCount = $entityManager->getRepository(PostReaction::class)->count([
            'idPost' => $id_post,
            'reactionType' => 'dislike'
        ]);
    
        return new JsonResponse([
            'likes' => $likesCount,
            'dislikes' => $dislikesCount
        ]);
        
    } catch (\Exception $e) {
        return new JsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
    }
    }

    #[Route('/admin/forum-stats', name: 'admin_forum_stats', methods: ['GET'])]
    public function forumStats(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Get total counts
        $totalPosts = $entityManager->getRepository(Posts::class)->count([]);
        $totalComments = $entityManager->getRepository(Comment::class)->count([]);

        // Get posts by type
        $postsByType = $entityManager->getRepository(Posts::class)
            ->createQueryBuilder('p')
            ->select('p.type, COUNT(p.id_post) as count')
            ->groupBy('p.type')
            ->getQuery()
            ->getResult();

        // Get most commented posts
        $mostCommentedPosts = $entityManager->getRepository(Posts::class)
            ->createQueryBuilder('p')
            ->select('p.id_post, p.content, COUNT(c.id_comment) as commentCount')
            ->leftJoin('App\Entity\gestion_de_depence\Comment', 'c', 'WITH', 'c.id_post = p.id_post')
            ->groupBy('p.id_post')
            ->orderBy('commentCount', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Get most liked posts
        $mostLikedPosts = $entityManager->getRepository(Posts::class)
            ->createQueryBuilder('p')
            ->select('p.id_post, p.content, COUNT(r.idPost) as likeCount')
            ->leftJoin('App\Entity\gestion_de_depence\PostReaction', 'r', 'WITH', 'r.idPost = p.id_post AND r.reactionType = :type')
            ->setParameter('type', 'like')
            ->groupBy('p.id_post')
            ->orderBy('likeCount', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('gestion_de_depence/forum/stats.html.twig', [
            'totalPosts' => $totalPosts,
            'totalComments' => $totalComments,
            'postsByType' => $postsByType,
            'mostCommentedPosts' => $mostCommentedPosts,
            'mostLikedPosts' => $mostLikedPosts
        ]);
    }

    #[Route('/admin/moderate', name: 'admin_moderate_posts', methods: ['GET'])]
    public function moderate(PostsRepository $postsRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $pendingPosts = $postsRepository->findBy(['approvalStatus' => 'pending']);
    
        return $this->render('gestion_de_depence/forum/moderate.html.twig', [
            'posts' => $pendingPosts
        ]);
    }

    #[Route('/admin/post/{id}/approve', name: 'admin_post_approve', methods: ['POST'])]
    public function approvePost(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $post = $entityManager->getRepository(Posts::class)->find($id);
        if (!$post) {
            return $this->json(['success' => false, 'error' => 'Post not found'], 404);
        }
        
        $post->setApprovalStatus('approved');
        $entityManager->flush();
    
        return $this->json([
            'success' => true,
            'message' => 'Post approved successfully'
        ]);
    }

    #[Route('/admin/post/{id}/reject', name: 'admin_post_reject', methods: ['POST'])]
    public function rejectPost(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $post = $entityManager->getRepository(Posts::class)->find($id);
        if (!$post) {
            return $this->json(['success' => false, 'error' => 'Post not found'], 404);
        }
        
        $post->setApprovalStatus('rejected');
        $entityManager->flush();
    
        return $this->json([
            'success' => true,
            'message' => 'Post rejected successfully'
        ]);
    }

    #[Route('/admin/forum-stats/qr', name: 'admin_forum_stats_qr', methods: ['GET'])]
    public function forumStatsQR(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Verify the base URL
        $baseUrl = $request->getSchemeAndHttpHost();
        if (!str_starts_with($baseUrl, 'http')) {
            throw new \RuntimeException('Invalid server configuration - missing scheme/host');
        }
    
        // Generate URL with validation
        $url = $this->generateUrl('admin_forum_stats', [], UrlGeneratorInterface::ABSOLUTE_URL);
        
        return $this->render('gestion_de_depence/forum/qr.html.twig', [
            'url' => $url
        ]);
    }

    #[Route('/admin/forum-stats/pdf', name: 'admin_forum_stats_pdf', methods: ['GET'])]
    public function forumStatsPdf(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Reuse the stats data logic
        $statsData = [
            'totalPosts' => $entityManager->getRepository(Posts::class)->count([]),
            'totalComments' => $entityManager->getRepository(Comment::class)->count([]),
            'postsByType' => $entityManager->getRepository(Posts::class)
                ->createQueryBuilder('p')
                ->select('p.type, COUNT(p.id_post) as count')
                ->groupBy('p.type')
                ->getQuery()
                ->getResult(),
            'mostCommentedPosts' => $entityManager->getRepository(Posts::class)
                ->createQueryBuilder('p')
                ->select('p.id_post, p.content, COUNT(c.id_comment) as commentCount')
                ->leftJoin('App\Entity\gestion_de_depence\Comment', 'c', 'WITH', 'c.id_post = p.id_post')
                ->groupBy('p.id_post')
                ->orderBy('commentCount', 'DESC')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult(),
            'mostLikedPosts' => $entityManager->getRepository(Posts::class)
                ->createQueryBuilder('p')
                ->select('p.id_post, p.content, COUNT(r.idPost) as likeCount')
                ->leftJoin('App\Entity\gestion_de_depence\PostReaction', 'r', 'WITH', 'r.idPost = p.id_post AND r.reactionType = :type')
                ->setParameter('type', 'like')
                ->groupBy('p.id_post')
                ->orderBy('likeCount', 'DESC')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult()
        ];
    
        // Configure Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
    
        // Generate HTML
        $html = $this->renderView('gestion_de_depence/forum/pdf/stats_pdf.html.twig', $statsData);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
    
        // Create response
        $response = new Response($dompdf->output());
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment;filename="forum_stats.pdf"');
        
        return $response;
    }

    #[Route('/admin/generate-stats-analysis', name: 'admin_generate_stats_analysis', methods: ['GET'])]
    public function generateStatsAnalysis(EntityManagerInterface $entityManager, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            // Get all stats data
            $statsData = [
                'totalPosts' => $entityManager->getRepository(Posts::class)->count([]),
                'totalComments' => $entityManager->getRepository(Comment::class)->count([]),
                'postsByType' => $entityManager->getRepository(Posts::class)
                    ->createQueryBuilder('p')
                    ->select('p.type, COUNT(p.id_post) as count')
                    ->groupBy('p.type')
                    ->getQuery()
                    ->getResult(),
                'mostCommentedPosts' => $entityManager->getRepository(Posts::class)
                    ->createQueryBuilder('p')
                    ->select('p.id_post, p.content, COUNT(c.id_comment) as commentCount')
                    ->leftJoin('App\Entity\gestion_de_depence\Comment', 'c', 'WITH', 'c.id_post = p.id_post')
                    ->groupBy('p.id_post')
                    ->orderBy('commentCount', 'DESC')
                    ->setMaxResults(5)
                    ->getQuery()
                    ->getResult(),
                'mostLikedPosts' => $entityManager->getRepository(Posts::class)
                    ->createQueryBuilder('p')
                    ->select('p.id_post, p.content, COUNT(r.idPost) as likeCount')
                    ->leftJoin('App\Entity\gestion_de_depence\PostReaction', 'r', 'WITH', 'r.idPost = p.id_post AND r.reactionType = :type')
                    ->setParameter('type', 'like')
                    ->groupBy('p.id_post')
                    ->orderBy('likeCount', 'DESC')
                    ->setMaxResults(5)
                    ->getQuery()
                    ->getResult()
            ];

            // Format the prompt with all statistics
            $prompt = "Analyze these forum statistics and provide professional insights:\n\n" .
                     "Total Posts: {$statsData['totalPosts']}\n" .
                     "Total Comments: {$statsData['totalComments']}\n" .
                     "Post Types:\n" . implode("\n", array_map(fn($t) => "- {$t['type']}: {$t['count']}", $statsData['postsByType'])) . "\n\n" .
                     "Top 5 Most Commented Posts:\n" . implode("\n", array_map(function($post, $index) {
                         return ($index+1).". ".mb_substr($post['content'], 0, 30)."... ({$post['commentCount']} comments)";
                     }, $statsData['mostCommentedPosts'], array_keys($statsData['mostCommentedPosts']))) . "\n\n" .
                     "Top 5 Most Liked Posts:\n" . implode("\n", array_map(function($post, $index) {
                         return ($index+1).". ".mb_substr($post['content'], 0, 30)."... ({$post['likeCount']} likes)";
                     }, $statsData['mostLikedPosts'], array_keys($statsData['mostLikedPosts']))) . "\n\n" .
                     "Provide analysis with these sections:\n" .
                     "1. Key Engagement Metrics\n" .
                     "2. Content Performance Overview\n" .
                     "3. User Behavior Insights\n" .
                     "4. Actionable Recommendations\n" .
                     "Format with clear headings and bullet points.";

            // Call Gemini API
                 // Call Gemini API
                 $httpClient = HttpClient::create();
                 $response = $httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent', [
                     'query' => ['key' => 'AIzaSyB9T0YNuLwOYN1LN98fXWKktReSl_pENMU'],
                     'headers' => [
                         'Content-Type' => 'application/json',
                     ],
                     'json' => [
                         'contents' => [
                             [
                                 'parts' => [
                                     ['text' => $prompt]
                                 ]
                             ]
                         ]
                     ]
                 ]);

            // Add HTTP status code check
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('API request failed with status: '.$response->getStatusCode());
            }

            $data = $response->toArray();
            
            // Add null checks for response structure
            if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                throw new \Exception('Invalid API response structure');
            }
            
            $analysis = $data['candidates'][0]['content']['parts'][0]['text'];
            
            // Store analysis in session and redirect
            $request->getSession()->set('ai_analysis', $analysis);
            return $this->redirectToRoute('ai_analysis_report');
    
        } catch (\Exception $e) {
            $request->getSession()->set('ai_analysis_error', 'Analysis failed: ' . $e->getMessage());
            return $this->redirectToRoute('ai_analysis_report');
        }
    }

    #[Route('/admin/ai-analysis-report', name: 'ai_analysis_report', methods: ['GET'])]
    public function showAnalysisReport(Request $request): Response
    {
        $analysis = $request->getSession()->get('ai_analysis');
        $error = $request->getSession()->get('ai_analysis_error');
    
        // Clear session data after reading
        $request->getSession()->remove('ai_analysis');
        $request->getSession()->remove('ai_analysis_error');
    
        if (!$analysis && !$error) {
            return $this->redirectToRoute('admin_forum_stats');
        }
    
        return $this->render('gestion_de_depence/forum/analysis.html.twig', [
            'analysis' => $error ? $error : $analysis
        ]);
    }
}

