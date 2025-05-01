<?php

namespace App\Controller\gestion_de_reservation;

use App\Entity\gestion_de_reservation\Transport;
use App\Repository\gestion_de_reservation\TransportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TransportController extends AbstractController
{
    #[Route('/transport', name: 'app_transport')]
    public function index(TransportRepository $transportRepository, Request $request): Response
    {
        // Load transport images from JSON
        $projectDir = $this->getParameter('kernel.project_dir');
        $transportImages = json_decode(
            file_get_contents($projectDir.'/assets/mydummydata/images.json'), 
            true
        )['transport'] ?? [];

        return $this->render('gestion_de_reservation/transport/index.html.twig', [
            'transportImages' => $transportImages,
            'transports' => $transportRepository->findAll(),
            'hotel_id' => $request->query->get('hotel_id'),
            'hotel_price' => $request->query->get('hotel_price'),
            'flight_id' => $request->query->get('flight_id'),
            'flight_price' => $request->query->get('flight_price'),
            'location_id' => $request->query->get('location_id'),
            'userid' => $request->query->get('userid'),
            'id_evenement' => $request->query->get('id_evenement'),
            'date_debut' => $request->query->get('date_debut'),
            'date_fin' => $request->query->get('date_fin'),
            'nombre_invite' => $request->query->get('nombre_invite'),
        ]);
    }

    #[Route('/transport/select/{transport_id}', name: 'app_transport_select')]
    public function select(Request $request, TransportRepository $transportRepository, EntityManagerInterface $entityManager, int $transport_id): Response
    {
        $transport = $transportRepository->find($transport_id);
        
        if (!$transport) {
            throw $this->createNotFoundException('Transport not found');
        }

        return $this->redirectToRoute('app_booking', [
            // Event data
            'id_evenement' => $request->query->get('id_evenement'),
            'name_evenement' => $request->query->get('name_evenement'),
            'date_debut' => $request->query->get('date_debut'),
            'date_fin' => $request->query->get('date_fin'),
            'nombre_invite' => $request->query->get('nombre_invite'),
            'lieuEvenement' => $request->query->get('lieuEvenement'),
            
            // Flight data
            'flight_id' => $request->query->get('flight_id'),
            'flight_price' => $request->query->get('flight_price'),
            
            // Hotel data
            'hotel_id' => $request->query->get('hotel_id'),
            'hotel_price' => $request->query->get('hotel_price'),
            
            // Conference Location data
            'location_id' => $request->query->get('location_id'),
            'conference_name' => $request->query->get('conference_name'),
            'conference_city' => $request->query->get('conference_city'),
            'conference_capacity' => $request->query->get('conference_capacity'),
            'conference_price' => $request->query->get('conference_price'),
            'conference_description' => $request->query->get('conference_description'),
            
            // Transport data
            'transport_id' => $transport->getTransportId(),
            'transport_type' => $transport->getType(),
            'transport_price' => $transport->getPrice(),
            'transport_description' => $transport->getDescription(),
            
            // User data
            'userid' => $request->query->get('userid')
        ]);
    }
}