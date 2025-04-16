<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\ConnexionType;
use App\Form\InscriptionType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UsersRepository;
use App\Entity\Users;
use App\Entity\Images;
use App\Service\ImageService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\UserAuthenticator;
use App\Service\SendMailService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use App\Service\JWTService;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ClientController extends AbstractController
{
    #[Route('/index', name: 'app_index')]
    public function index(): Response
    {

        return $this->render('client/index.html.twig');
    }
    #[Route('/wishlist', name: 'app_wishlist')]
    public function wishlist(): Response
    {

        return $this->render('client/wishlist.html.twig');
    }
    #[Route('/destinations', name: 'app_destinations')]
    public function all_destinations(): Response
    {
        return $this->render('client/destinations.html.twig');
    }
    #[Route('/event', name: 'app_event')]
    public function event(): Response
    {
        return $this->render('client/event.html.twig');
    }
    #[Route('/shop', name: 'app_shop')]
    public function shop(): Response
    {
        return $this->render('client/shop.html.twig');
    }
    #[Route('/sensibilisation', name: 'app_sensibilisation')]
    public function sensibilisation(): Response
    {
        return $this->render('client/sensibilisation.html.twig');
    }
    #[Route('/listAvis', name: 'app_avis')]
    public function avis(): Response
    {
        return $this->render('client/avis.html.twig');
    }

    #[Route('/register', name: 'app_user_register')]
    public function register(): Response
    {


        return $this->render('client/security/inscription.html.twig', [
            'registerform' => '',
            'title' => 'Créer un compte',
        ]);
    }

    // ----------------------------------
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('client/security/connexion.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
    #[Route('/profile', name: 'app_profile')]
    public function list(): Response
    {
        return $this->render('client/security/profile.html.twig', [
            'clientP' => 'clientProfile',
        ]);
    }
}
