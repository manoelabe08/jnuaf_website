<?php

namespace App\Enums;

enum InquiryStatus: string
{
    case New = 'new';
    case InDiscussion = 'discussion';
    case Signed = 'signed';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouvelle',
            self::InDiscussion => 'En discussion',
            self::Signed => 'Partenariat conclu',
            self::Declined => 'Sans suite',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
