<?php

namespace App\Controller\gestion_de_reservation;

use App\Entity\gestion_user\User;
use App\Entity\Gestion_Evenement\Evenement;
use App\Repository\Gestion_evenement\EvenementRepository;
use App\Service\GeminiAIService;
use App\Repository\gestion_de_reservation\{
    FlightRepository,
    HotelRepository,
    TransportRepository,
    ConferenceLocationRepository
};
use App\Entity\gestion_de_reservation\Booking;
use App\Repository\gestion_de_reservation\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

#[AsController]
class EvenementController extends AbstractController
{
    #[Route('/event', name: 'app_evenement_index')]
    public function index(EvenementRepository $evenementRepository, Request $request): Response
    {
        $user = $this->getUser();
        $type = $request->query->get('type');
        
        $qb = $evenementRepository->createQueryBuilder('e')
            ->where('e.validated = true')
            ->andWhere('e.user = :user')
            ->setParameter('user', $user);
    
        if ($type) {
            $qb->andWhere('e.type = :type')
               ->setParameter('type', $type);
        }
    
        $evenements = $qb->getQuery()->getResult();
    
        return $this->render('gestion_de_reservation/evenement/index.html.twig', [
            'evenements' => $evenements,
            'currentFilter' => $type
        ]);
    }
    

    #[Route('/evenement/select', name: 'app_evenement_select')]
    public function selectEvent(Request $request, EvenementRepository $evenementRepository): Response
    {
        $eventId = $request->query->get('id');

        if (!$eventId) {
            $this->addFlash('error', 'No event selected.');
            return $this->redirectToRoute('app_evenement_index');
        }

        $event = $evenementRepository->find($eventId);

        if (!$event) {
            $this->addFlash('error', 'Event not found.');
            return $this->redirectToRoute('app_evenement_index');
        }

        // Debug the event data being passed
        $this->addFlash('debug', sprintf(
            'Passing event data - ID: %d, Name: %s, Location: %s',
            $event->getId(),
            $event->getNom(),
            $event->getLieuEvenement()
        ));

        return $this->redirectToRoute('app_flights', [
            'lieuEvenement' => $event->getLieuEvenement(),
            'date_debut' => $event->getDateDebut()->format('Y-m-d'),
            'date_fin' => $event->getDateFin()->format('Y-m-d'),
            'nombre_invite' => $event->getNombreInvite(),
            'userid' => $event->getUser()?->getId(),
            'id_evenement' => $event->getId(),
            'name_evenement' => $event->getNom(),
            'description' => $event->getDescription(),
            'type_evenement' => $event->getType()
            // Removed status_evenement since getStatus() doesn't exist
        ]);
    }

    #[Route('/evenement/{id}', name: 'app_evenement_show')]
    public function show(int $id, EvenementRepository $evenementRepository): Response
    {
        $evenement = $evenementRepository->find($id);
        
        if (!$evenement) {
            throw $this->createNotFoundException('Event not found');
        }
    
        // Add validation check
        if (!$evenement->isValidated()) {
            $this->addFlash('error', 'This event is not yet approved');
            return $this->redirectToRoute('app_evenement_index');
        }
        
        return $this->render('gestion_de_reservation/evenement/show.html.twig', [
            'evenement' => $evenement,
        ]);
    }
    
    // In your event selection/booking action in EvenementController
    public function bookEvent(Evenement $evenement): Response
    {
        // Debug parameters before redirecting
        $parameters = [
            'lieuEvenement' => $evenement->getLieuEvenement(),
            'date_debut' => $evenement->getDateDebut()->format('Y-m-d'),
            'date_fin' => $evenement->getDateFin()->format('Y-m-d'),
            'nombre_invite' => $evenement->getNombreInvite(),
            'userid' => $this->getUser()->getId(),
            'id_evenement' => $evenement->getId()
        ];
        
        $this->addFlash('debug', 'Redirecting with parameters: ' . print_r($parameters, true));

        return $this->redirectToRoute('app_flights', $parameters);
    }
    
