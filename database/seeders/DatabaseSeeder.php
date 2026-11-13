<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\GalleryPhoto;
use App\Models\ProgramItem;
use App\Models\Territory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $territories = [
            ['ABJ', 'Abidjan', "Côte d'Ivoire"],
            ['BYK', 'Bouaké', "Côte d'Ivoire"],
            ['DKR', 'Dakar', 'Sénégal'],
            ['PAR', 'Paris', 'France'],
            ['CAS', 'Casablanca', 'Maroc'],
            // Points focaux prévus par les TDR 2026-2027.
            ['DIJ', 'Dijon', 'France'],
            ['ANG', 'Angers', 'France'],
            ['BRE', 'Brest', 'France'],
        ];
        foreach ($territories as $i => [$code, $city, $country]) {
            Territory::updateOrCreate(['code' => $code], ['city' => $city, 'country' => $country, 'position' => $i]);
        }

        // Programme type commun aux cinq territoires.
        if (ProgramItem::whereNull('territory_id')->doesntExist()) {
            $program = [
                ['Matin', 'Accueil et retrait des badges', "Scan du QR code à l'entrée, remise du kit participante.", 'Accueil'],
                ['Matin', "Cérémonie d'ouverture", 'Mot du Bureau de Pilotage et de la coordination :ville.', 'Plénière'],
                ['Matin', 'Panel : les femmes et les métiers du numérique', 'Parcours, obstacles et leviers, avec des professionnelles du secteur.', 'Panel'],
                ['Midi', 'Pause et réseautage', 'Échanges libres entre participantes, mentors et partenaires.', 'Réseau'],
                ['Après-midi', 'Ateliers pratiques en parallèle', 'Développement web, data, cybersécurité, entrepreneuriat numérique.', 'Atelier'],
                ['Après-midi', 'Sessions de mentorat', 'Rencontres en petits groupes avec les mentors inscrites.', 'Mentorat'],
                ['Fin de journée', 'Clôture et remise des attestations', 'Bilan de la journée et photo de groupe.', 'Plénière'],
            ];
            foreach ($program as $i => [$slot, $title, $description, $kind]) {
                ProgramItem::create(compact('slot', 'title', 'description', 'kind') + ['position' => $i]);
            }
        }

        if (GalleryPhoto::doesntExist()) {
            $photos = [
                ['2026-2027', "Affiche : rejoignez le comité d'organisation", 'images/affiche-comite.jpg', 'tall'],
                ['2026', "Cérémonie d'ouverture", null, 'wide'],
                ['2026', 'Atelier développement web', null, null],
                ['2026', 'Panel des intervenantes', null, null],
                ['2026', 'Session de mentorat', null, 'wide'],
                ['2025', 'Photo de groupe', null, 'wide'],
                ['2025', 'Hackathon', null, null],
                ['2025', 'Remise des attestations', null, null],
                ['2025', "L'équipe des bénévoles", null, null],
            ];
            foreach ($photos as $i => [$edition, $title, $path, $size]) {
                GalleryPhoto::create(compact('edition', 'title', 'path', 'size') + ['position' => $i]);
            }
        }

        $password = config('jnuaf.admin.password') ?: Str::password(16);
        $admin = User::firstOrCreate(
            ['email' => config('jnuaf.admin.email')],
            ['name' => 'Bureau de Pilotage', 'password' => $password, 'role' => Role::Admin],
        );

        if ($admin->wasRecentlyCreated && ! config('jnuaf.admin.password')) {
            $this->command?->warn("Compte admin créé : {$admin->email} / {$password} (à changer).");
        }
    }
}
