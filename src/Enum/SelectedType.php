<?php

namespace App\Enum;

enum SelectedType: string
{
    case Physical = 'physical';
    case Pattern = 'pattern';

    public function label(): string
    {
        return match ($this) {
            self::Physical => 'Creation physique',
            self::Pattern => 'Patron PDF',
        };
    }
}
