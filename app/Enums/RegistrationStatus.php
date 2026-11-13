<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    case Confirmed = 'confirmed';
    // Territoire complet : la personne attend qu'une place se libère (pas de badge).
    case Waitlisted = 'waitlisted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Confirmée',
            self::Waitlisted => 'Liste d\'attente',
            self::Cancelled => 'Annulée',
        };
    }
}
