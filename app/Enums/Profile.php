<?php

namespace App\Enums;

enum Profile: string
{
    case Participante = 'participante';
    case Benevole = 'benevole';
    case Mentor = 'mentor';
    case Intervenante = 'intervenante';

    public function label(): string
    {
        return match ($this) {
            self::Participante => 'Participante',
            self::Benevole => 'Bénévole',
            self::Mentor => 'Mentor',
            self::Intervenante => 'Intervenante',
        };
    }

    /** Groupe de champs spécifiques affiché dans le formulaire. */
    public function branch(): string
    {
        return match ($this) {
            self::Mentor, self::Intervenante => 'speaker',
            default => $this->value,
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
