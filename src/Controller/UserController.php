<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\Statut;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;





#[Route('/user')]
final class UserController extends AbstractController
{
   

    #[Route(name: 'app_user_index', methods: ['GET'])]
public function index(Request $request, UserRepository $userRepository, CsrfTokenManagerInterface $csrfTokenManager): Response
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
            $csrfToken = $csrfTokenManager->getToken('toggle-ban' . $user->getId())->getValue();

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

            // Bouton Bloquer/Débloquer
            $html .= '<form method="post" action="' . $this->generateUrl('app_user_toggle_ban', ['id' => $user->getId()]) . '" style="display: inline;">';
            $html .= '<input type="hidden" name="_token" value="' . $csrfToken . '">';
            if ($user->getStatut() === Statut::BANNED) {
                $html .= '<button type="submit" class="btn-action btn-unban" onclick="return confirm(\'Are you sure you want to unblock this user?\')">Unblock</button>';
            } else {
                $html .= '<button type="submit" class="btn-action btn-ban" onclick="return confirm(\'Are you sure you want to block this user?\')">Block</button>';
            }
            $html .= '</form>';

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
   public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
{
    $user = new User();
    $form = $this->createForm(UserType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $plainPassword = $form->get('password')->getData();
        $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();
        $this->addFlash('success', 'Votre Compte a été ajoutée avec succès !');
        return $this->redirectToRoute('app_login');
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

        // Vérifie si l'utilisateur connecté est admin
       $currentUser = $this->getUser();
if ($currentUser && $currentUser->getType() === 'Admin') {
            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        // Sinon redirige vers sa propre page de modification
        return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
    }

    return $this->render('user/edit.html.twig', [
        'user' => $user,
        'form' => $form->createView(),
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

    #[Route('/{id}/toggle-ban', name: 'app_user_toggle_ban', methods: ['POST'])]
    public function toggleBan(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        // Vérifier le token CSRF pour la sécurité
        if (!$this->isCsrfTokenValid('toggle-ban'.$user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        // Basculer entre ACTIVE et BANNED
        if ($user->getStatut() === Statut::BANNED) {
            $user->setStatut(Statut::ACTIVE);
            $this->addFlash('success', 'User account has been unblocked successfully');
        } else {
            $user->setStatut(Statut::BANNED);
            $this->addFlash('success', 'User account has been blocked successfully');
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

}