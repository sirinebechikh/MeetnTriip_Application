<?php
  // src/Controller/Gestion_Evennement/DSponsorController.php
namespace App\Controller\Gestion_Evennement;
use App\Repository\DemandeSponsoringRepository;
use App\Entity\DemandeSponsoring;
use App\Entity\Evenement;
use App\Entity\User;
use App\Form\DemandeSponsoringType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
class DSponsorController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/d_sponsor/{id}', name: 'app_d_sponsor', methods: ['GET', 'POST'])]
    public function createSponsorshipRequest(int $id, Request $request): Response
    {
        // Récupérer l'événement par ID
        $evenement = $this->entityManager->getRepository(Evenement::class)->find($id);
        if (!$evenement) {
            throw $this->createNotFoundException('L’événement demandé n’existe pas.');
        }

        // Vérifier si une demande de sponsoring existe déjà pour cet événement
        $existingRequest = $this->entityManager->getRepository(DemandeSponsoring::class)
            ->findOneBy(['evenement' => $evenement]);
        
        if ($existingRequest) {
            // Si une demande existe déjà, afficher un message d'erreur
            $this->addFlash('error', 'Une demande de sponsoring existe déjà pour cet événement.');
            return $this->redirectToRoute('client_evenements');
        }

        // Récupérer la liste des sponsors
        $sponsors = $this->entityManager->getRepository(User::class)->findBy(['role' => 'SPONSOR']);

        // Création de la demande de sponsoring
        $demandeSponsoring = new DemandeSponsoring();
        $demandeSponsoring->setEvenement($evenement);
        $demandeSponsoring->setStatut('pending'); // Statut initial en attente

        // Création du formulaire
        $form = $this->createForm(DemandeSponsoringType::class, $demandeSponsoring, [
            'sponsors' => $sponsors, // Passer les sponsors au formulaire
        ]);

        // Gérer la soumission du formulaire
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Enregistrer la demande de sponsoring
                $selectedSponsor = $form->get('sponsor')->getData();
                $demandeSponsoring->setSponsor($selectedSponsor); // Associer le sponsor choisi

                $this->entityManager->persist($demandeSponsoring);
                $this->entityManager->flush();

                $this->addFlash('success', 'Votre demande de sponsoring a été envoyée avec succès.');
                return $this->redirectToRoute('client_evenements');
            } else {
                // Log des erreurs
                $errors = (string) $form->getErrors(true, true);
                 $this->addFlash('error', 'Erreur lors de l’envoi de la demande: ' . $errors);
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
        // Remplacer l'ID 1 par l'ID du sponsor que tu veux tester
        $sponsor = 1;  // Utilise un ID spécifique de sponsor
    
        $demandes = $repository->findBySponsorWithEvent($sponsor);  // Passe l'ID directement
    
       // dd($demandes);  // Affiche les demandes de sponsoring pour cet ID de sponsor
    
        return $this->render('Gestion_Evennement/d_sponsor/decision_sponsoring_sponsor.html.twig', [
            'demandes' => $demandes,
        ]);
    }
    

    #[Route('/sponsor/demande/{id}/accepter', name: 'demande_sponsoring_accepter', methods: ['POST'])]
    public function accepter(DemandeSponsoring $demande, EntityManagerInterface $em): Response
    {
        $demande->setStatut('Accepté');
        $em->flush();

        return $this->redirectToRoute('sponsor_demandes');
    }

    #[Route('/sponsor/demande/{id}/refuser', name: 'demande_sponsoring_refuser', methods: ['POST'])]
    public function refuser(Request $request, DemandeSponsoring $demande, EntityManagerInterface $em): Response
    {
         $demande->setStatut('Refusé');
         $em->flush();

        return $this->redirectToRoute('sponsor_demandes');
    }
#[Route('/test-email', name: 'test_email')]
public function sendTestEmail(MailerInterface $mailer)
{
    $email = (new Email())
        ->from('sirineabdlwahab82@gmail.com')  // ton email ici
        ->to('sirinebechikh.123@gmail.com')
        ->subject('Test Email via Mailtrap')
        ->text('Ceci est un test envoyé via Mailtrap !');

    $mailer->send($email);

    return $this->json(['message' => 'Email envoyé avec succès !']);}}