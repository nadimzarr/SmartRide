<?php

namespace App\Command;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;

#[AsCommand(
    name: 'app:test-email',
    description: 'Envoie un e-mail de test pour vérifier la configuration Gmail SMTP',
)]
class TestEmailCommand extends Command
{
    public function __construct(private MailerInterface $mailer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse e-mail destinataire')
            ->addArgument('subject', InputArgument::OPTIONAL, 'Sujet de l\'e-mail', 'Test Email - SmartRide')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $recipientEmail = $input->getArgument('email');
        $subject = $input->getArgument('subject');

        $io->title('📧 Test d\'envoi d\'e-mail');
        $io->section('Configuration');
        $io->writeln([
            'Destinataire: ' . $recipientEmail,
            'Sujet: ' . $subject,
            'Mode: SYNCHRONE (immédiat)',
        ]);

        try {
            $io->section('Envoi en cours...');

            // Créer l'e-mail
            $email = (new TemplatedEmail())
                ->from($_ENV['MAILER_FROM_EMAIL'] ?? 'mohamedoueslati788@gmail.com')
                ->to($recipientEmail)
                ->subject($subject)
                ->htmlTemplate('emails/test.html.twig')
                ->context([
                    'recipientEmail' => $recipientEmail,
                    'timestamp' => new \DateTime(),
                ]);

            // Envoyer l'e-mail (SYNCHRONE)
            $this->mailer->send($email);

            $io->success('✓ E-mail envoyé avec succès!');
            $io->writeln([
                '',
                '📬 Détails:',
                '  • Destinataire: ' . $recipientEmail,
                '  • Sujet: ' . $subject,
                '  • Heure: ' . (new \DateTime())->format('Y-m-d H:i:s'),
                '  • Mode: SYNCHRONE (immédiat)',
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('❌ Erreur lors de l\'envoi de l\'e-mail');
            $io->writeln([
                '',
                'Message d\'erreur:',
                $e->getMessage(),
                '',
                'Vérifiez:',
                '  1. La configuration .env.local',
                '  2. Le mot de passe d\'application Gmail',
                '  3. L\'authentification 2FA activée',
                '  4. Les logs: tail -f var/log/dev.log',
            ]);

            return Command::FAILURE;
        }
    }
}
