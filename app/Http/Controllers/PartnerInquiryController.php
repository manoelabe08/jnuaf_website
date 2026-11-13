<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePartnerInquiryRequest;
use App\Mail\AdminAlert;
use App\Mail\PartnerInquiryReceived;
use App\Models\PartnerInquiry;
use App\Services\AdminNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;

class PartnerInquiryController extends Controller
{
    public function store(StorePartnerInquiryRequest $request, AdminNotifier $notifier): RedirectResponse
    {
        $inquiry = PartnerInquiry::create([
            ...Arr::except($request->validated(), ['consent', 'website']),
            'email' => mb_strtolower($request->validated('email')),
            'consented_at' => now(),
        ]);

        Mail::to($inquiry->email)->send(new PartnerInquiryReceived($inquiry));

        $notifier->send(new AdminAlert(
            title: 'nouvelle demande de partenariat',
            intro: "{$inquiry->organization} souhaite devenir partenaire de #JNUAF.ORG.",
            details: [
                'Contact' => trim($inquiry->contact_name.' '.($inquiry->job_title ? "({$inquiry->job_title})" : '')),
                'Email' => $inquiry->email,
                'Téléphone' => $inquiry->phone,
                'Pays' => $inquiry->country,
                'Formes souhaitées' => implode(', ', $inquiry->typeLabels()),
                'Projet' => $inquiry->message,
            ],
            url: route('admin.partners.show', $inquiry),
        ));

        return redirect()->to(route('home').'#partenaires')
            ->with('partner', ['organization' => $inquiry->organization]);
    }
}
