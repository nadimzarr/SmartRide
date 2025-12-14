<?php

namespace App\Controller;
use App\Service\InfoBipService;
use App\Entity\Reclamation;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Vendor\BadWordsBundle\Service\BadWordsFilter;
#[Route('/reclamation')]
final class ReclamationController extends AbstractController
{
    #[Route(name: 'app_reclamation_index', methods: ['GET'])]
    public function index(Request $request, ReclamationRepository $reclamationRepository): Response
    {
        $q = $request->query->get('q', '');

        $reclamations = $reclamationRepository->findByAllFields($q);

        if ($request->isXmlHttpRequest()) {
            $data = [];
            foreach ($reclamations as $rec) {
                $data[] = [
                    'id' => $rec->getId(),
                    'nom' => $rec->getNom(),
                    'prenom' => $rec->getPrenom(),
                    'message' => $rec->getMessage(),
                    'type_reclamation' => $rec->getTypeReclamation()->value,
                    'date_reclamation' => $rec->getDateReclamation()->format('Y-m-d'),
                ];
            }
            return new JsonResponse($data);
        }

        return $this->render('reclamation/index.html.twig', [
            'reclamations' => $reclamations,
        ]);
    }

  #[Route('/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager, BadWordsFilter $filter, InfoBipService $smsService): Response
{
    $reclamation = new Reclamation();
    $form = $this->createForm(ReclamationType::class, $reclamation);
    $form->handleRequest($request);

    if ($form->isSubmitted()) {

      
        if ($filter->containsBadWords($reclamation->getMessage() ?? '')) {
            
            $form->get('message')->addError(
                new \Symfony\Component\Form\FormError('Votre message contient des mots interdits.')
            );
        }

        if ($form->isValid()) {
            $entityManager->persist($reclamation);
            $entityManager->flush();
            
              $smsService->sendReclamationNotification($reclamation, '21656521654');
            
            $this->addFlash('success', 'Votre réclamation a été ajoutée avec succès !');

            return $this->redirectToRoute('app_reclamation_index');
        }
    }

    return $this->render('reclamation/new.html.twig', [
        'reclamation' => $reclamation,
        'form' => $form,
    ]);
}





    #[Route('/{id}', name: 'app_reclamation_show', methods: ['GET'])]
    public function show(Reclamation $reclamation): Response
    {
        return $this->render('reclamation/show.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reclamation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Votre réclamation a été mise à jour avec succès !');
            return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reclamation/edit.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reclamation_delete', methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        
        if ($this->isCsrfTokenValid('delete'.$reclamation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reclamation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/reponse', name: 'app_reclamation_reponse')]
    public function showReponse(Reclamation $reclamation): Response
    {
        $reponse = $reclamation->getReponse();

        return $this->render('reclamation/reponse.html.twig', [
            'reclamation' => $reclamation,
            'reponse' => $reponse,
        ]);
    }
}
