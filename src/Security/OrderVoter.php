<?php

namespace App\Security;

use App\Entity\Order;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter personnalise : autorise l'acces a une commande uniquement a son
 * proprietaire (ou a un admin). Permissions fines liees au cycle de vie de l'objet.
 */
class OrderVoter extends Voter
{
    public const VIEW = 'ORDER_VIEW';
    public const CANCEL = 'ORDER_CANCEL';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::CANCEL], true)
            && $subject instanceof Order;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Un admin peut tout voir / annuler.
        if (in_array('ROLE_ADMIN', $token->getRoleNames(), true)) {
            return true;
        }

        /** @var Order $order */
        $order = $subject;

        return match ($attribute) {
            self::VIEW => $order->getCustomer() === $user,
            // On ne peut annuler que sa propre commande non encore expediee.
            self::CANCEL => $order->getCustomer() === $user
                && in_array($order->getStatus()->value, ['pending', 'confirmed', 'in_progress'], true),
            default => false,
        };
    }
}
