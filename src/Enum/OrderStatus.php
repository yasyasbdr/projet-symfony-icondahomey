<?php

namespace App\Enum;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Preparing = 'preparing';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente de validation',
            self::Confirmed => 'Confirmée',
            self::InProgress => 'En cours de réalisation',
            self::Preparing => 'Colis en préparation',
            self::Shipped => 'Commande expediée',
            self::Completed => 'Terminée',
            self::Cancelled => 'Annulée',
        };
    }

    /** Etapes affichées dans la timeline "fil de laine" (front client). */
    public static function timeline(): array
    {
        return [self::Pending, self::InProgress, self::Preparing, self::Shipped];
    }

    /** Position dans la timeline, pour savoir quelles etapes sont "atteintes". */
    public function timelineIndex(): int
    {
        return match ($this) {
            self::Pending => 0,
            self::Confirmed, self::InProgress => 1,
            self::Preparing => 2,
            self::Shipped, self::Completed => 3,
            self::Cancelled => -1,
        };
    }
}
