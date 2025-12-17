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
         if (!$this->getUser()) {
             $this->addFlash('error', 'You must be logged in to access reclamations.');
             return $this->redirectToRoute('app_login');
         }

        $q = $request->query->get('q', '');

        $reclamations = $reclamationRepository->findByAllFields($q);

        if ($request->isXmlHttpRequest()) {
            $data = [];
            foreach ($reclamations as $rec) {
                $data[] = [
                    'id' => $rec->getId(),
                    'nom' => $rec->getNom(),
                    'prenom' => $rec->getPrenom(),
                    'user_nom' => $rec->getUser() ? $rec->getUser()->getNom() : null,
                    'user_prenom' => $rec->getUser() ? $rec->getUser()->getPrenom() : null,
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
    public function new(Request $request, EntityManagerInterface $entityManager, BadWordsFilter $filter, InfoBipService $smsService, \Psr\Log\LoggerInterface $logger): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('error', 'You must be logged in to create a reclamation.');
            return $this->redirectToRoute('app_login');
        }

        $reclamation = new Reclamation();
        $reclamation->setUser($user);
        $reclamation->setNom($user->getNom());
        $reclamation->setPrenom($user->getPrenom());

        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($filter->containsBadWords($reclamation->getMessage() ?? '')) {
                $form->get('message')->addError(
                    new \Symfony\Component\Form\FormError('Votre message contient des mots interdits.')
                );
                $logger->warning('Tentative de création de réclamation avec mots interdits', [
                    'user' => $user->getUserIdentifier(),
                    'message' => $reclamation->getMessage()
                ]);
            }

            if ($form->isValid()) {
                try {
                    // Double check user association
                    if (!$reclamation->getUser()) {
                        $reclamation->setUser($user);
                    }
                    if (!$reclamation->getNom()) $reclamation->setNom($user->getNom());
                    if (!$reclamation->getPrenom()) $reclamation->setPrenom($user->getPrenom());

                    $entityManager->persist($reclamation);
                    $entityManager->flush();
                    
                    $logger->info('Reclamation created successfully', [
                        'id' => $reclamation->getId(),
                        'user' => $user->getUserIdentifier()
                    ]);

                    try {
                        $smsService->sendReclamationNotification($reclamation, '21656521654');
                    } catch (\Exception $e) {
                        $logger->error('Error while sending SMS', [
                            'error' => $e->getMessage(),
                            'reclamation_id' => $reclamation->getId()
                        ]);
                    }
                    
                    $this->addFlash('success', 'Your reclamation was successfully created!');

                    return $this->redirectToRoute('app_reclamation_index');
                } catch (\Exception $e) {
                    $logger->error('Error while saving the reclamation', [
                        'error' => $e->getMessage(),
                        'user' => $user->getUserIdentifier()
                    ]);
                    $this->addFlash('error', 'An error occurred while saving your reclamation.');
                }
            } else {
                $logger->warning('Reclamation form validation failed', [
                    'user' => $user->getUserIdentifier(),
                    'errors' => (string) $form->getErrors(true, false)
                ]);
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
