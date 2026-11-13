<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckinController extends Controller
{
    public function index(Request $request): View
    {
        $recent = Registration::currentEdition()->visibleTo($request->user())
            ->whereNotNull('checked_in_at')->with('territory')
            ->orderByDesc('checked_in_at')->limit(15)->get();

        return view('admin.checkin.index', compact('recent'));
    }

    /** Ouvert directement par un téléphone qui scanne le QR code du badge. */
    public function token(Request $request, string $token): View
    {
        $registration = Registration::where('qr_token', $token)->with('territory')->first();

        return view('admin.checkin.result', $this->evaluate($request, $registration));
    }

    /**
     * Pointage : accepte le contenu du QR (URL ou jeton) ou le code imprimé sur le badge.
     * Répond en JSON pour le scanner caméra, sinon redirige.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $input = trim((string) $request->validate(['code' => ['required', 'string', 'max:255']])['code']);

        // Le QR encode l'URL de pointage : on en extrait le jeton final.
        $token = str_contains($input, '/') ? basename(parse_url($input, PHP_URL_PATH) ?: $input) : $input;

        $registration = Registration::where('qr_token', $token)
            ->orWhere('badge_code', strtoupper($input))
            ->with('territory')->first();

        $result = $this->evaluate($request, $registration);

        if ($result['state'] === 'ok') {
            $registration->forceFill(['checked_in_at' => now(), 'checked_in_by' => $request->user()->id])->save();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'state' => $result['state'],
                'message' => $result['message'],
                'name' => $registration?->fullName(),
                'profile' => $registration?->profile->label(),
                'territory' => $registration?->territory->city,
                'badge' => $registration?->badge_code,
                'accessibility' => $registration?->accessibility_needs,
            ], $result['state'] === 'ok' ? 200 : 422);
        }

        return redirect()->route('admin.checkin.index')->with('checkin', $result + [
            'name' => $registration?->fullName(),
            'badge' => $registration?->badge_code,
        ]);
    }

    /** @return array{state: string, message: string, registration: ?Registration} */
    private function evaluate(Request $request, ?Registration $registration): array
    {
        $user = $request->user();

        [$state, $message] = match (true) {
            $registration === null => ['unknown', 'Badge inconnu.'],
            ($registration->edition !== (string) config('jnuaf.edition') && ! $registration->isEventOnly()) => ['invalid', 'Badge d\'une autre édition.'],
            ! $user->isAdmin() && $registration->territory_id !== $user->territory_id => ['invalid', 'Badge rattaché au territoire de '.$registration->territory->city.'.'],
            $registration->isCancelled() => ['invalid', 'Inscription annulée.'],
            $registration->status === RegistrationStatus::Waitlisted => ['invalid', 'Sur liste d\'attente : aucune place confirmée.'],
            $registration->anonymized_at !== null => ['invalid', 'Badge expiré.'],
            $registration->checked_in_at !== null => ['already', 'Déjà entrée à '.$registration->checked_in_at->format('H:i').'.'],
            default => ['ok', 'Entrée validée.'],
        };

        return compact('state', 'message', 'registration');
    }
}
