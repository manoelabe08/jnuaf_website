<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommitteeApplicationRequest extends FormRequest
{
    /** Sac d'erreurs distinct : le formulaire d'inscription est sur la même page. */
    protected $errorBag = 'committee';

    public function authorize(): bool
    {
        return true;
    }

    protected function getRedirectUrl(): string
    {
        return route('home').'#candidature';
    }

    public function rules(): array
    {
        $max = config('jnuaf.committee.max_file_kb');

        return [
            'full_name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:150'],
            'phone' => ['required', 'regex:/^\+?[0-9 ().-]{8,20}$/'],
            'country' => ['required', Rule::in(config('jnuaf.committee.countries'))],
            'pole' => ['required', Rule::in(config('jnuaf.committee.poles'))],
            // mimes s'appuie sur le contenu réel du fichier, pas seulement sur l'extension.
            'cv' => ['required', 'file', 'mimes:pdf', "max:{$max}"],
            'letter' => ['required', 'file', 'mimes:pdf', "max:{$max}"],
            
'photo' => [

                'required',

                'file',

                function (string $attribute, mixed $value, \Closure $fail): void {

                    $path = $value instanceof \Illuminate\Http\UploadedFile ? $value->getRealPath() : null;

                    $info = $path ? @getimagesize($path) : false;

                    if ($info === false || ! in_array($info[2] ?? 0, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {

                        $fail("Cette photo n'est pas lisible. Envoyez une image JPG, PNG ou WebP (une photo HEIC d'iPhone doit d'abord \u{ea}tre convertie en JPG).");



                        return;

                    }

                    if ($value->getSize() > 8 * 1024 * 1024) {

                        $fail("La photo ne doit pas d\u{e9}passer 8 Mo.");



                        return;

                    }

                    if ($info[0] < 250 || $info[1] < 300) {

                        $fail("La photo est trop petite : 250 x 300 pixels au minimum.");

                    }

                },

            ],
            'consent' => ['accepted'],
            'website' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [


            'photo.required' => "Ajoutez votre photo (elle servira \u{e0} votre carte de membre).",

            'photo.mimes' => "La photo doit \u{ea}tre au format JPG, PNG ou WebP.",

            'photo.max' => "La photo ne doit pas d\u{e9}passer 8 Mo.",

            'photo.dimensions' => "La photo est trop petite (250 x 300 pixels au minimum).",

            'photo.uploaded' => "La photo n'a pas pu \u{ea}tre envoy\u{e9}e (fichier trop lourd pour le serveur).",

            'cv.mimes' => 'Le CV doit être au format PDF.',
            'letter.mimes' => 'La lettre doit être au format PDF.',
            'cv.max' => 'Le CV dépasse 2 Mo.',
            'letter.max' => 'La lettre dépasse 2 Mo.',
            'phone.regex' => "Numéro invalide, indiquez l'indicatif.",
            'consent.accepted' => 'Cochez la case pour que le Bureau puisse conserver votre candidature.',
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'nom et prénom', 'phone' => 'numéro WhatsApp', 'country' => 'pays',
            'pole' => 'pôle', 'cv' => 'CV', 'letter' => 'lettre de motivation',
        ];
    }
}
