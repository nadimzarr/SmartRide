<?php

namespace App\Security;

use App\Entity\User;
use App\Enum\Statut;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Vérifier si le compte est banni
        if ($user->getStatut() === Statut::BANNED) {
            throw new CustomUserMessageAccountStatusException('The account is banned');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Vérification supplémentaire après authentification si nécessaire
        if ($user->getStatut() === Statut::BANNED) {
            throw new CustomUserMessageAccountStatusException('The account is banned');
        }
    }
}

