<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Coordinator = 'coordinator';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Bureau de Pilotage (admin)',
            self::Coordinator => 'Coordination territoriale',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
