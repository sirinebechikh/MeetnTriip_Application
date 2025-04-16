<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\Reponse;
use App\Form\ReclamationType;
use App\Form\ReponseType;
use App\Repository\ReclamationRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Snipe\BanBuilder\CensorWords;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReclamationController extends AbstractController
{



    #[Route('/indexx', name: 'app_indexx')]
    public function indexx(): Response
    {

        return $this->render('client/index.html.twig');
    }
    #[Route('/reclamation', name: 'app_reclamation')]
    public function index(): Response
    {
        return $this->render('base.html.twig', [
            'controller_name' => 'ReclamationController',
        ]);
    }
    #[Route('/add_reclamation', name: 'add_reclamation')]

    public function Add(Request  $request , ManagerRegistry $doctrine ,SluggerInterface $slugger, SessionInterface $session) : Response {

        $Reclamation =  new Reclamation() ;
        $form =  $this->createForm(ReclamationType::class,$Reclamation) ;
        $form->handleRequest($request) ;


         // Les lignes de censure
    $censor = new CensorWords;
    $langs = array('fr', 'it', 'en-us', 'en-uk', 'es');
    $badwords = $censor->setDictionary($langs);
    $censor->setReplaceChar("*");


        if($form->isSubmitted()&& $form->isValid()){
            $brochureFile = $form->get('image')->getData();
            //$file =$Reclamation->getImage();
            $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);
            //$uploads_directory = $this->getParameter('upload_directory');
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$brochureFile->guessExtension();
            //$fileName = md5(uniqid()).'.'.$file->guessExtension();
            $brochureFile->move(
                $this->getParameter('upload_directory'),
                $newFilename
            );
            $Reclamation->setImage(($newFilename));


              // Censure du contenu du commentaire
        $string = $censor->censorString($Reclamation->getCommentaire());
        $Reclamation->setCommentaire($string['clean']);


            
            $em= $doctrine->getManager() ;
            $em->persist($Reclamation);
            $em->flush();
            $reclamationName = $Reclamation->getName();

        // Créer le message de notification
        $notificationMessage = "L'ajout a été effectué pour cet utilisateur, nom de la réclamation : $reclamationName";

        // Ajouter la notification à la session flash
        $session->getFlashBag()->add('success', $notificationMessage);

            return $this ->redirectToRoute('add_reclamation') ;
        }
        return $this->render('reclamation/addreclamations.html.twig' , [
            'form' => $form->createView()
        ]) ;
    }

    #[Route('/afficher_reclamation', name: 'afficher_reclamation')]
   
public function AfficheReclamation(ReclamationRepository $repo, PaginatorInterface $paginator, Request $request): Response
{
    $searchTerm = $request->query->get('search');
    $reclamations = $repo->searchByTypeOrNameOrComment($searchTerm);

    $pagination = $paginator->paginate(
        $reclamations,
        $request->query->getInt('page', 1),
        4 // items per page
    );

    return $this->render('reclamation/index.html.twig', [
        'Reclamation' => $pagination,
        'ajoutA' => $reclamations
    ]);
}
#[Route('/afficher_reclamationFront', name: 'afficher_reclamationFront')]
   
public function AfficheReclamationFront(ReclamationRepository $repo, PaginatorInterface $paginator, Request $request): Response
{
    $searchTerm = $request->query->get('search');
    $reclamations = $repo->searchByTypeOrNameOrComment($searchTerm);

    $pagination = $paginator->paginate(
        $reclamations,
        $request->query->getInt('page', 1),
        4 // items per page
    );

    return $this->render('reclamation/indexFront.html.twig', [
        'Reclamation' => $pagination,
        'ajoutA' => $reclamations
    ]);
}

    #[Route('/delete_ab/{id}', name: 'delete_ab')]
    public function Delete($id,ReclamationRepository $repository , ManagerRegistry $doctrine) : Response {
        $Reclamation=$repository->find($id) ;
        $em=$doctrine->getManager() ;
        $em->remove($Reclamation);
        $em->flush();
        return $this->redirectToRoute("afficher_reclamationFront") ;

    }
    #[Route('/update_ab/{id}', name: 'update_ab')]
    function update(ReclamationRepository $repo,$id,Request $request , ManagerRegistry $doctrine,SluggerInterface $slugger){
        $Reclamation = $repo->find($id) ;
        $form=$this->createForm(ReclamationType::class,$Reclamation) ;
        $form->add('update' , SubmitType::class) ;
        $form->handleRequest($request) ;
        if($form->isSubmitted()&& $form->isValid()){
            $brochureFile = $form->get('image')->getData();
            //$file =$Reclamation->getImage();
            $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);
            //$uploads_directory = $this->getParameter('upload_directory');
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$brochureFile->guessExtension();
            //$fileName = md5(uniqid()).'.'.$file->guessExtension();
            $brochureFile->move(
                $this->getParameter('upload_directory'),
                $newFilename
            );
            $Reclamation->setImage(($newFilename));

            $Reclamation = $form->getData();
            $em=$doctrine->getManager() ;
            $em->flush();
            return $this ->redirectToRoute('afficher_reclamationFront') ;
        }
        return $this->render('reclamation/updatereclamations.html.twig' , [
            'form' => $form->createView()
        ]) ;

    }

  
    #[Route('/send_message/{id}', name: 'send_message')]
    
public function sendMessage(Request  $request , ManagerRegistry $doctrine): Response
 {
     $Reponse =  new Reponse() ;
        $form =  $this->createForm(ReponseType::class,$Reponse) ;
        $form->add('Ajouter' , SubmitType::class) ;
        $form->handleRequest($request) ;
        if($form->isSubmitted()&& $form->isValid()){
            $Reponse = $form->getData();
            $em= $doctrine->getManager() ;
            $em->persist($Reponse);
            $em->flush();
            return $this ->redirectToRoute('add_Reponse') ;
        }
        return $this->render('reponse/frontadd.html.twig' , [
            'form' => $form->createView()
        ]) ;
 }


}


