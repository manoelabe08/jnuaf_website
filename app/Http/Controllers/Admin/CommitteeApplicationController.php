<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\CommitteeApplication;
use App\Support\CsvExport;
use App\Mail\CommitteeApplicationDecision;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommitteeApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $applications = CommitteeApplication::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('country'), fn ($q) => $q->where('country', $request->string('country')))
            ->when($request->filled('pole'), fn ($q) => $q->where('pole', $request->string('pole')))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.committee.index', [
            'applications' => $applications,
            'statuses' => ApplicationStatus::options(),
            'filters' => $request->only(['status', 'country', 'pole']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = CommitteeApplication::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('country'), fn ($q) => $q->where('country', $request->string('country')))
            ->when($request->filled('pole'), fn ($q) => $q->where('pole', $request->string('pole')));

        return CsvExport::download($request, 'candidatures-comite', $query,
            ['Nom', 'Email', 'WhatsApp', 'Pays', 'Pôle', 'Statut', 'Notes', 'Reçue le', 'Décision le'],
            fn (CommitteeApplication $a) => [
                $a->full_name, $a->email, $a->phone, $a->country, $a->pole, $a->status->label(),
                $a->notes, $a->created_at->format('d/m/Y H:i'), $a->reviewed_at?->format('d/m/Y H:i'),
            ],
            $request->only(['status', 'country', 'pole']));
    }

    public function show(CommitteeApplication $application): View
    {
        return view('admin.committee.show', [
            'application' => $application->load('reviewer'),
            'statuses' => ApplicationStatus::options(),
        ]);
    }

  public function update(
    Request $request,
    CommitteeApplication $application
): RedirectResponse {
    $data = $request->validate([
        'status' => ['required', Rule::enum(ApplicationStatus::class)],
        'notes' => ['nullable', 'string', 'max:3000'],
    ]);

    $application->forceFill($data + [
        'reviewed_by' => $request->user()->id,
        'reviewed_at' => now(),
    ])->save();

    $statusChanged = $application->wasChanged('status');

    $decision = $application->status instanceof ApplicationStatus
        ? $application->status
        : ApplicationStatus::from((string) $application->status);

    if (
        $statusChanged
        && in_array($decision, [
            ApplicationStatus::Accepted,
            ApplicationStatus::Rejected,
        ], true)
    ) {
        try {
            Mail::to($application->email)->send(
                new CommitteeApplicationDecision($application, $decision)
            );
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with(
                'status',
                'La décision a été enregistrée, mais l’envoi de l’email a échoué. Consultez le journal Laravel.'
            );
        }

        return back()->with(
            'status',
            'Candidature mise à jour. L’email de décision a été transmis au serveur de messagerie.'
        );
    }

    return back()->with('status', 'Candidature mise à jour.');
}

    public function bulk(Request $request): RedirectResponse

    {

        $data = $request->validate([

            'status' => ['required', 'in:reviewing,accepted,rejected'],

            'ids' => ['required', 'array', 'min:1', 'max:20'],

            'ids.*' => ['integer'],

        ]);

        $target = ApplicationStatus::from($data['status']);

        $done = 0;

        $same = 0;

        $mailFail = 0;

        foreach (CommitteeApplication::whereIn('id', array_unique($data['ids']))->get() as $application) {

            $current = $application->status instanceof ApplicationStatus ? $application->status : ApplicationStatus::from((string) $application->status);

            if ($current === $target) {

                $same++;

                continue;

            }

            $application->forceFill(['status' => $target, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()])->save();

            $done++;

            if (in_array($target, [ApplicationStatus::Accepted, ApplicationStatus::Rejected], true)) {

                try {

                    Mail::to($application->email)->send(new CommitteeApplicationDecision($application, $target));

                } catch (\Throwable $e) {

                    report($e);

                    $mailFail++;

                }

                usleep(250000);

            }

        }

        \Illuminate\Support\Facades\Log::info('Candidatures comite : action en masse', ['statut' => $data['status'], 'ids' => array_values(array_unique($data['ids'])), 'modifiees' => $done, 'inchangees' => $same, 'mails_en_echec' => $mailFail, 'acteur_id' => $request->user()->id]);

        $msg = $done.' candidature(s) modifiee(s)';

        if ($same) {

            $msg .= ', '.$same.' deja dans ce statut';

        }

        if ($mailFail) {

            $msg .= ', '.$mailFail.' mail(s) de decision en echec (voir le journal)';

        }

        return back()->with('status', $msg.'.');

    }

    public function download(CommitteeApplication $application, string $document): StreamedResponse
    {
        $path = match ($document) {
            'cv' => $application->cv_path,
            'lettre' => $application->letter_path,
            default => abort(404),
        };

        abort_unless(Storage::disk('local')->exists($path), 404);

        $name = Str::slug($application->full_name).'-'.$document.'.pdf';

        return Storage::disk('local')->response($path, $name, ['Content-Type' => 'application/pdf']);
    }

    public function destroy(CommitteeApplication $application): RedirectResponse
    {
        $application->delete();

        return redirect()->route('admin.committee.index')->with('status', 'Candidature et pièces jointes supprimées.');
    }
}
