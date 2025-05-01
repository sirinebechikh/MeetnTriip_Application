<?php

namespace App\Controller\gestion_de_reservation;

// Add these at the top with other imports
use Symfony\Component\Mime\Email;
use Symfony\Mailer\Exception\TransportExceptionInterface;

use App\Entity\gestion_de_reservation\Booking;
use App\Repository\gestion_de_reservation\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


#[Route('/admin/bookings')]
class BookingAdminController extends AbstractController
{
    #[Route('/', name: 'admin_bookings_list')]
    public function list(BookingRepository $bookingRepository, Request $request): Response
    {
        $searchTerm = $request->query->get('search', '');
        
        if (!empty($searchTerm)) {
            // Search by event name
            $bookings = $bookingRepository->createQueryBuilder('b')
                ->leftJoin('b.evenement', 'e')
                ->where('LOWER(e.nom) LIKE LOWER(:searchTerm)')
                ->setParameter('searchTerm', '%' . $searchTerm . '%')
                ->getQuery()
                ->getResult();
        } else {
            $bookings = $bookingRepository->findAll();
        }

        // Ensure Doctrine loads the related event data
        foreach ($bookings as $booking) {
            if ($booking->getEvenement()) {
                $booking->getEvenement()->getNom();
            }
        }

        return $this->render('gestion_de_reservation/booking_admin/list.html.twig', [
            'bookings' => $bookings,
            'searchTerm' => $searchTerm
        ]);
    }

    #[Route('/{id}', name: 'admin_booking_show')]
    public function show(int $id, BookingRepository $bookingRepository): Response
    {
        $booking = $bookingRepository->find($id);
        
        if (!$booking) {
            throw new NotFoundHttpException('Booking not found');
        }

        return $this->render('gestion_de_reservation/booking_admin/show.html.twig', [
            'booking' => $booking,
        ]);
    }

    #[Route('/{id}/update-status', name: 'admin_booking_update_status', methods: ['POST'])]
    public function updateStatus(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        BookingRepository $bookingRepository,
        MailerInterface $mailer
    ): Response {
        $booking = $bookingRepository->find($id);
        
        if (!$booking) {
            throw new NotFoundHttpException('Booking not found');
        }

        $status = $request->request->get('status');
        
        if ($status === 'confirmed' || $status === 'not_confirmed') {
            $booking->setStatus($status);
            $entityManager->flush();
        
            if ($booking->getUserName()) {
                $emailType = $status === 'confirmed' ? 'confirmation' : 'rejection';
                $template = 'emails/booking_' . $emailType . '.html.twig';
                
                $email = (new Email())
                    ->from('MeetNtrip <borgimoatez@gmail.com>')
                    ->to($booking->getUserName())
                    ->subject('Booking ' . ucfirst($emailType) . ' #' . $booking->getId())
                    ->html($this->renderView($template, ['booking' => $booking]));
        
                try {
                    $mailer->send($email);
                    $this->addFlash('success', "Booking status updated and {$emailType} email sent.");
                } catch (TransportExceptionInterface $e) {
                    $this->addFlash('warning', "Booking {$emailType} failed to send: " . $e->getMessage());
                }
            } else {
                $this->addFlash('warning', 'Booking status updated but no user email found');
            }
        } else {
            $this->addFlash('error', 'Invalid status provided.');
        }

        return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
    }

    #[Route('/{id}/validate', name: 'admin_booking_validate', methods: ['POST'])]
    public function validate(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        LoggerInterface $logger,
        BookingRepository $bookingRepository
    ): Response {
        $booking = $bookingRepository->find($id);
        
        if (!$booking) {
            throw new NotFoundHttpException('Booking not found');
        }

        $action = $request->request->get('action');

        if ($action === 'confirm') {
            $booking->setStatus('confirmed');
            $emailStatus = $this->sendConfirmationEmail($booking, $mailer, $logger);
            $this->addFlash('success', 'Booking confirmed' . ($emailStatus ? '' : ' (email failed)'));
        } elseif ($action === 'reject') {
            $booking->setStatus('not_confirmed');
            $emailStatus = $this->sendRejectionEmail($booking, $mailer, $logger);
            $this->addFlash('warning', 'Booking rejected' . ($emailStatus ? '' : ' (email failed)'));
        }

        $em->flush();

        return $this->redirectToRoute('admin_booking_show', ['id' => $booking->getId()]);
    }

    private function sendConfirmationEmail(Booking $booking, MailerInterface $mailer, LoggerInterface $logger): bool
    {
        // Implementation assumed to exist
        return true;
    }

    private function sendRejectionEmail(Booking $booking, MailerInterface $mailer, LoggerInterface $logger): bool
    {
        // Implementation assumed to exist
        return true;
    }
}