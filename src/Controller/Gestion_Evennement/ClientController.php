<?php

namespace App\Controller\Gestion_Evennement;
use App\Entity\DemandeSponsoring;
use App\Form\DemandeSponsoringType;
use App\Entity\User;

use App\Entity\Evenement;
use App\Form\EvenementType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
 
final class ClientController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    // Afficher uniquement les événements de l'utilisateur connecté
    #[Route('/client/evenements', name: 'client_evenements')]
    public function index(): Response
    {
        $user = $this->getUser(); // Récupérer l'utilisateur connecté

        // Filtrer les événements de l'utilisateur connecté
        $evenements = $this->entityManager->getRepository(Evenement::class)->findBy(['user' => $user]);

        // Créer un tableau de formulaires d'édition
        $editForms = [];
        foreach ($evenements as $event) {
            $editForms[$event->getId()] = $this->createForm(EvenementType::class, $event)->createView();
        }

        return $this->render('Gestion_Evennement/client/index.html.twig', [
            'evenements' => $evenements,
            'form' => $this->createForm(EvenementType::class, new Evenement())->createView(),
            'editForms' => $editForms
        ]);
    }

     // ✅ Créer un nouvel événement
    #[Route('/client/evenement/new', name: 'client_evenement_new', methods: ['POST'])]
    public function new(Request $request): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dateDebut = $evenement->getDateDebut();
            $dateFin = $evenement->getDateFin();

            if ($dateFin <= $dateDebut) {
                $this->addFlash('error', 'La date de fin doit être après la date de début.');
                return $this->redirectToRoute('client_evenements');
            }

            // Assigner l'utilisateur connecté
            $user = $this->getUser();
            $evenement->setUser($user);

            // Gestion de l'upload d'image
            $imageFile = $form->get('imagePath')->getData();
            if ($imageFile) {
                try {
                    if (!in_array($imageFile->getMimeType(), ['image/jpeg', 'image/png'])) {
                        $this->addFlash('error', 'Format invalide. Seuls JPEG et PNG sont acceptés.');
                        return $this->redirectToRoute('client_evenements');
                    }

                    if ($imageFile->getSize() > 5 * 1024 * 1024) {
                        $this->addFlash('error', 'Taille maximale 5MB.');
                        return $this->redirectToRoute('client_evenements');
                    }

                    $newFilename = uniqid().'.'.$imageFile->guessExtension();
                    $imageFile->move($this->getParameter('event_images_directory'), $newFilename);
                    $evenement->setImagePath($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur upload image : '.$e->getMessage());
                    return $this->redirectToRoute('client_evenements');
                }
            }

            $this->entityManager->persist($evenement);
            $this->entityManager->flush();

            $this->addFlash('success', 'Événement créé avec succès !');
            return $this->redirectToRoute('client_evenements');
        }

        return $this->redirectToRoute('client_evenements');
    }

    // ✅ Modifier un événement
    #[Route('/client/evenement/{id}/edit', name: 'client_evenement_edit', methods: ['POST'])]
    public function edit(Request $request, Evenement $evenement): Response
    {
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imagePath')->getData();
            if ($imageFile) {
                if ($evenement->getImagePath()) {
                    $oldImage = $this->getParameter('event_images_directory').'/'.$evenement->getImagePath();
                    if (file_exists($oldImage)) {
                        unlink($oldImage);
                    }
                }

                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move($this->getParameter('event_images_directory'), $newFilename);
                $evenement->setImagePath($newFilename);
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'Événement mis à jour avec succès !');
            return $this->redirectToRoute('client_evenements');
        }

        return $this->redirectToRoute('client_evenements');
    }

    // Supprimer un événement
    #[Route('/client/evenement/{id}/delete', name: 'client_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $evenement): Response
    {
        $user = $this->getUser();
        if ($evenement->getUser() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres événements.');
            return $this->redirectToRoute('client_evenements');
        }

        if ($this->isCsrfTokenValid('delete'.$evenement->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($evenement);
            $this->entityManager->flush();
            $this->addFlash('success', 'Événement supprimé avec succès !');
        }

        return $this->redirectToRoute('client_evenements');
    }// Dans votre contrôleur
   
}