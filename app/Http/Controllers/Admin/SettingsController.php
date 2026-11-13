<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'values' => [
                'event_date' => config('jnuaf.event_date'),
                'registrations_open' => (bool) config('jnuaf.registrations_open'),
                'retention_months' => config('jnuaf.privacy.retention_months'),
                'committee_retention_months' => config('jnuaf.privacy.committee_retention_months'),
                'notify_email' => config('jnuaf.notify_email'),
                'legal_controller' => config('jnuaf.legal.controller'),
                'legal_privacy_email' => config('jnuaf.legal.privacy_email'),
                'legal_publisher' => config('jnuaf.legal.publisher'),
                'legal_director' => config('jnuaf.legal.director'),
                'legal_host' => config('jnuaf.legal.host'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event_date' => ['required', 'date_format:Y-m-d'],
            'retention_months' => ['required', 'integer', 'min:1', 'max:60'],
            'committee_retention_months' => ['required', 'integer', 'min:1', 'max:60'],
            'notify_email' => ['nullable', 'email', 'max:150'],
            'legal_controller' => ['nullable', 'string', 'max:500'],
            'legal_privacy_email' => ['nullable', 'email', 'max:150'],
            'legal_publisher' => ['nullable', 'string', 'max:500'],
            'legal_director' => ['nullable', 'string', 'max:150'],
            'legal_host' => ['nullable', 'string', 'max:500'],
        ]);
        $data['registrations_open'] = $request->boolean('registrations_open') ? '1' : '0';

        Setting::store($data);
        Setting::applyToConfig();

        return back()->with('status', 'Paramètres enregistrés.');
    }
}
