<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Bloque la connexion d'un utilisateur banni. Appele automatiquement par le
 * firewall (configure via "user_checker" dans security.yaml) avant l'authentification.
 */
class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->isBlocked()) {
            throw new CustomUserMessageAccountStatusException('Votre compte a été suspendu. Contactez le service client.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
