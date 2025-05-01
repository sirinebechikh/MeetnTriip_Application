<?php

namespace App\Controller\gestion_de_reservation;

use App\Entity\gestion_de_reservation\Flight;
use App\Repository\gestion_de_reservation\FlightRepository;
use App\Repository\Gestion_evenement\EvenementRepository; // Add this line
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FlightController extends AbstractController
{
    #[Route('/flights', name: 'app_flights')]
    public function index(Request $request, FlightRepository $flightRepository, EvenementRepository $evenementRepository): Response
    {
        // Get all parameters from URL
        $lieuEvenement = $request->query->get('lieuEvenement');
        $date_debut = $request->query->get('date_debut');
        $date_fin = $request->query->get('date_fin');
        $nombre_invite = $request->query->get('nombre_invite');
        $userid = $request->query->get('userid');
        $id_evenement = $request->query->get('id_evenement');

        // Get event details
        $event = null;
        if ($id_evenement) {
            $event = $evenementRepository->find($id_evenement);
        }

        // Get flights for the destination
        $flights = $lieuEvenement ? $flightRepository->findBy(['destination' => $lieuEvenement]) : [];

        // Load airline logos from JSON
        $projectDir = $this->getParameter('kernel.project_dir');
        $airlineLogos = json_decode(
            file_get_contents($projectDir.'/assets/mydummydata/images.json'), 
            true
        );

        return $this->render('gestion_de_reservation/flight/index.html.twig', [
            'flights' => $flights,
            'city' => $lieuEvenement,
            'date_debut' => $date_debut,
            'date_fin' => $date_fin,
            'nombre_invite' => $nombre_invite,
            'userid' => $userid,
            'id_evenement' => $id_evenement,
            'event' => $event,
            'airlineLogos' => $airlineLogos['flights'] ?? []
        ]);
    }

    #[Route('/flights/select', name: 'app_flight_select')]
    public function selectFlight(Request $request, FlightRepository $flightRepository): Response
    {
        $flightId = $request->query->get('flight_id');
        $city = $request->query->get('city');
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');
        $nombreInvite = $request->query->get('nombre_invite');
        $userid = $request->query->get('userid');
        $idEvenement = $request->query->get('id_evenement');

        if (!$flightId) {
            $this->addFlash('error', 'No flight selected.');
            return $this->redirectToRoute('app_flights', [
                'lieuEvenement' => $city,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'nombre_invite' => $nombreInvite,
                'userid' => $userid,
                'id_evenement' => $idEvenement,
            ]);
        }

        $flight = $flightRepository->find($flightId);

        if (!$flight) {
            $this->addFlash('error', 'Flight not found.');
            return $this->redirectToRoute('app_flights', [
                'lieuEvenement' => $city,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'nombre_invite' => $nombreInvite,
                'userid' => $userid,
                'id_evenement' => $idEvenement,
            ]);
        }

        // In selectFlight method
        // Debug data being passed
        $this->addFlash('debug', 'Passing flight and event data to hotels');

        return $this->redirectToRoute('app_hotels', [
            // Flight data
            'flight_id' => $flight->getFlightId(),
            'departure_time' => $flight->getDepartureTime()?->format('H:i'),
            'back_time' => $flight->getBackTime()?->format('H:i'),
            'flight_price' => $flight->getPrice(),
            'airline' => $flight->getAirline(),
            
            // Event data - fix parameter names to match what's expected
            'id_evenement' => $request->query->get('id_evenement'),
            'date_debut' => $request->query->get('date_debut'),
            'date_fin' => $request->query->get('date_fin'),
            'nombre_invite' => $request->query->get('nombre_invite'),
            'city' => $request->query->get('city'), // Add city parameter
            'lieuEvenement' => $request->query->get('lieuEvenement'),
            
            // User data
            'userid' => $request->query->get('userid')
        ]);
    }
}