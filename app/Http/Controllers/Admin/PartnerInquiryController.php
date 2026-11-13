<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\PartnerInquiry;
use App\Support\CsvExport;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PartnerInquiryController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.partners.index', [
            'inquiries' => PartnerInquiry::query()
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->latest()->paginate(25)->withQueryString(),
            'statuses' => InquiryStatus::options(),
            'filters' => $request->only('status'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = PartnerInquiry::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));

        return CsvExport::download($request, 'demandes-partenaires', $query,
            ['Organisation', 'Contact', 'Fonction', 'Email', 'Téléphone', 'Pays', 'Formes souhaitées', 'Projet', 'Statut', 'Notes', 'Reçue le'],
            fn (PartnerInquiry $i) => [
                $i->organization, $i->contact_name, $i->job_title, $i->email, $i->phone, $i->country,
                implode(', ', $i->typeLabels()), $i->message, $i->status->label(), $i->notes, $i->created_at->format('d/m/Y H:i'),
            ],
            $request->only('status'));
    }

    public function show(PartnerInquiry $inquiry): View
    {
        return view('admin.partners.show', [
            'inquiry' => $inquiry->load('handler'),
            'statuses' => InquiryStatus::options(),
        ]);
    }

    public function update(Request $request, PartnerInquiry $inquiry): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(InquiryStatus::class)],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $inquiry->forceFill($data + ['handled_by' => $request->user()->id, 'handled_at' => now()])->save();

        return back()->with('status', 'Demande mise à jour.');
    }

    public function bulk(Request $request): RedirectResponse

    {

        $data = $request->validate([

            'status' => ['required', 'in:new,discussion,signed,declined'],

            'ids' => ['required', 'array', 'min:1', 'max:50'],

            'ids.*' => ['integer'],

        ]);

        $target = InquiryStatus::from($data['status']);

        $done = 0;

        $same = 0;

        foreach (PartnerInquiry::whereIn('id', array_unique($data['ids']))->get() as $inquiry) {

            if ($inquiry->status->value === $data['status']) {

                $same++;

                continue;

            }

            $inquiry->forceFill(['status' => $target, 'handled_by' => $request->user()->id, 'handled_at' => now()])->save();

            $done++;

        }

        \Illuminate\Support\Facades\Log::info('Demandes partenaires : action en masse', ['statut' => $data['status'], 'ids' => array_values(array_unique($data['ids'])), 'modifiees' => $done, 'inchangees' => $same, 'acteur_id' => $request->user()->id]);

        $msg = $done.' demande(s) modifiee(s)';

        if ($same) {

            $msg .= ', '.$same.' deja dans ce statut';

        }

        return back()->with('status', $msg.'.');

    }

    public function destroy(PartnerInquiry $inquiry): RedirectResponse
    {
        $inquiry->delete();

        return redirect()->route('admin.partners.index')->with('status', 'Demande supprimée.');
    }
}