    #[Route('/event/{id}/ai-booking', name: 'app_evenement_ai_booking')]
    public function aiBooking(
        int $id,
        EvenementRepository $eventRepository,
        GeminiAIService $aiService,
        FlightRepository $flightRepo,
        HotelRepository $hotelRepo,
        TransportRepository $transportRepo,
        ConferenceLocationRepository $conferenceRepo
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');

        $event = $eventRepository->find($id);
        
        if (!$event) {
            $this->addFlash('error', 'Event not found.');
            return $this->redirectToRoute('app_evenement_index');
        }

        try {
            $recommendations = $aiService->getBookingRecommendations([
                'id' => $event->getId(),
                'type' => $event->getType(),
                'city' => $event->getLieuEvenement(),
                'start_date' => $event->getDateDebut()->format('Y-m-d'),
                'end_date' => $event->getDateFin()->format('Y-m-d'),
                'guests' => $event->getNombreInvite(),
                'budget' => $event->getBudgetPrevu()
            ]);

            // Validate recommendations structure
            if (!isset($recommendations['flight_id']) || 
                !isset($recommendations['hotel_id']) || 
                !isset($recommendations['transport_id']) || 
                !isset($recommendations['conference_location_id'])) {
                throw new \Exception('Invalid AI recommendations format');
            }

            // Fetch and validate recommended entities
            $flight = $flightRepo->find($recommendations['flight_id']);
            $hotel = $hotelRepo->find($recommendations['hotel_id']);
            $transport = $transportRepo->find($recommendations['transport_id']);
            $conference = $conferenceRepo->find($recommendations['conference_location_id']);

            if (!$flight || !$hotel || !$transport || !$conference) {
                throw new \Exception('One or more recommended services not found');
            }

            return $this->render('gestion_de_reservation/evenement/ai_booking_confirm.html.twig', [
                'event' => $event,
                'recommendations' => $recommendations,
                'flight' => $flight,
                'hotel' => $hotel,
                'transport' => $transport,
                'conference' => $conference,
                'submit_url' => $this->generateUrl('app_evenement_booking_create', ['id' => $event->getId()])
            ]);

        } catch (\Exception $e) {
            $this->addFlash('error', 'AI booking service error: ' . $e->getMessage());
            return $this->redirectToRoute('app_evenement_index');
        }
    }

