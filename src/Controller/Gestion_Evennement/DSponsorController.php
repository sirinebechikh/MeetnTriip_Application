<?php

namespace App\Controller\Gestion_Evennement;

use App\Entity\Gestion_Evenement\DemandeSponsoring;
use App\Entity\Gestion_Evenement\Evenement;
use App\Entity\gestion_user\User;
use App\Form\DemandeSponsoringType;
use App\Repository\Gestion_evenement\DemandeSponsoringRepository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
 

class DSponsorController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/d_sponsor/{id}', name: 'app_d_sponsor', methods: ['GET', 'POST'])]
    public function createSponsorshipRequest(int $id, Request $request): Response
    {
        
        $evenement = $this->entityManager->getRepository(Evenement::class)->find($id);

        if (!$evenement) {
            throw $this->createNotFoundException('The requested event does not exist.');
        }

        $existingRequest = $this->entityManager->getRepository(DemandeSponsoring::class)
            ->findOneBy(['evenement' => $evenement]);

        if ($existingRequest) {
            $this->addFlash('error', 'A sponsorship request already exists for this event.');
            return $this->redirectToRoute('client_evenements');
        }

        $sponsors = $this->entityManager->getRepository(User::class)->findBy(['role' => 'SPONSOR']);

        $demandeSponsoring = new DemandeSponsoring();
        $demandeSponsoring->setEvenement($evenement);
        $demandeSponsoring->setStatut('pending');

        $form = $this->createForm(DemandeSponsoringType::class, $demandeSponsoring, [
            'sponsors' => $sponsors,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $selectedSponsor = $form->get('sponsor')->getData();
                $demandeSponsoring->setSponsor($selectedSponsor);

                $this->entityManager->persist($demandeSponsoring);
                $this->entityManager->flush();

                $this->addFlash('success', 'Your sponsorship request has been successfully sent.');
                return $this->redirectToRoute('client_evenements');
            } else {
                $errors = (string) $form->getErrors(true, true);
                $this->addFlash('error', 'Error sending request: ' . $errors);
            }
        }

        return $this->render('Gestion_Evennement/d_sponsor/demande_sponsoring_client.html.twig', [
            'evenement' => $evenement,
            'form' => $form->createView(),
            'sponsors' => $sponsors,
        ]);
    }

    #[Route('/sponsor/demandes', name: 'sponsor_demandes')]
    public function index(DemandeSponsoringRepository $repository): Response
    { 
        $this->denyAccessUnlessGranted('ROLE_SPONSOR');

        if (!$this->isGranted('ROLE_SPONSOR')) {
        return $this->redirectToRoute('app_home');
    }
    // Utiliser l'utilisateur connecté
    $user = $this->getUser(); // L'utilisateur connecté

    $demandes = $this->entityManager->getRepository(DemandeSponsoring::class)->findBy([
        'sponsor' => $user,
    ]);
    
    return $this->render('Gestion_Evennement/d_sponsor/decision_sponsoring_sponsor.html.twig', [
        'demandes' => $demandes,
    ]);
    
    }
    #[Route('/sponsor/demande/{id}/accepter', name: 'demande_sponsoring_accepter', methods: ['POST'])]
    public function accept(int $id): Response
    {
        $demande = $this->entityManager->getRepository(DemandeSponsoring::class)->find($id);
    
        if (!$demande) {
            $this->addFlash('error', 'La demande de sponsoring n\'a pas été trouvée.');
            return $this->redirectToRoute('sponsor_demandes');
        }
    
        $demande->setStatut('Accepted');
        $this->entityManager->flush();
    
        return $this->redirectToRoute('sponsor_demandes');
    }
    
    #[Route('/sponsor/demande/{id}/refuser', name: 'demande_sponsoring_refuser', methods: ['POST'])]
    public function refuse(Request $request, $id): Response
    {
        // Vérifier si la demande existe
        $demande = $this->entityManager->getRepository(DemandeSponsoring::class)->find($id);
    
        if (!$demande) {
            $this->addFlash('error', 'La demande de sponsoring n\'a pas été trouvée.');
            return $this->redirectToRoute('sponsor_demandes');
        }
    
        // Mettre à jour le statut de la demande
        $demande->setStatut('Rejected');
        $this->entityManager->flush();
    
        return $this->redirectToRoute('sponsor_demandes');
    }
    

    #[Route('/test-email', name: 'test_email')]
    public function sendTestEmail(MailerInterface $mailer)
    {
        $email = (new Email())
            ->from('sirineabdlwahab82@gmail.com')
            ->to('sirinebechikh.123@gmail.com')
            ->subject('Test Email via Mailtrap')
            ->text('This is a test sent via Mailtrap!');

        $mailer->send($email);

        return $this->json(['message' => 'Email sent successfully!']);
    }
}