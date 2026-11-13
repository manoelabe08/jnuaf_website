<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartnerInquiryRequest extends FormRequest
{
    /** Sac d'erreurs distinct : d'autres formulaires sont sur la même page. */
    protected $errorBag = 'partner';

    public function authorize(): bool
    {
        return true;
    }

    protected function getRedirectUrl(): string
    {
        return route('home').'#partenaires';
    }

    public function rules(): array
    {
        return [
            'organization' => ['required', 'string', 'min:2', 'max:150'],
            'contact_name' => ['required', 'string', 'min:3', 'max:120'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:150'],
            'phone' => ['nullable', 'regex:/^\+?[0-9 ().-]{8,20}$/'],
            'country' => ['required', 'string', 'max:60'],
            'types' => ['required', 'array', 'min:1'],
            'types.*' => [Rule::in(array_keys(config('jnuaf.partners.types')))],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'consent' => ['accepted'],
            'website' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'types.required' => 'Choisissez au moins une forme de partenariat.',
            'phone.regex' => "Numéro invalide, indiquez l'indicatif.",
            'consent.accepted' => 'Cochez la case pour que nous puissions vous recontacter.',
        ];
    }

    public function attributes(): array
    {
        return [
            'organization' => 'organisation', 'contact_name' => 'nom du contact', 'job_title' => 'fonction',
            'phone' => 'téléphone', 'country' => 'pays', 'types' => 'forme de partenariat', 'message' => 'message',
        ];
    }
}
