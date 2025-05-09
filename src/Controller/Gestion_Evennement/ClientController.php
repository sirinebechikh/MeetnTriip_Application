<?php

namespace App\Controller\Gestion_Evennement;
 
use App\Entity\gestion_user\User;
use  App\Repository\Gestion_evenement\EvenementRepository;
use App\Entity\Gestion_Evenement\Evenement;
use App\Form\EvenementType;
use App\Form\Gestion_Evenement\AiEventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Writer;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;

final class ClientController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    // Display only the events of the connected user
    #[Route('/client/evenements', name: 'client_evenements')]
    public function index(EvenementRepository $evenementRepository, FormFactoryInterface $formFactory): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');

        if (!$this->isGranted('ROLE_CLIENT')) {
            return $this->redirectToRoute('app_home');
        }
        $user = $this->getUser(); // Get the connected user

        // Filter events for the connected user
        $evenements = $this->entityManager->getRepository(Evenement::class)->findBy(['user' => $user]);

        // Create an array of edit forms
        $editForms = [];
        foreach ($evenements as $event) {
            $editForms[$event->getId()] = $this->createForm(EvenementType::class, $event)->createView();
        }
        $this->denyAccessUnlessGranted('ROLE_CLIENT');

        // Add AI form creation
        $aiForm = $formFactory->createNamed('ai_event', AiEventType::class);

        // Create main form for event creation
        $form = $this->createForm(EvenementType::class);
        
        return $this->render('Gestion_Evennement/client/index.html.twig', [
            'evenements' => $evenements,
            'form' => $form->createView(),  // Add main form
            'editForms' => $editForms,
            'aiForm' => $aiForm->createView()
        ]);
    }

    //  Create a new event
   //  Create a new event
#[Route('/client/evenement/new', name: 'client_evenement_new', methods: ['POST'])]
public function new(Request $request): Response
{
    $evenement = new Evenement();
    $form = $this->createForm(EvenementType::class, $evenement);
    $form->handleRequest($request);

    // Always return JSON for AJAX requests
    if ($request->isXmlHttpRequest()) {
        if ($form->isSubmitted() && $form->isValid()) {
            
            $dateDebut = $evenement->getDateDebut();
            $dateFin = $evenement->getDateFin();

            if ($dateFin <= $dateDebut) {
                return $this->json([
                    'success' => false,
                    'errors' => ['dateFin' => 'The end date must be after the start date.']
                ]);
            }

            // Assign the connected user
            $user = $this->getUser();
            $evenement->setUser($user);

            // Handle image upload
            $imageFile = $form->get('imagePath')->getData();
            if ($imageFile) {
                try {
                    if (!in_array($imageFile->getMimeType(), ['image/jpeg', 'image/png'])) {
                        return $this->json([
                            'success' => false,
                            'errors' => ['imagePath' => 'Invalid format. Only JPEG and PNG are accepted.']
                        ]);
                    }

                    if ($imageFile->getSize() > 5 * 1024 * 1024) {
                        return $this->json([
                            'success' => false,
                            'errors' => ['imagePath' => 'Maximum size is 5MB.']
                        ]);
                    }

                    $newFilename = uniqid().'.'.$imageFile->guessExtension();
                    $imageFile->move($this->getParameter('event_images_directory'), $newFilename);
                    $evenement->setImagePath($newFilename);
                } catch (FileException $e) {
                    return $this->json([
                        'success' => false,
                        'errors' => ['imagePath' => 'Image upload error: '.$e->getMessage()]
                    ]);
                }
            }

            $this->entityManager->persist($evenement);
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Event successfully created!'
            ]);
        } else if ($form->isSubmitted()) {
            // Extract form errors
            $errors = [];
            foreach ($form->all() as $child) {
                if (!$child->isValid()) {
                    foreach ($child->getErrors() as $error) {
                        $errors[$child->getName()] = $error->getMessage();
                    }
                }
            }
            
            return $this->json([
                'success' => false,
                'errors' => $errors
            ]);
        }
    }

    // For non-AJAX requests
    if ($form->isSubmitted() && $form->isValid()) {
        $dateDebut = $evenement->getDateDebut();
        $dateFin = $evenement->getDateFin();

        if ($dateFin <= $dateDebut) {
            $this->addFlash('error', 'The end date must be after the start date.');
            return $this->redirectToRoute('client_evenements');
        }

        // Assign the connected user
        $user = $this->getUser();
        $evenement->setUser($user);

        // Handle image upload
        $imageFile = $form->get('imagePath')->getData();
        if ($imageFile) {
            try {
                if (!in_array($imageFile->getMimeType(), ['image/jpeg', 'image/png'])) {
                    $this->addFlash('error', 'Invalid format. Only JPEG and PNG are accepted.');
                    return $this->redirectToRoute('client_evenements');
                }

                if ($imageFile->getSize() > 5 * 1024 * 1024) {
                    $this->addFlash('error', 'Maximum size is 5MB.');
                    return $this->redirectToRoute('client_evenements');
                }

                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move($this->getParameter('event_images_directory'), $newFilename);
                $evenement->setImagePath($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Image upload error: '.$e->getMessage());
                return $this->redirectToRoute('client_evenements');
            }
        }

        $this->entityManager->persist($evenement);
        $this->entityManager->flush();
        $this->addFlash('success', 'Event successfully created!');
    }

    return $this->redirectToRoute('client_evenements');
}
    // Helper method to extract form errors
