<?php

namespace App\Controller\gestion_de_reservation;

use App\Entity\gestion_de_reservation\ConferenceLocation;
use App\Repository\gestion_de_reservation\ConferenceLocationRepository;
use App\Repository\gestion_de_reservation\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
#[Route('/admin/conference-locations')]
class ConferenceLocationAdminController extends AbstractController
{
    #[Route('/', name: 'admin_conference_locations_list')]
    public function index(ConferenceLocationRepository $repository, Request $request): Response
    {
        $searchTerm = $request->query->get('search', '');
        
        if (!empty($searchTerm)) {
            $locations = $repository->createQueryBuilder('l')
                ->where('LOWER(l.name) LIKE LOWER(:searchTerm)')
                ->setParameter('searchTerm', '%' . $searchTerm . '%')
                ->getQuery()
                ->getResult();
        } else {
            $locations = $repository->findAll();
        }

        return $this->render('gestion_de_reservation/conference_location_admin/index.html.twig', [
            'locations' => $locations,
            'searchTerm' => $searchTerm
        ]);
    }

    #[Route('/new', name: 'admin_conference_location_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $location = new ConferenceLocation();

        if ($request->isMethod('POST')) {
            $location->setName($request->request->get('name'));
            $location->setCity($request->request->get('city'));
            $location->setAddress($request->request->get('address'));
            $location->setCapacity((int)$request->request->get('capacity'));
            $location->setPricePerDay($request->request->get('price_per_day'));
            $location->setDescription($request->request->get('description'));

            $errors = $validator->validate($location);

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('gestion_de_reservation/conference_location_admin/new.html.twig', [
                    'location' => $location
                ]);
            }

            $entityManager->persist($location);
            $entityManager->flush();

            $this->addFlash('success', 'Conference location created successfully.');
            return $this->redirectToRoute('admin_conference_locations_list');
        }

        return $this->render('gestion_de_reservation/conference_location_admin/new.html.twig');
    }

    #[Route('/{id}/edit', name: 'admin_conference_location_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        int $id,
        ConferenceLocationRepository $repository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        $location = $repository->find($id);
        
        if (!$location) {
            $this->addFlash('error', 'Conference location not found.');
            return $this->redirectToRoute('admin_conference_locations_list');
        }

        if ($request->isMethod('POST')) {
            $location->setName($request->request->get('name'));
            $location->setCity($request->request->get('city'));
            $location->setAddress($request->request->get('address'));
            $location->setCapacity((int)$request->request->get('capacity'));
            $location->setPricePerDay($request->request->get('price_per_day'));
            $location->setDescription($request->request->get('description'));

            $errors = $validator->validate($location);

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('gestion_de_reservation/conference_location_admin/edit.html.twig', [
                    'location' => $location
                ]);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Conference location updated successfully.');
            return $this->redirectToRoute('admin_conference_locations_list');
        }

        return $this->render('gestion_de_reservation/conference_location_admin/edit.html.twig', [
            'location' => $location,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_conference_location_delete', methods: ['POST'])]
    public function delete(
        int $id,
        ConferenceLocationRepository $repository,
        EntityManagerInterface $entityManager, 
        BookingRepository $bookingRepository
    ): Response {
        $location = $repository->find($id);
        
        if (!$location) {
            $this->addFlash('error', 'Conference location not found.');
            return $this->redirectToRoute('admin_conference_locations_list');
        }
    
        // Check if location is used in any booking
        $bookings = $bookingRepository->findBy(['conferenceLocation' => $location]);
        
        if (!empty($bookings)) {
            $this->addFlash('error', 'Cannot delete location as it is associated with existing bookings.');
            return $this->redirectToRoute('admin_conference_locations_list');
        }
    
        $entityManager->remove($location);
        $entityManager->flush();
    
        $this->addFlash('success', 'Conference location deleted successfully.');
        return $this->redirectToRoute('admin_conference_locations_list');
    }

    #[Route('/generate-description', name: 'generate_conference_description', methods: ['POST'])]
    public function generateDescription(Request $request): JsonResponse
    {
        try {
            $name = $request->request->get('name');
            $city = $request->request->get('city');
            $address = $request->request->get('address'); // Add this line
            $capacity = $request->request->get('capacity');
            $pricePerDay = $request->request->get('price_per_day');

            // Update validation to include address
            if (!$name || !$city || !$address || !$capacity || !$pricePerDay) {
                return new JsonResponse(['error' => 'Missing required fields'], 400);
            }

            $httpClient = HttpClient::create();
            $apiKey = 'AIzaSyB9T0YNuLwOYN1LN98fXWKktReSl_pENMU';
            
            // Update API endpoint and request structure
            $response = $httpClient->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent', [
                'query' => ['key' => $apiKey],
                'headers' => ['Content-Type' => 'application/json'],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => sprintf("Generate a professional conference venue description using these details:
                                    Name: %s
                                    City: %s
                                    Address: %s
                                    Capacity: %d people
                                    Price: $%s per day
                                    Create 2-3 sentences highlighting key features and value proposition.",
                                        $name,
                                        $city,
                                        $address,
                                        (int)$capacity,
                                        number_format((float)$pricePerDay, 2)
                                    )
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 200
                    ]
                ]
            ]);

            // Add detailed response logging
            $statusCode = $response->getStatusCode();
            $content = $response->getContent();
            
            if ($statusCode !== 200) {
                throw new \Exception("API returned status $statusCode. Response: " . substr($content, 0, 200));
            }

            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
            }

            // Handle API error responses
            if (isset($data['error'])) {
                throw new \Exception($data['error']['message'] ?? 'Unknown API error');
            }

            if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                throw new \Exception('Unexpected API response structure: ' . json_encode($data));
            }

            $description = $data['candidates'][0]['content']['parts'][0]['text'];
            return new JsonResponse(['description' => $description]);

        } catch (\Exception $e) {
            // Log the full error for debugging
            error_log("Description generation error: " . $e->getMessage());
            return new JsonResponse(
                ['error' => 'Generation failed: ' . $e->getMessage()],
                500
            );
        }
    }
}