    #[Route('/event/{id}/booking/create', name: 'app_evenement_booking_create', methods: ['POST'])]
    // In createBooking method:
    public function createBooking(
        int $id,
        Request $request,
        EvenementRepository $eventRepository,
        EntityManagerInterface $entityManager,
        FlightRepository $flightRepo,
        HotelRepository $hotelRepo,
        TransportRepository $transportRepo,
        ConferenceLocationRepository $conferenceRepo
    ): Response {
        $event = $eventRepository->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
    
        // Validate special requests
        $specialRequests = $request->request->get('specialRequests');
        if (strlen(trim($specialRequests)) < 10) {
            $this->addFlash('error', 'Special requests must contain at least 10 characters');
            return $this->redirectToRoute('app_evenement_ai_booking', ['id' => $id]);
        }
    
        // Create new booking
        $booking = new Booking();
        $booking->setEvenement($event);
        $booking->setUser($this->getUser());
        $booking->setBookingDate(new \DateTime());
        $booking->setStatus('pending');
        
        // Remove the service inclusion setters
        $services = $request->request->all('services');
        
        // Set service IDs directly based on selection
        $booking->setFlight(in_array('flight', $services) ? $flightRepo->find($request->request->get('flight_id')) : null);
        $booking->setHotel(in_array('hotel', $services) ? $hotelRepo->find($request->request->get('hotel_id')) : null);
        $booking->setTransport(in_array('transport', $services) ? $transportRepo->find($request->request->get('transport_id')) : null);
        $booking->setConferenceLocation(in_array('conference', $services) ? $conferenceRepo->find($request->request->get('conference_id')) : null);

        // Set price total from request
        $booking->setPriceTotal($request->request->get('total_price'));
        $booking->setSpecialRequests($request->request->get('specialRequests'));

        // Set service relationships and copy data
        if ($flight = $flightRepo->find($request->request->get('flight_id'))) {
            $booking->setFlight($flight);
            $booking->setAirlines($flight->getAirline());
            
            // Add date adjustment logic
            $event = $booking->getEvenement();
            $destination = $flight->getDestination();
            $dateDebut = $event->getDateDebut();
            $dateFin = $event->getDateFin();
    
            if (in_array($destination, ['Paris', 'Dubai', 'London', 'Istanbul', 'Riyadh', 'Madrid'])) {
                $departureDate = (clone $dateDebut)->modify('-1 day');
                $returnDate = (clone $dateFin)->modify('+1 day');
            } elseif (in_array($destination, ['New York', 'Tokyo', 'Beijing', 'Sydney', 'Ottawa'])) {
                $departureDate = (clone $dateDebut)->modify('-2 days');
                $returnDate = (clone $dateFin)->modify('+1 day');
            }
    
            // Set adjusted datetime values
            $booking->setDepartureTime(new \DateTime(
                $departureDate->format('Y-m-d') . ' ' . 
                $flight->getDepartureTime()->format('H:i:s')
            ));
            
            $booking->setBackTime(new \DateTime(
                $returnDate->format('Y-m-d') . ' ' . 
                $flight->getBackTime()->format('H:i:s')
            ));
            
            $booking->setFlightPrice($flight->getPrice());
        }

        if ($hotel = $hotelRepo->find($request->request->get('hotel_id'))) {
            $booking->setHotel($hotel);
            $booking->setHotelName($hotel->getName());
            $booking->setHotelLocation($hotel->getLocation());
            $booking->setHotelPricePerNight($hotel->getPricePerNight());
            $booking->setHotelRating($hotel->getRating());
        }

        if ($transport = $transportRepo->find($request->request->get('transport_id'))) {
            $booking->setTransport($transport);
            $booking->setTransportType($transport->getType());
            $booking->setTransportPrice($transport->getPrice());
            $booking->setTransportDescription($transport->getDescription());
        }

        if ($conference = $conferenceRepo->find($request->request->get('conference_id'))) {
            $booking->setConferenceLocation($conference);
            $booking->setConferenceName($conference->getName());
            $booking->setConferencePricePerDay($conference->getPricePerDay());
        }

        // Set user details
        $user = $this->getUser();
        $booking->setUserName($user->getEmail());  // Changed from getUsername() to getNom()
        $booking->setUserEmail($user->getEmail());

        // Set event details
        $booking->setNameEvement($event->getNom());
        $booking->setNumberofInvites($event->getNombreInvite());
        $booking->setStartEvement($event->getDateDebut());
        $booking->setEndEvement($event->getDateFin());

        // Save to database
        $entityManager->persist($booking);
        $entityManager->flush();
    
        $this->addFlash('success', 'Booking created successfully!');
        return $this->redirectToRoute('app_booking_list');
    }
    
    #[Route('/bookings', name: 'app_booking_list')]
    public function listBookings(BookingRepository $bookingRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');
        
        $user = $this->getUser();
        $allBookings = $bookingRepository->findBy(['user' => $user]);
        
        $pendingBookings = array_filter($allBookings, function($booking) {
            return $booking->getStatus() === 'pending';
        });
        
        $confirmedBookings = array_filter($allBookings, function($booking) {
            return $booking->getStatus() === 'confirmed';
        });

        return $this->render('gestion_de_reservation/booking/list.html.twig', [
            'pendingBookings' => $pendingBookings,
            'confirmedBookings' => $confirmedBookings
        ]);
    }
}