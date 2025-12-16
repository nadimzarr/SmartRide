<?php

namespace App\Controller;

use App\Service\RecaptchaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Request;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(
        Request $request, 
        AuthenticationUtils $authenticationUtils,
        RecaptchaService $recaptchaService
    ): Response {
        // Si l'utilisateur est déjà connecté, rediriger
        if ($this->getUser()) {
            return $this->redirectToRoute('app_user_index');
        }

        // ✅ Vérification reCAPTCHA v2 - UNIQUEMENT pour les soumissions POST
        if ($request->isMethod('POST')) {
            // Récupère la réponse reCAPTCHA v2 (g-recaptcha-response)
            $recaptchaResponse = $request->request->get('g-recaptcha-response');
            $clientIp = $request->getClientIp();

            // Valide le token avec Google reCAPTCHA API
            $recaptchaResult = $recaptchaService->verify($recaptchaResponse, $clientIp);

            if (!$recaptchaResult['success']) {
                // reCAPTCHA v2 échoué - Affiche un message d'erreur
                $this->addFlash('error', $recaptchaResult['message']);
                
                // Log pour débogage
                error_log('❌ reCAPTCHA v2 ÉCHEC: ' . $recaptchaResult['message']);
                
                // Redirige vers la page de login
                return $this->redirectToRoute('app_login');
            }

            // ✅ reCAPTCHA v2 validé avec succès
            error_log('✅ reCAPTCHA v2 SUCCÈS! Utilisateur vérifié.');
        }

        // Récupère l'erreur de connexion si elle existe (identifiants incorrects)
        $error = $authenticationUtils->getLastAuthenticationError();

        // Récupère le dernier email saisi
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode est interceptée par le firewall de logout.');
    }
}