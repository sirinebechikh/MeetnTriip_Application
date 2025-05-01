<?php

namespace App\Controller\gestion_de_reservation;

use App\Entity\gestion_de_reservation\Hotel;
use App\Repository\gestion_de_reservation\HotelRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HotelController extends AbstractController
{
    #[Route('/hotels', name: 'app_hotels')]
    public function index(HotelRepository $hotelRepository, Request $request): Response
    {
        // Get all query parameters
        $city = $request->query->get('city');
        $flightId = $request->query->get('flight_id');
        $departureTime = $request->query->get('departure_time');
        $backTime = $request->query->get('back_time');
        $flightPrice = $request->query->get('flight_price');
        $userId = $request->query->get('userid');
        $idEvenement = $request->query->get('id_evenement');
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');
        $nombreInvite = $request->query->get('nombre_invite');
        $lieuEvenement = $request->query->get('lieuEvenement');
        
        // Load hotel images from JSON
        $projectDir = $this->getParameter('kernel.project_dir');
        $hotelImages = json_decode(
            file_get_contents($projectDir.'/assets/mydummydata/images.json'), 
            true
        )['hotels'] ?? [];
    
        // Filter hotels by city if provided
        $hotels = $city ? $hotelRepository->findBy(['city' => $city]) : $hotelRepository->findAll();
    
        return $this->render('gestion_de_reservation/hotel/index.html.twig', [
            'hotelImages' => $hotelImages,
            'hotels' => $hotels,
            // Pass all event data
            'id_evenement' => $idEvenement,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'nombre_invite' => $nombreInvite,
            'city' => $city,
            'lieuEvenement' => $lieuEvenement,
            // Pass all booking related data
            'flight_id' => $flightId,
            'departure_time' => $departureTime,
            'back_time' => $backTime,
            'flight_price' => $flightPrice,
            'userid' => $userId,
        ]);
    }

    #[Route('/hotels/select', name: 'app_hotel_select')]
    public function select(Request $request, HotelRepository $hotelRepository): Response
    {
        $hotelId = $request->query->get('hotel_id');
        
        if (!$hotelId) {
            $this->addFlash('error', 'Hotel ID is required');
            return $this->redirectToRoute('app_hotels');
        }
    
        $hotel = $hotelRepository->find($hotelId);
    
        if (!$hotel) {
            $this->addFlash('error', 'Hotel not found');
            return $this->redirectToRoute('app_hotels');
        }
    
        return $this->redirectToRoute('app_conference_locations', [
            // Event data - maintain consistent parameter names
            'id_evenement' => $request->query->get('id_evenement'),
            'date_debut' => $request->query->get('date_debut'),
            'date_fin' => $request->query->get('date_fin'),
            'nombre_invite' => $request->query->get('nombre_invite'),
            'lieuEvenement' => $hotel->getCity(), // Use the hotel's city as lieuEvenement
            
            // Flight data
            'flight_id' => $request->query->get('flight_id'),
            'departure_time' => $request->query->get('departure_time'),
            'back_time' => $request->query->get('back_time'),
            'flight_price' => $request->query->get('flight_price'),
            
            // Hotel data
            'hotel_id' => $hotel->getHotelId(),
            'hotel_name' => $hotel->getName(),
            'hotel_price' => $hotel->getPricePerNight(), // Changed from getPrice()
            
            // User data
            'userid' => $request->query->get('userid')
        ]);
    }
}