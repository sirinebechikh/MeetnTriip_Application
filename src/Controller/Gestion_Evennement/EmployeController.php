<?php

namespace App\Controller\Gestion_Evennement;

use App\Entity\Evenement;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Entity\EmployeeEventAssignments;
use App\Form\EmployeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class EmployeController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/evenement/{id}/assign-employees', name: 'assign_employees_to_event')]
    public function assignEmployees(int $id, EntityManagerInterface $em): Response
    {
        $event = $em->getRepository(Evenement::class)->find($id);
        $employees = $em->getRepository(User::class)->findBy(['role' => 'EMPLOY']);

        return $this->render('Gestion_Evennement/employe/index.html.twig', [
            'event' => $event,
            'employees' => $employees,
        ]);
    }

   // #[Route('/evenement/{id}/assign-employees/submit', name: 'submit_employee_assignments', methods: ['POST'])]
   // public function submitAssignments(int $id, Request $request, EntityManagerInterface $em): Response
//{
  ///  $event = $em->getRepository(Evenement::class)->find($id);

//    if (!$event) {
      //  $this->addFlash('error', 'L\'événement spécifié n\'existe pas.');
      //  return $this->redirectToRoute('your_event_list_route'); // Assurez-vous de rediriger vers une page valide
    //}

    // Déboguer les données envoyées
  //  $data = $request->request->get('assignments');  // 'assignments' doit correspondre à l'attribut name dans le formulaire
//dd($data);
    //if (empty($data)) {
      //  $this->addFlash('error', 'Aucune donnée d\'assignation n\'a été envoyée.');
      //  return $this->redirectToRoute('assign_employees_to_event', ['id' => $id]);
    //}

    //foreach ($data as $assignment) {
        // Vérifier que chaque champ existe avant de l'utiliser
       //// if (isset($assignment['employeeId']) && isset($assignment['role']) && isset($assignment['status'])) {
            //$employee = $em->getRepository(User::class)->find($assignment['employeeId']);

            //if (!$employee) {
               // $this->addFlash('error', 'L\'employé spécifié n\'existe pas.');
               // continue;  // Passez à la prochaine assignation
           // }

           // $assignmentEntity = new EmployeeEventAssignments();
           // $assignmentEntity->setEmployee($employee);
           // $assignmentEntity->setEvent($event);
            //$assignmentEntity->setRole($assignment['role']);
            //$assignmentEntity->setStatus($assignment['status']);
            //$assignmentEntity->setCreatedAt(new \DateTime());
           // $assignmentEntity->setUpdatedAt(new \DateTime());
// $em->persist($assignmentEntity);
       // } else// {
            // Log ou message d'erreur si un champ est manquant
           // $this->addFlash('error', 'Certains champs sont manquants pour une affectation.');
        }
   // }

    // Effectuer l'insertion dans la base de données
    //$em//->flush();
    //$this->addFlash('success', 'Employés assignés avec succès.');
    //return $this->redirectToRoute('assign_employees_to_event', ['id' => $id]);
//}