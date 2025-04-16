<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Produit;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UsersRepository;
use App\Entity\Users;
use App\Form\CategorieFormType;
use App\Security\AdminAuthenticator;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Form\InscriptionType;
use App\Form\ProduitFormType;
use App\Repository\CategorieRepository;
use App\Repository\ProduitRepository;

class AdminController extends AbstractController
{
    #[Route('/admin/index', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }
    #[Route(path: '/admin/login', name: 'app_admin_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('admin/security/connexion.html.twig', ['email' => $lastUsername, 'error' => $error]);
    }
    #[Route('/admin/reset', name: 'app_admin_reset')]
    public function reset(): Response
    {
        return $this->render('admin/security/reset.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }
    #[Route('/admin/reservation', name: 'app_reservationManagement')]
    public function reservationManagement(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }


    
}
