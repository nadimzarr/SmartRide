<?php

namespace App\Controller;

use App\Entity\Reponse;
use App\Entity\Reclamation;
use App\Form\ReponseType;
use App\Repository\ReponseRepository;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reponse')]
final class ReponseController extends AbstractController
{
    #[Route(name: 'app_reponse_index', methods: ['GET'])]
public function index(Request $request, ReponseRepository $reponseRepository): Response
{
    
    $q = $request->query->get('q', '');

    
    if ($request->isXmlHttpRequest()) {

        $reponses = $reponseRepository->search($q); 

        $data = [];
        foreach ($reponses as $r) {
            $data[] = [
                'id' => $r->getId(),
                'contenu' => $r->getContenu(),
                'date_reponse' => $r->getDateReponse()?->format('d/m/Y'),

                'reclamation_nom' => $r->getReclamation()->getNom(),
                'reclamation_prenom' => $r->getReclamation()->getPrenom(),
                'reclamation_type' => $r->getReclamation()->getTypeReclamation()->value,
                'reclamation_message' => $r->getReclamation()->getMessage(),
                'reclamation_date' => $r->getReclamation()->getDateReclamation()?->format('d/m/Y'),
            ];
        }

        return $this->json($data);
    }

    
    return $this->render('reponse/index.html.twig', [
        'reponses' => $reponseRepository->findAll(),
    ]);
}


   #[Route('/new', name: 'app_reponse_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager): Response
{
    $reclamationsNonRepondues = $entityManager->getRepository(Reclamation::class)
        ->createQueryBuilder('r')
        ->leftJoin('r.reponse', 'rep')
        ->where('rep IS NULL')
        ->getQuery()
        ->getResult();

    $reponse = new Reponse();

    $form = $this->createForm(ReponseType::class, $reponse, [
        'reclamations' => $reclamationsNonRepondues  
    ]);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($reponse);
        $entityManager->flush();

        return $this->redirectToRoute('app_reponse_index');
    }

    return $this->render('reponse/new.html.twig', [
        'reponse' => $reponse,
        'form' => $form,
        
    ]);
}


    #[Route('/{id}', name: 'app_reponse_show', methods: ['GET'])]
    public function show(Reponse $reponse): Response
    {
        return $this->render('reponse/show.html.twig', [
            'reponse' => $reponse,
        ]);
    }

   #[Route('/{id}/edit', name: 'app_reponse_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
{
    $form = $this->createForm(ReponseType::class, $reponse, [
        'is_edit' => true,  
    ]);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();
        return $this->redirectToRoute('app_reponse_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('reponse/edit.html.twig', [
        'reponse' => $reponse,
        'form' => $form->createView(),
    ]);
}


    #[Route('/{id}', name: 'app_reponse_delete', methods: ['POST'])]
    public function delete(Request $request, Reponse $reponse, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$reponse->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reponse);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reponse_index', [], Response::HTTP_SEE_OTHER);
    }


 
    
}
