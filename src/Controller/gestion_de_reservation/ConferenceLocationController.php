<?php

namespace App\Controller\gestion_de_reservation;

use App\Entity\gestion_de_reservation\ConferenceLocation;
use App\Repository\gestion_de_reservation\ConferenceLocationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConferenceLocationController extends AbstractController
{
    #[Route('/conference-locations', name: 'app_conference_locations')]
    public function index(ConferenceLocationRepository $repository, Request $request): Response
    {
        // Get all query parameters
        $hotelId = $request->query->get('hotel_id');
        $hotelPrice = $request->query->get('hotel_price');
        $flightId = $request->query->get('flight_id');
        $flightPrice = $request->query->get('flight_price');
        $userId = $request->query->get('userid');
        $idEvenement = $request->query->get('id_evenement');
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');
        $nombreInvite = $request->query->get('nombre_invite');
        $lieuEvenement = $request->query->get('lieuEvenement'); // Get lieuEvenement instead of city
    
        // Filter locations by lieuEvenement
        $locations = $lieuEvenement ? $repository->findBy(['city' => $lieuEvenement]) : $repository->findAll();
    
        $projectDir = $this->getParameter('kernel.project_dir');
        $conferenceImages = json_decode(
            file_get_contents($projectDir.'/assets/mydummydata/images.json'), 
            true
        )['conference_locations'] ?? [];
    
        return $this->render('gestion_de_reservation/conference_location/index.html.twig', [
            'locations' => $locations,
            'hotel_id' => $hotelId,
            'hotel_price' => $hotelPrice,
            'flight_id' => $flightId,
            'flight_price' => $flightPrice,
            'userid' => $userId,
            'id_evenement' => $idEvenement,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'city' => $lieuEvenement, // Pass lieuEvenement as city
            'nombre_invite' => $nombreInvite,
            'conferenceImages' => $conferenceImages
        ]);
    }

    #[Route('/conference-locations/select', name: 'app_conference_location_select')]
    public function selectLocation(Request $request, ConferenceLocationRepository $repository): Response
    {
        $locationId = $request->get('location_id');
        
        if (!$locationId) {
            $this->addFlash('error', 'No conference location selected.');
            return $this->redirectToRoute('app_conference_locations');
        }

        $location = $repository->find($locationId);
        
        if (!$location) {
            $this->addFlash('error', 'Invalid conference location.');
            return $this->redirectToRoute('app_conference_locations');
        }

        // Debug data
        $this->addFlash('debug', 'Conference Location ID: ' . $locationId);

        return $this->redirectToRoute('app_transport', [
            // Event data
            'id_evenement' => $request->get('id_evenement'),
            'date_debut' => $request->get('date_debut'),
            'date_fin' => $request->get('date_fin'),
            'nombre_invite' => $request->get('nombre_invite'),
            
            // Flight data
            'flight_id' => $request->get('flight_id'),
            'flight_price' => $request->get('flight_price'),
            
            // Hotel data
            'hotel_id' => $request->get('hotel_id'),
            'hotel_price' => $request->get('hotel_price'),
            
            // Conference Location data - ensure these match the expected names in booking
            'conference_location_id' => $locationId,
            'location_id' => $locationId, // Add both versions to ensure compatibility
            'conference_name' => $location->getName(),
            'conference_city' => $location->getCity(),
            'conference_capacity' => $location->getCapacity(),
            'conference_price' => $location->getPricePerDay(),
            'conference_description' => $location->getDescription(),
            
            // User data
            'userid' => $request->get('userid')
        ]);
    }
}