<?php

namespace App\Controller\gestion_de_reservation;

use App\Entity\gestion_de_reservation\{Booking, Flight, Hotel, Transport, ConferenceLocation};
use App\Entity\Gestion_Evenement\Evenement;
use App\Entity\gestion_user\User;
use App\Repository\gestion_de_reservation\{FlightRepository, HotelRepository, TransportRepository, 
                 ConferenceLocationRepository,    BookingRepository};
use App\Repository\gestion_user\UserRepository;
 use App\Repository\Gestion_evenement\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use TCPDF;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\BinaryWriter;
use Endroid\QrCode\Writer\SvgWriter;




class BookingController extends AbstractController
{
    #[Route('/booking', name: 'app_booking')]
    public function index(
        Request $request,
        FlightRepository $flightRepository,
        HotelRepository $hotelRepository,
        TransportRepository $transportRepository,
        ConferenceLocationRepository $conferenceLocationRepository,
        EvenementRepository $evenementRepository,
        UserRepository $userRepository
    ): Response {
        // Get all query parameters
        $flightId = $request->query->get('flight_id');
        $flightPrice = $request->query->get('flight_price');
        $departureTime = $request->query->get('departure_time');
        $backTime = $request->query->get('back_time');
        $hotelId = $request->query->get('hotel_id');
        $hotelPrice = $request->query->get('hotel_price');
        $locationId = $request->query->get('location_id');
        $conferencePrice = $request->query->get('conference_price');
        $transportId = $request->query->get('transport_id');
        $transportPrice = $request->query->get('transport_price');
        $userId = $request->query->get('userid');
        $idEvenement = $request->query->get('id_evenement');
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');
        $nombreInvite = $request->query->get('nombre_invite');

        // Fetch related entities
        $flight = $flightId ? $flightRepository->find($flightId) : null;
        $hotel = $hotelId ? $hotelRepository->find($hotelId) : null;
        $transport = $transportId ? $transportRepository->find($transportId) : null;
        $location = $locationId ? $conferenceLocationRepository->find($locationId) : null;
        $evenement = $idEvenement ? $evenementRepository->find($idEvenement) : null;
        $user = $userId ? $userRepository->find($userId) : null;

        // Calculate total price
        $priceTotal = 0;
        if ($flightPrice) $priceTotal += floatval($flightPrice);
        if ($hotelPrice) $priceTotal += floatval($hotelPrice);
        if ($conferencePrice) $priceTotal += floatval($conferencePrice);
        if ($transportPrice) $priceTotal += floatval($transportPrice);

        return $this->render('gestion_de_reservation/booking/index.html.twig', [
            'flight' => $flight,
            'hotel' => $hotel,
            'transport' => $transport,
            'location' => $location,
            'evenement' => $evenement,
            'user' => $user,
            'flight_id' => $flightId,
            'flight_price' => $flightPrice,
            'departure_time' => $departureTime,
            'back_time' => $backTime,
            'hotel_id' => $hotelId,
            'hotel_price' => $hotelPrice,
            'location_id' => $locationId,
            'conference_price' => $conferencePrice,
            'transport_id' => $transportId,
            'transport_price' => $transportPrice,
            'userid' => $userId,
            'id_evenement' => $idEvenement,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'nombre_invite' => $nombreInvite,
            'price_total' => $priceTotal,
        ]);
    }

