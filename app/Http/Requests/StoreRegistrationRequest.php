<?php

namespace App\Http\Requests;

use App\Enums\Profile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegistrationRequest extends FormRequest
{
    /** Inscriptions fermées : refus immédiat (403), avant même la validation des champs. */
    public function authorize(): bool
    {
        return (bool) config('jnuaf.registrations_open');
    }

    protected function failedAuthorization(): void
    {
        abort(403, 'Les inscriptions sont fermées.');
    }

    protected function getRedirectUrl(): string
    {
        return route('home').'#inscription';
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        $profile = Profile::tryFrom((string) $this->input('profile'));
        $speaker = $profile?->branch() === 'speaker';

        return [
            'profile' => ['required', Rule::enum(Profile::class)],
            'last_name' => ['required', 'string', 'min:2', 'max:80'],
            'first_name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => [
                'required', 'email:rfc', 'max:150',
                Rule::unique('registrations')->where('edition', config('jnuaf.edition')),
            ],
            'phone' => ['required', 'regex:/^\+?[0-9 ().-]{8,20}$/'],
            'territory_id' => ['required', Rule::exists('territories', 'id')->where('is_active', true)],
            'city' => ['required', 'string', 'min:2', 'max:80'],

            'organization' => ['nullable', 'string', 'max:150'],
            'motivation' => ['nullable', 'string', 'max:1500'],
            'skills' => ['nullable', 'string', 'max:255'],
            'availability' => ['nullable', 'string', 'max:255'],
            'experience' => ['nullable', 'string', 'max:1500'],
            // Le Bureau a besoin de ces informations pour évaluer une proposition de mentorat ou d'intervention.
            'expertise' => [Rule::requiredIf($speaker), 'nullable', 'string', 'max:150'],
            'bio' => [Rule::requiredIf($speaker), 'nullable', 'string', 'max:1500'],
            'topic' => [Rule::requiredIf($profile === Profile::Intervenante), 'nullable', 'string', 'max:200'],

            'accessibility_needs' => ['nullable', 'string', 'max:255'],
            'consent_data' => ['accepted'],
            'consent_image' => ['nullable', 'boolean'],
            // Champ piège anti-robots : doit rester vide.
            
'photo' => [

                'nullable',

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
            'cv' => ['nullable', 'file', 'mimes:pdf', 'max:4096'],
            'website' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [

            'cv.mimes' => "Le CV doit \u{ea}tre au format PDF.",

            'cv.max' => "Le CV ne doit pas d\u{e9}passer 4 Mo.",

            'photo.uploaded' => "La photo n'a pas pu \u{ea}tre envoy\u{e9}e (fichier trop lourd pour le serveur).",
            'email.unique' => "Une inscription existe déjà pour cet email. Pour la modifier ou l'annuler, utilisez le lien personnel reçu dans votre email de confirmation.",
            'phone.regex' => "Numéro invalide, indiquez l'indicatif, ex. +225…",
            'consent_data.accepted' => 'Cochez la case pour autoriser le traitement de vos données.',
        ];
    }

    public function attributes(): array
    {
        return [
            'last_name' => 'nom', 'first_name' => 'prénom', 'phone' => 'téléphone',
            'territory_id' => 'territoire', 'city' => 'ville', 'expertise' => "domaine d'expertise",
            'bio' => 'biographie', 'topic' => 'thématique',
        ];
    }
}
