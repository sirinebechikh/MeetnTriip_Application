<?php
namespace App\Controller\Gestion_Evennement;

use App\Entity\Gestion_Evenement\Evenement;
use App\Entity\gestion_user\{User,UserRole};
use App\Form\AdminEvenementType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
 
 final class AdminController extends AbstractController
{
    private $entityManager;
    private $paginator;

    public function __construct(EntityManagerInterface $entityManager, PaginatorInterface $paginator)
    {
        $this->entityManager = $entityManager;
        $this->paginator = $paginator;
    }

    // ✅ Display all events
    #[Route('/admin/evenements', name: 'admin_evenements')]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_home');
        }

        $searchTerm = $request->query->get('search', '');
        
        $queryBuilder = $this->entityManager->getRepository(Evenement::class)
            ->createQueryBuilder('e')
            ->orderBy('e.id', 'DESC');

        if (!empty($searchTerm)) {
            $queryBuilder
                ->where('LOWER(e.nom) LIKE LOWER(:searchTerm)')
                ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        $pagination = $this->paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            2// Items per page
        );

        return $this->render('Gestion_Evennement/admin/index.html.twig', [
            'evenements' => $pagination,
            'form' => $this->createForm(AdminEvenementType::class, new Evenement())->createView(),
            'searchTerm' => $searchTerm
        ]);
    }

    // ✅ Display events by user_id
    #[Route('/admin/evenements/user/{id}', name: 'admin_evenements_par_user')]
    public function indexParUser(int $id): Response
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            $this->addFlash('error1', 'User not found.');
            return $this->redirectToRoute('admin_evenements');
        }

        $evenements = $this->entityManager->getRepository(Evenement::class)
            ->findBy(['user' => $user]);

        $editForms = [];
        foreach ($evenements as $event) {
            $editForms[$event->getId()] = $this->createForm(AdminEvenementType::class, $event)->createView();
        }

        return $this->render('Gestion_Evennement/admin/index.html.twig', [
            'evenements' => $evenements,
            'form' => $this->createForm(AdminEvenementType::class, new Evenement())->createView(),
            'editForms' => $editForms,
            'user' => $user
        ]);
    }

    // ✅ Create a new event
    #[Route('/admin/evenement/new', name: 'admin_evenement_new', methods: ['POST'])]
    public function new(Request $request): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(AdminEvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dateDebut = $evenement->getDateDebut();
            $dateFin = $evenement->getDateFin();

            if ($dateFin <= $dateDebut) {
                $this->addFlash('error1', 'End date must be after start date.');
                return $this->redirectToRoute('admin_evenements');
            }

            // Assign the logged-in user
            $user = $this->getUser();
            $evenement->setUser($user);

            // Handle image upload
            $imageFile = $form->get('imagePath')->getData();
            if ($imageFile) {
                try {
                    if (!in_array($imageFile->getMimeType(), ['image/jpeg', 'image/png'])) {
                        $this->addFlash('error1', 'Invalid format. Only JPEG and PNG are allowed.');
                        return $this->redirectToRoute('admin_evenements');
                    }

                    if ($imageFile->getSize() > 5 * 1024 * 1024) {
                        $this->addFlash('error1', 'Maximum size is 5MB.');
                        return $this->redirectToRoute('admin_evenements');
                    }

                    $newFilename = uniqid().'.'.$imageFile->guessExtension();
                    $imageFile->move($this->getParameter('event_images_directory'), $newFilename);
                    $evenement->setImagePath($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error1', 'Image upload error: '.$e->getMessage());
                    return $this->redirectToRoute('admin_evenements');
                }
            }

            $this->entityManager->persist($evenement);
            $this->entityManager->flush();
            $this->addFlash('success1', 'Event successfully created!');
            return $this->redirectToRoute('admin_evenements');
        }

        return $this->redirectToRoute('admin_evenements');
    }

    // ✅ Edit an event
    #[Route('/admin/evenement/{id}/edit', name: 'admin_evenement_edit', methods: ['POST'])]