private function getFormErrors($form): array
{
    $errors = [];
    foreach ($form->getErrors(true) as $error) {
        $fieldName = $error->getOrigin()->getName();
        $errors[$fieldName] = $error->getMessage();
    }
    
    // Also collect errors from each field
    foreach ($form as $fieldName => $field) {
        if ($field->getErrors()->count() > 0) {
            foreach ($field->getErrors() as $error) {
                $errors[$fieldName] = $error->getMessage();
            }
        }
    }
    
    return $errors;
}

   // Edit an event
   #[Route('/client/evenement/{id}/edit', name: 'client_evenement_edit', methods: ['POST'])]
   #[Route('/edit/{id}', name: 'client_edit')]
   public function edit(Request $request, int $id): Response
   {
       $evenement = $this->entityManager->getRepository(Evenement::class)->find($id);
       
       if (!$evenement) {
           throw $this->createNotFoundException('Event not found');
       }
   
       $form = $this->createForm(EvenementType::class, $evenement);
       $form->handleRequest($request);
   
       // Handle AJAX requests
       if ($request->isXmlHttpRequest()) {
           if ($form->isSubmitted() && $form->isValid()) {
               $imageFile = $form->get('imagePath')->getData();
               if ($imageFile) {
                   try {
                       if (!in_array($imageFile->getMimeType(), ['image/jpeg', 'image/png'])) {
                           return $this->json([
                               'success' => false,
                               'errors' => ['imagePath' => 'Invalid format. Only JPEG and PNG are accepted.']
                           ]);
                       }
   
                       if ($imageFile->getSize() > 5 * 1024 * 1024) {
                           return $this->json([
                               'success' => false,
                               'errors' => ['imagePath' => 'Maximum size is 5MB.']
                           ]);
                       }
   
                       if ($evenement->getImagePath()) {
                           $oldImage = $this->getParameter('event_images_directory').'/'.$evenement->getImagePath();
                           if (file_exists($oldImage)) {
                               unlink($oldImage);
                           }
                       }
   
                       $newFilename = uniqid().'.'.$imageFile->guessExtension();
                       $imageFile->move($this->getParameter('event_images_directory'), $newFilename);
                       $evenement->setImagePath($newFilename);
                   } catch (FileException $e) {
                       return $this->json([
                           'success' => false,
                           'errors' => ['imagePath' => 'Image upload error: '.$e->getMessage()]
                       ]);
                   }
               }
   
               $this->entityManager->flush();
               
               return $this->json([
                   'success' => true,
                   'message' => 'Event successfully updated!'
               ]);
           } else if ($form->isSubmitted()) {
               // Extract form errors
               $errors = [];
               foreach ($form->all() as $child) {
                   if (!$child->isValid()) {
                       foreach ($child->getErrors() as $error) {
                           $errors[$child->getName()] = $error->getMessage();
                       }
                   }
               }
               
               return $this->json([
                   'success' => false,
                   'errors' => $errors
               ]);
           }
       }
   
       // Handle regular form submissions
       if ($form->isSubmitted() && $form->isValid()) {
           $imageFile = $form->get('imagePath')->getData();
           if ($imageFile) {
               try {
                   if (!in_array($imageFile->getMimeType(), ['image/jpeg', 'image/png'])) {
                       $this->addFlash('error', 'Invalid format. Only JPEG and PNG are accepted.');
                       return $this->redirectToRoute('client_evenements');
                   }
   
                   if ($imageFile->getSize() > 5 * 1024 * 1024) {
                       $this->addFlash('error', 'Maximum size is 5MB.');
                       return $this->redirectToRoute('client_evenements');
                   }
   
                   if ($evenement->getImagePath()) {
                       $oldImage = $this->getParameter('event_images_directory').'/'.$evenement->getImagePath();
                       if (file_exists($oldImage)) {
                           unlink($oldImage);
                       }
                   }
   
                   $newFilename = uniqid().'.'.$imageFile->guessExtension();
                   $imageFile->move($this->getParameter('event_images_directory'), $newFilename);
                   $evenement->setImagePath($newFilename);
               } catch (FileException $e) {
                   $this->addFlash('error', 'Image upload error: '.$e->getMessage());
                   return $this->redirectToRoute('client_evenements');
               }
           }
   
           $this->entityManager->flush();
           $this->addFlash('success', 'Event successfully updated!');
           return $this->redirectToRoute('client_evenements');
       }
   
       if ($form->isSubmitted() && !$form->isValid()) {
           $this->addFlash('error', 'There were errors in your form. Please check and try again.');
       }
   
       return $this->redirectToRoute('client_evenements');
   }    #[Route('/client/evenement/{id}/delete', name: 'client_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $evenement = $this->entityManager->getRepository(Evenement::class)->find($id);
    
        if (!$evenement) {
            $this->addFlash('error', 'Event not found.');
            return $this->redirectToRoute('client_evenements');
        }
    
        $user = $this->getUser();
        if ($evenement->getUser() !== $user) {
            $this->addFlash('error', 'You can only delete your own events.');
            return $this->redirectToRoute('client_evenements');
        }
    
        if ($this->isCsrfTokenValid('delete' . $evenement->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($evenement);
            $this->entityManager->flush();
            $this->addFlash('success', 'Event successfully deleted!');
        }
    
        return $this->redirectToRoute('client_evenements');
    }
    
    // GENERATE with IA      
    #[Route('/client/ai/generate-event', name: 'ai_generate_event', methods: ['POST'])]
    public function aiGenerate(Request $request): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(AiEventType::class, $evenement);
        $form->handleRequest($request);
    
        // Handle AJAX requests
        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    // Safety checks for required properties
                    if (!$evenement->getNom() || !$evenement->getType() || !$evenement->getNombreInvite() 
                        || !$evenement->getDateDebut() || !$evenement->getDateFin() || !$evenement->getLieuEvenement()) {
                        return $this->json([
                            'success' => false,
                            'errors' => ['form' => 'All fields are required to generate an AI event.']
                        ]);
                    }
    
                    $httpClient = HttpClient::create();
                    $apiKey = 'AIzaSyB9T0YNuLwOYN1LN98fXWKktReSl_pENMU';
                    
                    // Generate description and details using Gemini
                    $geminiResponse = $httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent', [
                        'query' => ['key' => $apiKey],
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                        'json' => [
                            'contents' => [
                                'parts' => [
                                    [
                                        'text' => "Generate event details as JSON with EXACTLY these fields: 
                                        {
                                          \"description\": \"string\",
                                          \"budget\": number,
                                          \"activities\": [\"string1\", \"string2\"]
                                        }
                                        Based on: 
                                        Name: {$evenement->getNom()}, 
                                        Type: {$evenement->getType()}, 
                                        Guests: {$evenement->getNombreInvite()}, 
                                        Dates: {$evenement->getDateDebut()->format('Y-m-d')} to {$evenement->getDateFin()->format('Y-m-d')}, 
                                        Location: {$evenement->getLieuEvenement()}. 
                                        Budget must be a float. Return ONLY the JSON object."
                                    ]
                                ]
                            ]
                        ]
                    ]);
    
                    // Parse Gemini response
                    $responseData = $geminiResponse->toArray();
                    
                    if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                        throw new \Exception('Invalid response structure from Gemini API');
                    }
                    
                    $aiContent = $responseData['candidates'][0]['content']['parts'][0]['text'];
                    
                    // Sanitize the response
                    $aiContent = trim($aiContent);
                    $aiContent = preg_replace('/^```json|```$/i', '', $aiContent); // Remove JSON code blocks
                    
                    $aiData = json_decode($aiContent, true);
    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \Exception('Failed to parse Gemini response: '.json_last_error_msg().'. Received: '.$aiContent);
                    }
    
                    // Add validation for required fields
                    if (!isset($aiData['description'], $aiData['budget'], $aiData['activities'])) {
                        throw new \Exception('Missing required fields in AI response');
                    }
    
                    // Get image from Pexels
                    // Pexels API call
                    $pexelsResponse = $httpClient->request('GET', 'https://api.pexels.com/v1/search', [
                        'headers' => [
                            'Authorization' => 'rrB2lAZowyS2XCTHxYZMeqQBSoN8ScE2rB0MyxtrCvJWGmquEqxqPwOj'
                        ],
                        'query' => [
                            'query' => $evenement->getNom().' '.$evenement->getLieuEvenement(),
                            'per_page' => 1
                        ]
                    ]);
    
                    $imageUrl = $pexelsResponse->toArray()['photos'][0]['src']['large'];
                    
                    // Save image
                    $imageContent = file_get_contents($imageUrl);
                    $newFilename = uniqid().'.jpg';
                    file_put_contents($this->getParameter('event_images_directory').'/'.$newFilename, $imageContent);
    
                    // Set generated fields
                    $evenement->setDescription($aiData['description']);
                    $evenement->setBudgetPrevu($aiData['budget']);
                    $evenement->setActivities(implode(', ', $aiData['activities']));  // Convert array to string
                    $evenement->setImagePath($newFilename);
                    $evenement->setUser($this->getUser());
    
                    $this->entityManager->persist($evenement);
                    $this->entityManager->flush();
    
                    return $this->json([
                        'success' => true,
                        'message' => 'AI-generated event saved!'
                    ]);
                } catch (\Exception $e) {
                    return $this->json([
                        'success' => false,
                        'errors' => ['api' => 'AI generation failed: '.$e->getMessage()]
                    ]);
                }
            } else if ($form->isSubmitted()) {
                // Extract form errors
                $errors = [];
                foreach ($form->all() as $child) {
                    if (!$child->isValid()) {
                        foreach ($child->getErrors() as $error) {
                            $errors[$child->getName()] = $error->getMessage();
                        }
                    }
                }
                
                return $this->json([
                    'success' => false,
                    'errors' => $errors
                ]);
            }
            
            // Default JSON response for AJAX requests with no submission
            return $this->json([
                'success' => false,
                'errors' => ['form' => 'No data submitted.']
            ]);
        }
    
        // Handle regular form submissions
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Safety checks for required properties
                if (!$evenement->getNom() || !$evenement->getType() || !$evenement->getNombreInvite() 
                    || !$evenement->getDateDebut() || !$evenement->getDateFin() || !$evenement->getLieuEvenement()) {
                    $this->addFlash('error', 'All fields are required to generate an AI event.');
                    return $this->redirectToRoute('client_evenements');
                }
                
                $httpClient = HttpClient::create();
                $apiKey = 'AIzaSyB9T0YNuLwOYN1LN98fXWKktReSl_pENMU';
                
                // Generate description and details using Gemini
                $geminiResponse = $httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent', [
                    'query' => ['key' => $apiKey],
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'contents' => [
                            'parts' => [
                                [
                                    'text' => "Generate event details as JSON with EXACTLY these fields: 
                                    {
                                      \"description\": \"string\",
                                      \"budget\": number,
                                      \"activities\": [\"string1\", \"string2\"]
                                    }
                                    Based on: 
                                    Name: {$evenement->getNom()}, 
                                    Type: {$evenement->getType()}, 
                                    Guests: {$evenement->getNombreInvite()}, 
                                    Dates: {$evenement->getDateDebut()->format('Y-m-d')} to {$evenement->getDateFin()->format('Y-m-d')}, 
                                    Location: {$evenement->getLieuEvenement()}. 
                                    Budget must be a float. Return ONLY the JSON object."
                                ]
                            ]
                        ]
                    ]
                ]);
    
                // Parse Gemini response
                $responseData = $geminiResponse->toArray();
                
                if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                    throw new \Exception('Invalid response structure from Gemini API');
                }
                
                $aiContent = $responseData['candidates'][0]['content']['parts'][0]['text'];
                
                // Sanitize the response
                $aiContent = trim($aiContent);
                $aiContent = preg_replace('/^```json|```$/i', '', $aiContent); // Remove JSON code blocks
                
                $aiData = json_decode($aiContent, true);
    
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Failed to parse Gemini response: '.json_last_error_msg().'. Received: '.$aiContent);
                }
    
                // Add validation for required fields
                if (!isset($aiData['description'], $aiData['budget'], $aiData['activities'])) {
                    throw new \Exception('Missing required fields in AI response');
                }
    
                // Get image from Pexels
                // Pexels API call
                $pexelsResponse = $httpClient->request('GET', 'https://api.pexels.com/v1/search', [
                    'headers' => [
                        'Authorization' => 'rrB2lAZowyS2XCTHxYZMeqQBSoN8ScE2rB0MyxtrCvJWGmquEqxqPwOj'
                    ],
                    'query' => [
                        'query' => $evenement->getNom().' '.$evenement->getLieuEvenement(),
                        'per_page' => 1
                    ]
                ]);
    
                $imageUrl = $pexelsResponse->toArray()['photos'][0]['src']['large'];
                
                // Save image
                $imageContent = file_get_contents($imageUrl);
                $newFilename = uniqid().'.jpg';
                file_put_contents($this->getParameter('event_images_directory').'/'.$newFilename, $imageContent);
    
                // Set generated fields
                $evenement->setDescription($aiData['description']);
                $evenement->setBudgetPrevu($aiData['budget']);
                $evenement->setActivities(implode(', ', $aiData['activities']));  // Convert array to string
                $evenement->setImagePath($newFilename);
                $evenement->setUser($this->getUser());
    
                $this->entityManager->persist($evenement);
                $this->entityManager->flush();
    
                $this->addFlash('success', 'AI-generated event saved!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'AI generation failed: '.$e->getMessage());
            }
    
            return $this->redirectToRoute('client_evenements');
        }
    
        if ($form->isSubmitted()) {
            $this->addFlash('error', 'Invalid AI generation data');
        }
        
        return $this->redirectToRoute('client_evenements');
    }
    #[Route('/client/evenements/confirmed/{type}', name: 'client_evenements_confirmed', defaults: ['type' => null])]
    public function showConfirmedEvents(EvenementRepository $evenementRepository, ?string $type): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        
        $user = $this->getUser();
        
        $qb = $evenementRepository->createQueryBuilder('e')
            ->where('e.validated = :validated')
            ->andWhere('e.user = :user')
            ->setParameter('validated', true)
            ->setParameter('user', $user);
    
        if ($type) {
            $qb->andWhere('e.type = :type')
               ->setParameter('type', $type);
        }
    
        $confirmedEvents = $qb->orderBy('e.dateDebut', 'DESC')
                          ->getQuery()
                          ->getResult();
    
        return $this->render('Gestion_Evennement/client/confirmed_events.html.twig', [
            'evenements' => $confirmedEvents,
            'currentFilter' => $type,
            'user' => $user
        ]);
    }
    
    #[Route('/client/evenement/{id}/download', name: 'client_event_download', methods: ['GET'])]
    public function downloadPdf(int $id): Response
    {
        $evenement = $this->entityManager->getRepository(Evenement::class)->find($id);
        
        if (!$evenement) {
            throw $this->createNotFoundException('Event not found');
        }
    
        $html = $this->renderView('Gestion_Evennement/client/event_pdf.html.twig', [
            'event' => $evenement
        ]);
    
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
    
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="event-'.$id.'.pdf"'
            ]
        );
    }
    
    // Add these use statements with the other imports
  

    #[Route('/client/evenement/{id}/qrcode', name: 'client_event_qrcode', methods: ['GET'])]
    public function generateQrCode(int $id): Response
    {
        $evenement = $this->entityManager->getRepository(Evenement::class)->find($id);
        
        if (!$evenement) {
            throw $this->createNotFoundException('Event not found');
        }
    
        // Create event details string
        $eventDetails = json_encode([
            'Event Name' => $evenement->getNom(),
            'Type' => $evenement->getType(),
            'Location' => $evenement->getLieuEvenement(),
            'Start Date' => $evenement->getDateDebut()->format('Y-m-d'),
            'End Date' => $evenement->getDateFin()->format('Y-m-d'),
            'Guests' => $evenement->getNombreInvite(),
            'Budget' => $evenement->getBudgetPrevu(),
            'URL' => $this->generateUrl('app_evenement_show', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL)
        ]);
    
        $renderer = new ImageRenderer(
            new RendererStyle(400, 4), // Increased size and margin
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $svg = $writer->writeString($eventDetails);
    
        return new Response(
            $svg,
            Response::HTTP_OK,
            [
                'Content-Type' => 'image/svg+xml',
                'Cache-Control' => 'max-age=60, public'
            ]
        );
    }
}