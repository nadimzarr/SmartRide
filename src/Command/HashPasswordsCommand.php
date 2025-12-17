<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:hash-passwords',
    description: 'Hash all plain text passwords in the database',
)]
class HashPasswordsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Hashing Plain Text Passwords');

        // Récupérer tous les utilisateurs
        $users = $this->entityManager->getRepository(User::class)->findAll();

        if (empty($users)) {
            $io->warning('No users found in database');
            return Command::SUCCESS;
        }

        $io->text(sprintf('Found %d users', count($users)));

        $hashedCount = 0;
        $skippedCount = 0;

        foreach ($users as $user) {
            $currentPassword = $user->getPassword();

            // Vérifier si le mot de passe est déjà hashé
            // Les mots de passe hashés commencent généralement par $2y$ (bcrypt)
            if (str_starts_with($currentPassword, '$2y$') || str_starts_with($currentPassword, '$argon')) {
                $io->text(sprintf('⏭️  Skipping user %s (already hashed)', $user->getEmail()));
                $skippedCount++;
                continue;
            }

            // Hasher le mot de passe en clair
            $hashedPassword = $this->passwordHasher->hashPassword($user, $currentPassword);
            $user->setPassword($hashedPassword);

            $io->text(sprintf('✅ Hashed password for user: %s (was: %s)', $user->getEmail(), $currentPassword));
            $hashedCount++;
        }

        // Sauvegarder tous les changements
        if ($hashedCount > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('Successfully hashed %d passwords!', $hashedCount));
        }

        if ($skippedCount > 0) {
            $io->info(sprintf('Skipped %d users (already hashed)', $skippedCount));
        }

        $io->section('Summary');
        $io->table(
            ['Status', 'Count'],
            [
                ['Hashed', $hashedCount],
                ['Skipped', $skippedCount],
                ['Total', count($users)],
            ]
        );

        return Command::SUCCESS;
    }
}

