<?php

namespace App\Controller;

use App\Entity\Reponse;
use App\Form\ReponseType;
use App\Repository\ReponseRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
class ReponseController extends AbstractController
{
    #[Route('/reponse', name: 'app_reponse')]
    public function index(): Response
    {
        return $this->render('base.html.twig', [
            'controller_name' => 'ReponseController',
        ]);
    }
    #[Route('/add_Reponse', name: 'add_Reponse')]

    public function Add(Request  $request , ManagerRegistry $doctrine ) : Response {
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

    #[Route('/afficher_reponse', name: 'afficher_reponse')]
    public function AfficheReponse (ReponseRepository $repo ,PaginatorInterface $paginator ,Request $request     ): Response
    {
        //$repo=$this ->getDoctrine()->getRepository(Reponse::class) ;
        $Reponse=$repo->findAll() ;
        $pagination = $paginator->paginate(
            $Reponse,
            $request->query->getInt('page', 1),
            3 // items per page
        );
        return $this->render('reponse/index.html.twig' , [
            'Reponse' => $pagination ,
            'ajoutA' => $Reponse
        ]) ;
    }
    #[Route('/afficher_reponseFront', name: 'afficher_reponseFront')]
    public function AfficheReponseFront (ReponseRepository $repo ,PaginatorInterface $paginator ,Request $request     ): Response
    {
        //$repo=$this ->getDoctrine()->getRepository(Reponse::class) ;
        $Reponse=$repo->findAll() ;
        $pagination = $paginator->paginate(
            $Reponse,
            $request->query->getInt('page', 1),
            3 // items per page
        );
        return $this->render('reponse/indexFront.html.twig' , [
            'Reponse' => $pagination ,
            'ajoutA' => $Reponse
        ]) ;
    }

    #[Route('/delete_adh/{id}', name: 'delete_adh')]
    public function Delete($id,ReponseRepository $repository , ManagerRegistry $doctrine) : Response {
        $Reponse=$repository->find($id) ;
        $em=$doctrine->getManager() ;
        $em->remove($Reponse);
        $em->flush();
        return $this->redirectToRoute("afficher_reponse") ;

    }
    #[Route('/update_adh/{id}', name: 'update_adh')]
    function update(ReponseRepository $repo,$id,Request $request , ManagerRegistry $doctrine){
        $Reponse = $repo->find($id) ;
        $form=$this->createForm(ReponseType::class,$Reponse) ;
        $form->add('update' , SubmitType::class) ;
        $form->handleRequest($request) ;
        if($form->isSubmitted()&& $form->isValid()){

            $Reponse = $form->getData();
            $em=$doctrine->getManager() ;
            $em->flush();
            return $this ->redirectToRoute('afficher_reponse') ;
        }
        return $this->render('reponse/update.html.twig' , [
            'form' => $form->createView()
        ]) ;

    }
   
}
