<?php

namespace App\Controller\Gestion_Evennement;
use App\Repository\Gestion_evenement\EmployeeEventAssignmentsRepository;
use App\Entity\Gestion_Evenement\Evenement;
use App\Entity\gestion_user\User;
use App\Repository\UserRepository;
use App\Entity\Gestion_Evenement\EmployeeEventAssignments;
use App\Form\EmployeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
 

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
        if (!$this->isGranted('ROLE_CLIENT')) {
            return $this->redirectToRoute('app_home');
        }
        
        // Get the current client user
        $currentUser = $this->getUser();
        $event = $em->getRepository(Evenement::class)->find($id);
        
        // Filter employees by the client's name (company name)
        $employees = $em->getRepository(User::class)->findBy([
            'role' => 'EMPLOY',
            'nameCompany' => $currentUser->getNom() // Match employees with nameCompany = client's name
        ]);

        return $this->render('Gestion_Evennement/employe/index.html.twig', [
            'event' => $event,
            'employees' => $employees,
        ]);
    }
    
    #[Route('/evenement/{id}/assign-employees/submit', name: 'submit_employee_assignments', methods: ['POST'])]
    public function submitAssignments(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $event = $em->getRepository(Evenement::class)->find($id);
    
        if (!$event) {
            $this->addFlash('error2', 'Événement introuvable.');
            return $this->redirectToRoute('assign_employees_to_event', ['id' => $id]);
        }
    
        $data = $request->request->get('assignments'); // récupère les données
    
        if (empty($data)) {
            $this->addFlash('error2', 'Aucune affectation reçue.');
            return $this->redirectToRoute('assign_employees_to_event', ['id' => $id]);
        }
    
        // Décodage JSON si jamais c'est une chaîne (optionnel si bien configuré dans le Twig)
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
    
        foreach ($data as $assignment) {
            if (
                isset($assignment['employeeId']) &&
                isset($assignment['role']) 
             ) {
                $employee = $em->getRepository(User::class)->find($assignment['employeeId']);
    
                if (!$employee) {
                    $this->addFlash('error2', 'Employé ID ' . $assignment['employeeId'] . ' introuvable.');
                    continue;
                }
    
                $assignmentEntity = new EmployeeEventAssignments();
                $assignmentEntity->setEmployee($employee);
                $assignmentEntity->setEvent($event);
                $assignmentEntity->setRole($assignment['role']);
                $assignmentEntity->setStatus('Assigned'); // Default status is Assigned
     
                if (isset($assignment['createdAt'])) {
                    $assignmentEntity->setCreatedAt(new \DateTime($assignment['createdAt']));
                }
    
                if (isset($assignment['updatedAt'])) {
                    $assignmentEntity->setUpdatedAt(new \DateTime($assignment['updatedAt']));
                }
    
                $em->persist($assignmentEntity);
            } else {
                $this->addFlash('error2', 'Champ(s) manquant(s) pour une affectation.');
            }
        }
    
        $em->flush();
    
        $this->addFlash('success2', 'Assignations enregistrées avec succès.');
        return $this->redirectToRoute('assign_employees_to_event', ['id' => $id]);
    }
    
    #[Route('/employe/evenements', name: 'App_employe')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_EMPLOY');
    
        $user = $this->getUser();
    
        $assignations = $entityManager
            ->getRepository(EmployeeEventAssignments::class)
            ->findBy(['employee' => $user]);
    
        return $this->render('Gestion_Evennement/employe/employe.html.twig', [
            'assignations' => $assignations,
        ]);
    }
    
    #[Route('/employe/assignment/{id}/update-status/{status}', name: 'update_assignment_status')]
    public function updateAssignmentStatus(int $id, string $status, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_EMPLOY');
        
        $assignment = $em->getRepository(EmployeeEventAssignments::class)->find($id);
        
        if (!$assignment) {
            $this->addFlash('error', 'Assignment not found.');
            return $this->redirectToRoute('App_employe');
        }
        
        // Verify the employee is the owner of this assignment
        if ($assignment->getEmployee() !== $this->getUser()) {
            $this->addFlash('error', 'You are not authorized to update this assignment.');
            return $this->redirectToRoute('App_employe');
        }
        
        // Validate status
        if (!in_array($status, ['Assigned', 'Confirmed', 'Cancelled'])) {
            $this->addFlash('error', 'Invalid status.');
            return $this->redirectToRoute('App_employe');
        }
        
        $assignment->setStatus($status);
        $assignment->setUpdatedAt(new \DateTime());
        
        $em->flush();
        
        $this->addFlash('success', 'Assignment status updated successfully.');
        return $this->redirectToRoute('App_employe');
    }
}