<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class EmailTestController extends AbstractController
{
    #[Route('/email-test', name: 'app_email_test')]
    public function sendTestEmail(MailerInterface $mailer, LoggerInterface $logger): Response
    {
        $logFile = $this->getParameter('kernel.project_dir') . '/var/log/email_test.log';
        $timestamp = date('Y-m-d H:i:s');
        
        $email = (new Email())
            ->from('mohamedoueslati788@gmail.com') // Doit correspondre au MAILER_DSN
            ->to('mohamedoueslati788@gmail.com')   // Email de test
            ->subject('Test Symfony Mailer - ' . $timestamp)
            ->html('<h1>Test d\'envoi d\'email</h1><p>Ceci est un test pour vérifier l\'envoi d\'email via Gmail.</p><p>Date: ' . $timestamp . '</p>');

        try {
            $mailer->send($email);
            
            // Log succès
            $logMessage = sprintf(
                "[%s] ✅ SUCCESS - Email envoyé avec succès de %s vers %s\n",
                $timestamp,
                'mohamedoueslati788@gmail.com',
                'mohamedoueslati788@gmail.com'
            );
            
            file_put_contents($logFile, $logMessage, FILE_APPEND);
            $logger->info('Email test envoyé avec succès', ['timestamp' => $timestamp]);
            
            return new Response(
                '<h1>✅ Email envoyé avec succès !</h1>' .
                '<p>Vérifie ta boîte de réception : mohamedoueslati788@gmail.com</p>' .
                '<p>Log enregistré dans : var/log/email_test.log</p>' .
                '<p><a href="/email-test">Renvoyer un test</a></p>',
                200,
                ['Content-Type' => 'text/html']
            );
            
        } catch (\Exception $e) {
            // Log erreur détaillée
            $errorMessage = sprintf(
                "[%s] ❌ ERROR - Échec de l'envoi\n" .
                "   Type d'erreur: %s\n" .
                "   Message: %s\n" .
                "   Fichier: %s:%d\n" .
                "   Trace: %s\n\n",
                $timestamp,
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            );
            
            file_put_contents($logFile, $errorMessage, FILE_APPEND);
            $logger->error('Erreur lors de l\'envoi d\'email test', [
                'exception' => $e->getMessage(),
                'timestamp' => $timestamp
            ]);
            
            return new Response(
                '<h1>❌ Erreur lors de l\'envoi</h1>' .
                '<h2>Message d\'erreur :</h2>' .
                '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>' .
                '<h2>Type d\'exception :</h2>' .
                '<p>' . get_class($e) . '</p>' .
                '<h2>Fichier :</h2>' .
                '<p>' . $e->getFile() . ':' . $e->getLine() . '</p>' .
                '<p>Log détaillé enregistré dans : var/log/email_test.log</p>' .
                '<p><a href="/email-test">Réessayer</a></p>',
                500,
                ['Content-Type' => 'text/html']
            );
        }
    }
}

