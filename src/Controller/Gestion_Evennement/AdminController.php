<?php
namespace App\Controller\Gestion_Evennement;

use App\Entity\Evenement;
use App\Entity\User;
use App\Form\EvenementType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

final class AdminController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    // ✅ Afficher tous les événements
    #[Route('/admin/evenements', name: 'admin_evenements')]
    public function index(): Response
    {
        $evenements = $this->entityManager->getRepository(Evenement::class)->findAll();

        // Créer un tableau de formulaires d'édition
        $editForms = [];
        foreach ($evenements as $event) {
            $editForms[$event->getId()] = $this->createForm(EvenementType::class, $event)->createView();
        }

        return $this->render('Gestion_Evennement/admin/index.html.twig', [
            'evenements' => $evenements,
            'form' => $this->createForm(EvenementType::class, new Evenement())->createView(),
            'editForms' => $editForms
        ]);
    }

    // ✅ Afficher les événements par user_id
    #[Route('/admin/evenements/user/{id}', name: 'admin_evenements_par_user')]
    public function indexParUser(int $id): Response
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('admin_evenements');
        }

        $evenements = $this->entityManager->getRepository(Evenement::class)
            ->findBy(['user' => $user]);

        $editForms = [];
        foreach ($evenements as $event) {
            $editForms[$event->getId()] = $this->createForm(EvenementType::class, $event)->createView();
        }

        return $this->render('Gestion_Evennement/admin/index.html.twig', [
            'evenements' => $evenements,
            'form' => $this->createForm(EvenementType::class, new Evenement())->createView(),
            'editForms' => $editForms,
            'user' => $user
        ]);
    }

    // ✅ Créer un nouvel événement
    #[Route('/admin/evenement/new', name: 'admin_evenement_new', methods: ['POST'])]
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
                return $this->redirectToRoute('admin_evenements');
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
                        return $this->redirectToRoute('admin_evenements');
                    }

                    if ($imageFile->getSize() > 5 * 1024 * 1024) {
                        $this->addFlash('error', 'Taille maximale 5MB.');
                        return $this->redirectToRoute('admin_evenements');
                    }

                    $newFilename = uniqid().'.'.$imageFile->guessExtension();
                    $imageFile->move($this->getParameter('event_images_directory'), $newFilename);
                    $evenement->setImagePath($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur upload image : '.$e->getMessage());
                    return $this->redirectToRoute('admin_evenements');
                }
            }

            $this->entityManager->persist($evenement);
            $this->entityManager->flush();

            $this->addFlash('success', 'Événement créé avec succès !');
            return $this->redirectToRoute('admin_evenements');
        }

        return $this->redirectToRoute('admin_evenements');
    }

    // ✅ Modifier un événement
    #[Route('/admin/evenement/{id}/edit', name: 'admin_evenement_edit', methods: ['POST'])]
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
            return $this->redirectToRoute('admin_evenements');
        }

        return $this->redirectToRoute('admin_evenements');
    }

    // ✅ Supprimer un événement
    #[Route('/admin/evenement/{id}/delete', name: 'admin_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $evenement): Response
    {
        if ($this->isCsrfTokenValid('delete'.$evenement->getId(), $request->request->get('_token'))) {
            if ($evenement->getImagePath()) {
                $imagePath = $this->getParameter('event_images_directory').'/'.$evenement->getImagePath();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $this->entityManager->remove($evenement);
            $this->entityManager->flush();
            $this->addFlash('success', 'Événement supprimé avec succès !');
        }

        return $this->redirectToRoute('admin_evenements');
    }

    // ✅ Accepter un événement
    #[Route('/admin/evenement/{id}/accept', name: 'admin_evenement_accept', methods: ['POST'])]
    public function acceptEvent(Evenement $evenement): Response
    {
        $evenement->setValidated(1);
        $this->entityManager->flush();
        $this->addFlash('success', 'Événement accepté !');
        return $this->redirectToRoute('admin_evenements');
    }

    // ✅ Rejeter un événement
    #[Route('/admin/evenement/{id}/reject', name: 'admin_evenement_reject', methods: ['POST'])]
    public function rejectEvent(Evenement $evenement): Response
    {
        $evenement->setValidated(0);
        $this->entityManager->flush();
        $this->addFlash('success', 'Événement rejeté !');
        return $this->redirectToRoute('admin_evenements');
    }
}