public function edit(Request $request, int $id): Response
{
    $evenement = $this->entityManager->getRepository(Evenement::class)->find($id);

    if (!$evenement) {
        $this->addFlash('error1', 'Event not found!');
        return $this->redirectToRoute('admin_evenements');
    }

    $form = $this->createForm(AdminEvenementType::class, $evenement);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $imageFile = $form->get('imagePath')->getData();
        if ($imageFile) {
            if ($evenement->getImagePath()) {
                $oldImage = $this->getParameter('event_images_directory') . '/' . $evenement->getImagePath();
                if (file_exists($oldImage)) {
                    unlink($oldImage);
                }
            }

            $newFilename = uniqid() . '.' . $imageFile->guessExtension();
            $imageFile->move($this->getParameter('event_images_directory'), $newFilename);
            $evenement->setImagePath($newFilename);
        }

        $this->entityManager->flush();
        $this->addFlash('success1', 'Event updated successfully!');
    }

    return $this->redirectToRoute('admin_evenements');
}

    // ✅ Delete an event
    #[Route('/admin/evenement/{id}/delete', name: 'admin_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $evenement = $this->entityManager->getRepository(Evenement::class)->find($id);
    
        if (!$evenement) {
            $this->addFlash('error', 'Event not found.');
            return $this->redirectToRoute('admin_evenements');
        }
    
        $user = $this->getUser();
        if ($evenement->getUser() !== $user) {
            $this->addFlash('error', 'You can only delete your own events.');
            return $this->redirectToRoute('admin_evenements');
        }
    
             $this->entityManager->remove($evenement);
            $this->entityManager->flush();
            $this->addFlash('success', 'Event successfully deleted!');
        
    
        return $this->redirectToRoute('admin_evenements');}
    

    // ✅ Accept an event
    #[Route('/admin/evenement/{id}/accept', name: 'admin_evenement_accept', methods: ['POST'])]
    // Update the acceptEvent method parameters
    public function acceptEvent(int $id, Request $request, MailerInterface $mailer): Response
    {
        $evenement = $this->entityManager->getRepository(Evenement::class)->find($id);
    
        if (!$evenement) {
            $this->addFlash('error1', 'Event not found.');
            return $this->redirectToRoute('admin_evenements');
        }
    
        // Valider l'événement
        $evenement->setValidated(true);
        $this->entityManager->flush();
    
        // Send confirmation email
        $user = $evenement->getUser();
        if ($user && method_exists($user, 'getEmail')) {
            $email = (new Email())
                ->from('MeetNTrip <borgimoatez@gmail.com>')
                ->to($user->getEmail())
                ->subject('Event Accepted: ' . $evenement->getNom())
                ->html($this->renderView('emails/event_accepted.html.twig', [
                    'event' => $evenement
                ]));
    
            try {
                $mailer->send($email);
                $this->addFlash('success1', 'Event accepted and notification sent!');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Event accepted but email failed to send: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('warning', 'Event accepted but no user email found');
        }
    
        return $this->redirectToRoute('admin_evenements');
    }
    // ✅ Reject an event
    #[Route('/admin/evenement/{id}/reject', name: 'admin_evenement_reject', methods: ['POST'])]
    // Update the rejectEvent method
    public function rejectEvent(int $id, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $evenement = $entityManager->getRepository(Evenement::class)->find($id);
    
        if (!$evenement) {
            $this->addFlash('error1', 'Event not found!');
            return $this->redirectToRoute('admin_evenements');
        }
    
        $evenement->setValidated(false);
        $entityManager->flush();
    
        // Send rejection email
        $user = $evenement->getUser();
        if ($user && method_exists($user, 'getEmail')) {
            $email = (new Email())
                ->from('MeetNTrip <borgimoatez@gmail.com>')
                ->to($user->getEmail())
                ->subject('Event Rejected: ' . $evenement->getNom())
                ->html($this->renderView('emails/event_rejected.html.twig', [
                    'event' => $evenement
                ]));
    
            try {
                $mailer->send($email);
                $this->addFlash('success1', 'Event rejected and notification sent!');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Event rejected but email failed to send: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('warning', 'Event rejected but no user email found');
        }
    
        return $this->redirectToRoute('admin_evenements');
    }
    #[Route('/admin/evenement/{id}', name: 'admin_evenement_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $evenement = $this->entityManager->getRepository(Evenement::class)->find($id);

        if (!$evenement) {
            $this->addFlash('error1', 'Event not found!');
            return $this->redirectToRoute('admin_evenements');
        }

        return $this->render('Gestion_Evennement/admin/show.html.twig', [
            'event' => $evenement,
        ]);
    }
}