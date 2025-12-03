<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;



#[Route('/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
public function index(Request $request, UserRepository $userRepository): Response
{
    $filters = [
        'search' => $request->query->get('search', ''),
        'type'   => $request->query->get('type', ''),
        'status' => $request->query->get('status', ''),
    ];

    $users = $userRepository->findByAll($filters);

    // Vérifie si la requête est AJAX
    if ($request->isXmlHttpRequest()) {
        $html = '';
        foreach ($users as $user) {
            $html .= '<div class="table-row">';
            $html .= '<div class="body-cell checkbox-cell"><input type="checkbox" class="row-checkbox"></div>';
            $html .= '<div class="body-cell id-cell">' . $user->getId() . '</div>';
            $html .= '<div class="body-cell nom-cell"><strong>' . $user->getNom() . '</strong></div>';
            $html .= '<div class="body-cell prenom-cell">' . $user->getPrenom() . '</div>';
            $html .= '<div class="body-cell email-cell">' . $user->getEmail() . '</div>';
            $html .= '<div class="body-cell phone-cell">' . $user->getTel() . '</div>';
            $html .= '<div class="body-cell type-cell"><span class="type-badge ' . strtolower($user->getType()->value) . '">' . $user->getType()->value . '</span></div>';
            $html .= '<div class="body-cell status-cell"><span class="status-badge ' . strtolower($user->getStatut()->value) . '">' . $user->getStatut()->value . '</span></div>';
            $html .= '<div class="body-cell actions-cell">';
            $html .= '<a href="' . $this->generateUrl('app_user_show', ['id' => $user->getId()]) . '" class="btn-action btn-show">Show</a>';
            $html .= '<a href="' . $this->generateUrl('app_user_edit', ['id' => $user->getId()]) . '" class="btn-action btn-edit">Edit</a>';
            $html .= '</div></div>';
        }

        if (empty($users)) {
            $html = '<div class="no-data"><p>No records found</p></div>';
        }

        return new Response($html);
    }

    // Requête normale : rend la page complète
    return $this->render('user/index.html.twig', [
        'users' => $users,
    ]);
}

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
    
    
}