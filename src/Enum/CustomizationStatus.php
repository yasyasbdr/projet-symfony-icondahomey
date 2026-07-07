<?php

namespace App\Enum;

enum CustomizationStatus: string
{
    case Pending = 'pending';
    case Priced = 'priced';
    case Accepted = 'accepted';
    case Refused = 'refused';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Demande envoyee',
            self::Priced => 'Prix propose',
            self::Accepted => 'Acceptee par le client',
            self::Refused => 'Refusee',
        };
    }
}
