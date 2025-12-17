<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestMailerController extends AbstractController
{
    #[Route('/test-mail', name: 'app_test_mail')]
    public function sendTestEmail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('mohamedoueslati788@gmail.com') // ton email Gmail
            ->to('mohamedoueslati788@gmail.com')   // tu peux mettre ton email pour test
            ->subject('Test Symfony Mailer')
            ->text('Ceci est un test pour vérifier l’envoi d’email via Gmail.');

        try {
            $mailer->send($email);
            return new Response('Email envoyé avec succès ! Vérifie ta boîte de réception.');
        } catch (\Exception $e) {
            return new Response('Erreur lors de l’envoi : ' . $e->getMessage());
        }
    }
}