    #[Route('/booking/save', name: 'app_booking_save', methods: ['POST'])]
    public function save(Request $request, EntityManagerInterface $entityManager,
        FlightRepository $flightRepository,
        HotelRepository $hotelRepository,
        TransportRepository $transportRepository,
        ConferenceLocationRepository $conferenceLocationRepository,
        EvenementRepository $evenementRepository,
        UserRepository $userRepository
    ): Response {
        $booking = new Booking();
        
        // Set basic booking info
        $booking->setBookingDate(new \DateTime());
        $booking->setStatus('pending');
        $booking->setSpecialRequests($request->request->get('special_requests'));

        // Event data
        if ($eventId = $request->request->get('id_evenement')) {
            $event = $evenementRepository->find($eventId);
            if ($event) {
                // Set event relationship and data
                $booking->setEvenement($event);                    // This already handles the event ID
                $booking->setNameEvement($event->getNom());
                $booking->setStartEvement($event->getDateDebut());
                $booking->setEndEvement($event->getDateFin());
                $booking->setNumberOfInvites($event->getNombreInvite());
                
                // Debug information
                $this->addFlash('debug', sprintf(
                    'Saving event data - ID: %d, Name: %s, Start: %s, End: %s, Invites: %d',
                    $eventId,
                    $event->getNom(),
                    $event->getDateDebut()->format('Y-m-d H:i:s'),
                    $event->getDateFin()->format('Y-m-d H:i:s'),
                    $event->getNombreInvite()
                ));
            }
        }

        // Flight data
        // In the save method's flight handling section:
        if ($flightId = $request->request->get('flight_id')) {
            $flight = $flightRepository->find($flightId);
            if ($flight) {
                $booking->setFlight($flight);
                $booking->setAirlines($flight->getAirline());
                
                // Get event dates and flight destination
                $event = $booking->getEvenement();
                $destination = $flight->getDestination();
                $dateDebut = $event->getDateDebut();
                $dateFin = $event->getDateFin();
                
                // Calculate date adjustments based on destination
                if (in_array($destination, ['Paris', 'Dubai', 'London', 'Istanbul', 'Riyadh', 'Madrid'])) {
                    $departureDate = (clone $dateDebut)->modify('-1 day');
                    $returnDate = (clone $dateFin)->modify('+1 day');
                } elseif (in_array($destination, ['New York', 'Tokyo', 'Beijing', 'Sydney', 'Ottawa'])) {
                    $departureDate = (clone $dateDebut)->modify('-2 days');
                    $returnDate = (clone $dateFin)->modify('+1 day');
                }
                
                // Combine dates with flight times
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
        }

        // Hotel data
        if ($hotelId = $request->request->get('hotel_id')) {
            $hotel = $hotelRepository->find($hotelId);
            if ($hotel) {
                $booking->setHotel($hotel);
                $booking->setHotelName($hotel->getName());
                $booking->setHotelLocation($hotel->getCity());
                $booking->setHotelPricePerNight($hotel->getPricePerNight());
                $booking->setHotelRating($hotel->getRating());
            }
        }

        // Conference Location data
        if ($locationId = $request->request->get('location_id')) {
            $location = $conferenceLocationRepository->find($locationId);
            if ($location) {
                $booking->setConferenceLocation($location);
                $booking->setConferenceName($location->getName());
                $booking->setConferencePricePerDay($location->getPricePerDay());
            }
        }

        // Transport data
        if ($transportId = $request->request->get('transport_id')) {
            $transport = $transportRepository->find($transportId);
            if ($transport) {
                $booking->setTransport($transport);
                $booking->setTransportType($transport->getType());
                $booking->setTransportPrice($transport->getPrice());
                $booking->setTransportDescription($transport->getDescription());
            }
        }

        // User data
        if ($userId = $request->request->get('userid')) {
            $user = $userRepository->find($userId);
            if ($user) {
                $booking->setUser($user);
                $booking->setUserName($user->getEmail() ?? $user->getFullName() ?? 'User #' . $userId);
            }
        }

        // Calculate total price
        $totalPrice = 0;
        if ($booking->getFlightPrice()) $totalPrice += $booking->getFlightPrice();
        if ($booking->getHotelPricePerNight()) $totalPrice += $booking->getHotelPricePerNight();
        if ($booking->getConferencePricePerDay()) $totalPrice += $booking->getConferencePricePerDay();
        if ($booking->getTransportPrice()) $totalPrice += $booking->getTransportPrice();
        
        $booking->setPriceTotal((float)$totalPrice);

        // Save to database
        $entityManager->persist($booking);
        $entityManager->flush();

        $this->addFlash('success', 'Booking created successfully!');
        return $this->redirectToRoute('app_bookings_list');
    }

    #[Route('/bookings', name: 'app_bookings_list')]
    public function list(BookingRepository $bookingRepository): Response
    {
        $user = $this->getUser();
        
        if ($user && $user->getStatus() === 'islogedclient') {
            $pendingBookings = $bookingRepository->findBy([
                'user' => $user,
                'status' => 'pending'
            ]);
            
            $confirmedBookings = $bookingRepository->findBy([
                'user' => $user,
                'status' => 'confirmed'
            ]);
            
            // Add not_confirmed bookings
            $notConfirmedBookings = $bookingRepository->findBy([
                'user' => $user,
                'status' => 'not_confirmed'
            ]);
        } else {
            $pendingBookings = [];
            $confirmedBookings = [];
            $notConfirmedBookings = [];
        }

        return $this->render('gestion_de_reservation/booking/list.html.twig', [
            'pendingBookings' => $pendingBookings,
            'confirmedBookings' => $confirmedBookings,
            'notConfirmedBookings' => $notConfirmedBookings
        ]);
    }

    #[Route('/booking/update/{id}', name: 'app_booking_update', methods: ['GET', 'POST'])]
    public function update(
        Request $request,
        int $id,
        BookingRepository $bookingRepository,
        EntityManagerInterface $entityManager,
        FlightRepository $flightRepository,
        HotelRepository $hotelRepository,
        TransportRepository $transportRepository,
        ConferenceLocationRepository $conferenceLocationRepository
    ): Response {
        $booking = $bookingRepository->find($id);
        
        if (!$booking) {
            throw $this->createNotFoundException('Booking not found');
        }

        // Get the event's location (lieuEvenement)
        $eventLocation = $booking->getEvenement()->getLieuEvenement();
        
        // Filter entities based on location
        $flights = $flightRepository->findBy(['destination' => $eventLocation]);
        $hotels = $hotelRepository->findBy(['city' => $eventLocation]);
        $conferenceLocations = $conferenceLocationRepository->findBy(['city' => $eventLocation]);
        $transports = $transportRepository->findAll(); // Keep all transports available

        if ($request->isMethod('POST')) {
            // Update booking info
            $booking->setSpecialRequests($request->request->get('special_requests'));
            
            // Flight details
            $flightId = $request->request->get('flight_id');
            if ($flightId) {
                $flight = $flightRepository->find($flightId);
                if ($flight) {
                    $booking->setFlight($flight);
                    $booking->setAirlines($flight->getAirline());
                    $booking->setDepartureTime($flight->getDepartureTime());
                    $booking->setBackTime($flight->getBackTime());
                }
            }

            // Hotel details
            $hotelId = $request->request->get('hotel_id');
            if ($hotelId) {
                $hotel = $hotelRepository->find($hotelId);
                if ($hotel) {
                    $booking->setHotel($hotel);
                    $booking->setHotelName($hotel->getName());
                    $booking->setHotelLocation($hotel->getCity());
                }
            }

            // Transport details
            $transportId = $request->request->get('transport_id');
            if ($transportId = $request->request->get('transport_id')) {
                $transport = $transportRepository->find($transportId);
                $booking->setTransport($transport);
                return $this->redirectToRoute('app_booking_update_select', [
                    'id' => $booking->getBookingId(),
                    'type' => 'special_requests'
                ]);
            }

            // Conference location details
            $locationId = $request->request->get('location_id');
            if ($locationId) {
                $conferenceLocation = $conferenceLocationRepository->find($locationId);
                if ($conferenceLocation) {
                    $booking->setConferenceLocation($conferenceLocation);
                    $booking->setConferenceName($conferenceLocation->getName());
                }
            }

            // Recalculate total price
            $totalPrice = 0;
            $flightPrice = $request->request->get('flight_price');
            $hotelPrice = $request->request->get('hotel_price');
            $conferencePrice = $request->request->get('conference_price');
            $transportPrice = $request->request->get('transport_price');

            if ($flightPrice) $totalPrice += floatval($flightPrice);
            if ($hotelPrice) $totalPrice += floatval($hotelPrice);
            if ($conferencePrice) $totalPrice += floatval($conferencePrice);
            if ($transportPrice) $totalPrice += floatval($transportPrice);

            $booking->setPriceTotal((float)$totalPrice);

            // Save changes
            $entityManager->flush();
            $this->addFlash('success', 'Booking updated successfully!');
            return $this->redirectToRoute('app_bookings_list');
        }

        return $this->render('gestion_de_reservation/booking/update.html.twig', [
            'booking' => $booking,
            'flights' => $flights,
            'hotels' => $hotels,
            'transports' => $transports,
            'conferenceLocations' => $conferenceLocations,
            'eventLocation' => $eventLocation,
        ]);
    }

    #[Route('/booking/update/{id}/process', name: 'app_booking_update_process', methods: ['POST'])]
    public function updateProcess(
        Request $request,
        int $id,
        BookingRepository $bookingRepository,
        EntityManagerInterface $entityManager,
        FlightRepository $flightRepository,
        HotelRepository $hotelRepository,
        TransportRepository $transportRepository,
        ConferenceLocationRepository $conferenceLocationRepository
    ): Response {
        $booking = $bookingRepository->find($id);
        
        if (!$booking) {
            throw $this->createNotFoundException('Booking not found');
        }

        // Process flight selection
        if ($flightId = $request->request->get('flight_id')) {
            $flight = $flightRepository->find($flightId);
            if ($flight) {
                $booking->setFlight($flight);
                $booking->setAirlines($flight->getAirline());
                
                // Add date adjustment logic here
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
                // End of date adjustment logic

                $entityManager->flush();
                
                return $this->redirectToRoute('app_booking_update_select', [
                    'id' => $booking->getBookingId(),
                    'type' => 'hotel'
                ]);
            }
        }

        // Process hotel selection
        if ($hotelId = $request->request->get('hotel_id')) {
            $hotel = $hotelRepository->find($hotelId);
            if ($hotel) {
                $booking->setHotel($hotel);
                $booking->setHotelName($hotel->getName());
                $booking->setHotelLocation($hotel->getCity());
                $entityManager->flush();
                
                return $this->redirectToRoute('app_booking_update_select', [
                    'id' => $booking->getBookingId(),
                    'type' => 'conference'
                ]);
            }
        }

        // Process conference location selection
        if ($locationId = $request->request->get('location_id')) {
            $location = $conferenceLocationRepository->find($locationId);
            if ($location) {
                $booking->setConferenceLocation($location);
                $booking->setConferenceName($location->getName());
                $entityManager->flush();
                
                return $this->redirectToRoute('app_booking_update_select', [
                    'id' => $booking->getBookingId(),
                    'type' => 'transport'
                ]);
            }
        }

        // Process transport selection
        if ($transportId = $request->request->get('transport_id')) {
            $transport = $transportRepository->find($transportId);
            if ($transport) {
                $booking->setTransport($transport);
                $booking->setTransportType($transport->getType());
                $booking->setTransportDescription($transport->getDescription());
                
                // Calculate total price
                $totalPrice = 0;
                if ($booking->getFlight()) $totalPrice += $booking->getFlight()->getPrice();
                if ($booking->getHotel()) $totalPrice += $booking->getHotel()->getPricePerNight();
                if ($booking->getConferenceLocation()) $totalPrice += $booking->getConferenceLocation()->getPricePerDay();
                if ($transport) $totalPrice += $transport->getPrice();
                
                $booking->setPriceTotal($totalPrice);
                $entityManager->flush();
                
                return $this->redirectToRoute('app_booking_review', [
                    'id' => $booking->getBookingId()
                ]);
            }
        }
        
        return $this->redirectToRoute('app_bookings_list');
    }

    #[Route('/booking/update/{id}/final', name: 'app_booking_update_final', methods: ['POST'])]
    public function updateFinal(
        Request $request,
        int $id,
        BookingRepository $bookingRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $booking = $bookingRepository->find($id);
        
        if (!$booking) {
            throw $this->createNotFoundException('Booking not found');
        }

        // Add validation before saving
        $specialRequests = $request->request->get('special_requests');
        $termsAccepted = $request->request->has('terms');

        // Validate special requests
        if (empty($specialRequests) || strlen(trim($specialRequests)) < 10) {
            $this->addFlash('error', 'Special requests must contain at least 10 characters');
            return $this->redirectToRoute('app_booking_review', ['id' => $id]);
        }

        // Validate terms checkbox
        if (!$termsAccepted) {
            $this->addFlash('error', 'You must agree to the terms and conditions');
            return $this->redirectToRoute('app_booking_review', ['id' => $id]);
        }

        // Update the booking with final changes
        $booking->setSpecialRequests($specialRequests);
        $booking->setStatus('pending');  // Add status update
        
        $entityManager->flush();
        $this->addFlash('success', 'Booking updated successfully!');
        return $this->redirectToRoute('app_bookings_list');
    }

    #[Route('/booking/{id}', name: 'app_booking_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, int $id, BookingRepository $bookingRepository, EntityManagerInterface $entityManager): Response
    {
        $booking = $bookingRepository->find($id);
        
        if (!$booking) {
            $this->addFlash('error', 'Booking not found');
            return $this->redirectToRoute('app_bookings_list');
        }
        
        if ($this->isCsrfTokenValid('delete'.$booking->getBookingId(), $request->request->get('_token'))) {
            $entityManager->remove($booking);
            $entityManager->flush();
            $this->addFlash('success', 'Booking deleted successfully');
        } else {
            $this->addFlash('error', 'Invalid CSRF token');
        }

        return $this->redirectToRoute('app_bookings_list');
    }

    #[Route('/booking/update/{id}/select/{type}', name: 'app_booking_update_select')]
    public function updateSelect(
        Request $request,
        int $id, 
        string $type,
        EntityManagerInterface $entityManager,
        FlightRepository $flightRepository,
        HotelRepository $hotelRepository,
        TransportRepository $transportRepository,
        ConferenceLocationRepository $conferenceLocationRepository
    ): Response {
        $booking = $entityManager->getRepository(Booking::class)->find($id);

        if (!$booking) {
            throw $this->createNotFoundException('Booking not found');
        }

        $eventLocation = $booking->getEvenement()->getLieuEvenement();

        // Prepare data based on type
        switch ($type) {
            case 'flight':
                $flights = $flightRepository->findBy(['destination' => $eventLocation]);
                // Load airline logos from JSON
                $projectDir = $this->getParameter('kernel.project_dir');
                $airlineLogos = json_decode(
                    file_get_contents($projectDir.'/assets/mydummydata/images.json'), 
                    true
                )['flights'] ?? [];
                
                return $this->render('gestion_de_reservation/booking/update_flight.html.twig', [
                    'booking' => $booking,
                    'flights' => $flights,
                    'eventLocation' => $eventLocation,
                    'airlineLogos' => $airlineLogos // Add this line
                ]);

            case 'hotel':
                $hotels = $hotelRepository->findBy(['city' => $eventLocation]);
                $projectDir = $this->getParameter('kernel.project_dir');
                $hotelImages = json_decode(
                    file_get_contents($projectDir.'/assets/mydummydata/images.json'), 
                    true
                )['hotels'] ?? [];
                
                return $this->render('gestion_de_reservation/booking/update_hotel.html.twig', [
                    'booking' => $booking,
                    'hotels' => $hotels,
                    'eventLocation' => $eventLocation,
                    'hotelImages' => $hotelImages
                ]);

            // In the updateSelect method's 'conference' case:
            case 'conference':
                $locations = $conferenceLocationRepository->findBy(['city' => $eventLocation]);
                $projectDir = $this->getParameter('kernel.project_dir');
                $conferenceImages = json_decode(
                    file_get_contents($projectDir.'/assets/mydummydata/images.json'), 
                    true
                )['conference_locations'] ?? [];
                
                return $this->render('gestion_de_reservation/booking/update_conference.html.twig', [
                    'booking' => $booking,
                    'locations' => $locations,
                    'eventLocation' => $eventLocation,
                    'conferenceImages' => $conferenceImages
                ]);

            case 'transport':
                // Load transport images from JSON
                $projectDir = $this->getParameter('kernel.project_dir');
                $transportImages = json_decode(
                    file_get_contents($projectDir.'/assets/mydummydata/images.json'), 
                    true
                )['transport'] ?? [];
                
                // Add this line to fetch transports
                $transports = $transportRepository->findAll();

                return $this->render('gestion_de_reservation/booking/update_transport.html.twig', [
                    'booking' => $booking,
                    'transports' => $transports,
                    'transportImages' => $transportImages,
                    'eventLocation' => $eventLocation
                ]);

            case 'review':
                return $this->render('gestion_de_reservation/booking/review.html.twig', [
                    'booking' => $booking
                ]);

            default:
                throw $this->createNotFoundException('Invalid selection type');
        }
    }

    #[Route('/booking/{id}/review', name: 'app_booking_review')]
    public function review(
        int $id,
        BookingRepository $bookingRepository
    ): Response {
        $booking = $bookingRepository->find($id);
        
        if (!$booking) {
            throw $this->createNotFoundException('Booking not found');
        }

        return $this->render('gestion_de_reservation/booking/review.html.twig', [
            'booking' => $booking
        ]);
    }

    #[Route('/booking/pdf/{id}', name: 'app_booking_pdf')]
    public function generatePdf(int $id, BookingRepository $bookingRepository): Response
    {
        $booking = $bookingRepository->find($id);
        
        if (!$booking) {
            throw $this->createNotFoundException('Booking not found');
        }
    
        // Configure PDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('MeetNtrip');
        $pdf->SetTitle('Booking Details #' . $booking->getId());
        $pdf->AddPage();
    
        // Generate HTML content
        $html = $this->renderView('gestion_de_reservation/booking/pdf_template.html.twig', [
            'booking' => $booking
        ]);
    
        // Add content to PDF
        $pdf->writeHTML($html);
        
        // Output PDF
        return new Response($pdf->Output('booking_details.pdf', 'I'), 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }
    
    // Add this with other use statements at the top of the file


    // Add this new route
    #[Route('/booking/qrcode/{id}', name: 'app_booking_qrcode')]
    public function generateQrCode(int $id, BookingRepository $bookingRepository): Response
    {
        $booking = $bookingRepository->find($id);
        
        if (!$booking) {
            throw $this->createNotFoundException('Booking not found');
        }
    
        // Create QR content with booking details
        $qrContent = json_encode([
            'Booking ID' => $booking->getId(),
            'Event' => $booking->getEvenement()?->getNom() ?? 'N/A',
            'Date' => $booking->getBookingDate()->format('Y-m-d H:i'),
            'User' => $booking->getUserName(),
            'Total Price' => '$'.$booking->getPriceTotal(),
            'Status' => ucfirst($booking->getStatus())
        ], JSON_PRETTY_PRINT);
    
        // Generate QR code
        // Add this use statement at the top with other use statements
 
        
        // In the generateQrCode method
        $qrCode = Builder::create()
        ->writer(new SvgWriter())  // Changed from PngWriter to SvgWriter
        ->data($qrContent)
        ->encoding(new Encoding('UTF-8'))
        ->errorCorrectionLevel(ErrorCorrectionLevel::High)
        ->size(400)
        ->margin(20)
        ->build();
        
        return new Response($qrCode->getString(), 200, [
            'Content-Type' => 'image/svg+xml',  // Changed content type
            'Content-Disposition' => 'inline; filename="booking_qrcode.svg"'  // Changed extension
        ]);
    }
}
