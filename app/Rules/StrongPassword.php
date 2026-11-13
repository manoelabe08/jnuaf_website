<?php



namespace App\Rules;



use App\Models\User;

use Closure;

use Illuminate\Contracts\Validation\ValidationRule;

use Illuminate\Support\Facades\Http;

use Illuminate\Support\Str;



class StrongPassword implements ValidationRule

{

    private const BANNED = [

        'password', 'passw0rd', 'pssw0rd', 'motdepasse', 'azerty', 'qwerty', 'qwertz', '123456', '654321',

        'abcdef', 'letmein', 'welcome', 'bienvenue', 'iloveyou', 'jetaime', 'changeme', 'jnuaf', 'journeedunumerique',

    ];



    public function __construct(private ?User $user = null, private ?string $email = null) {}



    public function validate(string $attribute, mixed $value, Closure $fail): void

    {

        $password = (string) $value;



        if (mb_strlen($password) < 12) {

            $fail('Le mot de passe doit contenir au moins 12 caractères.');



            return;

        }

        if (mb_strlen($password) > 128) {

            $fail('Le mot de passe ne doit pas dépasser 128 caractères.');



            return;

        }

        if (count(array_unique(mb_str_split($password))) < 6) {

            $fail('Ce mot de passe est trop répétitif : utilisez davantage de caractères différents.');



            return;

        }



        $flat = preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii($password)));



        foreach (self::BANNED as $word) {

            if (str_contains($flat, $word)) {

                $fail('Ce mot de passe est trop courant ou trop prévisible : évitez les mots usuels et le nom de l’organisation.');



                return;

            }

        }



        $parts = [];

        $email = $this->user?->email ?? $this->email;

        if ($email) {

            $parts[] = Str::before(Str::lower($email), '@');

        }

        if ($this->user?->name) {

            foreach (preg_split('/[\s\-]+/', Str::lower(Str::ascii($this->user->name))) ?: [] as $piece) {

                $parts[] = $piece;

            }

        }

        foreach ($parts as $part) {

            $part = preg_replace('/[^a-z0-9]/', '', $part);

            if (strlen($part) >= 4 && str_contains($flat, $part)) {

                $fail('Le mot de passe ne doit pas contenir votre nom ni votre adresse email.');



                return;

            }

        }



        if ($this->isBreached($password)) {

            $fail('Ce mot de passe figure dans des fuites de données publiques : choisissez-en un autre.');

        }

    }



    private function isBreached(string $password): bool

    {

        try {

            $sha = strtoupper(sha1($password));

            $response = Http::timeout(3)

                ->withHeaders(['Add-Padding' => 'true', 'User-Agent' => 'JNUAF-password-check'])

                ->get('https://api.pwnedpasswords.com/range/'.substr($sha, 0, 5));



            if ($response->failed()) {

                return false;

            }



            $suffix = substr($sha, 5);

            foreach (explode("\n", $response->body()) as $line) {

                [$hash, $count] = array_pad(explode(':', trim($line), 2), 2, '0');

                if (hash_equals($suffix, strtoupper($hash)) && (int) $count > 0) {

                    return true;

                }

            }

        } catch (\Throwable $exception) {

            report($exception);

        }



        return false;

    }

}

