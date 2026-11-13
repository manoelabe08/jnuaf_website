<?php
namespace App\Http\Controllers;

use App\Models\PartnerShare;
use App\Services\PartnerPortalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartnerPortalController extends Controller
{
    private function headers($response)
    {
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        return $response;
    }

    private function logged(Request $request, PartnerShare $share): bool
    {
        $key = 'partner_portal.auth.'.$share->id;
        $auth = $request->session()->get($key);
        if (! $auth || $share->status !== 'active' || $auth['generation'] !== $share->generation
            || $auth['email'] !== $share->email || time() - $auth['issued_at'] >= config('partner_portal.session_seconds')
            || time() - $auth['last_activity'] >= config('partner_portal.idle_seconds')) {
            $request->session()->forget($key); return false;
        }
        $auth['last_activity'] = time();
        $request->session()->put($key, $auth);
        return true;
    }

    public function landing(Request $request, string $token, PartnerPortalService $portal)
    {
        $share = $portal->findToken($token);
        if ($share->status === 'active' && ! $share->first_opened_at) {
            DB::transaction(function () use ($share, $portal) {
                $share = PartnerShare::lockForUpdate()->findOrFail($share->id);
                if (! $share->first_opened_at) {
                    $share->update(['first_opened_at' => now()]);
                    $portal->event($share, 'page_du_lien_appelée', 'Peut être un robot de messagerie ; ce n’est pas une connexion.');
                }
            });
        }
        if ($this->logged($request, $share)) {
            return $this->headers(redirect()->route('partner-portal.documents', $token));
        }
        $loginEmail = $request->session()->get('partner_portal.email.'.$share->id);
        $requested = $request->session()->get('partner_portal.requested.'.$share->id, false);
        $authorized = $loginEmail && hash_equals($share->email, $loginEmail) && $portal->knownEmail($loginEmail);
        return $this->headers(response()->view('partner-portal.login', [
            'share' => $share, 'token' => $token, 'requested' => $requested,
            'loginEmail' => $loginEmail,
            // Des délais de façade identiques sont utilisés pour un email non autorisé.
            'expiry' => $authorized ? $share->code_expires_at?->getTimestamp()
                : $request->session()->get('partner_portal.facade_expiry.'.$share->id),
            'nextCode' => $authorized ? $share->next_code_at?->getTimestamp()
                : $request->session()->get('partner_portal.facade_next.'.$share->id),
            'pending' => $requested && $authorized && $share->code_hash && ! $share->code_expires_at,
            'serverTime' => time(),
        ]));
    }

   public function requestCode(
    Request $request,
    string $token,
    PartnerPortalService $portal
) {
    $data = $request->validate([
        'email' => ['required', 'email', 'max:255'],
    ]);

    $share = $portal->findToken($token);
    $email = $portal->normalize($data['email']);

    if ($share->status === 'active') {
        try {
            $portal->requestCode(
                $share,
                $email,
                $request->ip()
            );
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    $request->session()->put(
        'partner_portal.email.'.$share->id,
        $email
    );

    $request->session()->put(
        'partner_portal.requested.'.$share->id,
        true
    );

    $request->session()->put(
        'partner_portal.facade_expiry.'.$share->id,
        time() + 300
    );

    $request->session()->put(
        'partner_portal.facade_next.'.$share->id,
        time() + 390
    );

    \Illuminate\Support\defer(function () {
        try {
            app(PartnerPortalService::class)
                ->processMaintenance();
        } catch (\Throwable $exception) {
            report($exception);
        }
    });

    return $this->headers(
        redirect()
            ->route('partner-portal.landing', $token)
            ->with(
                'status',
                'Si cette adresse est autorisée, votre demande de code est prise en compte.'
            )
    );
}

    public function verify(Request $request, string $token, PartnerPortalService $portal)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:64']]);
        $share = $portal->findToken($token);
        $email = (string) $request->session()->get('partner_portal.email.'.$share->id, '');
        if (! $portal->verifyCode($share, $email, trim($data['code']))) {
            return $this->headers(redirect()->route('partner-portal.landing', $token)
                ->withErrors(['code' => 'Code incorrect, expiré ou accès non autorisé.']));
        }
        $share->refresh();
        $request->session()->regenerate();
        $request->session()->put('partner_portal.auth.'.$share->id, [
            'generation' => $share->generation, 'email' => $share->email,
            'issued_at' => time(), 'last_activity' => time(),
        ]);
        return $this->headers(redirect()->route('partner-portal.documents', $token));
    }

    public function documents(Request $request, string $token, PartnerPortalService $portal)
    {
        $share = $portal->findToken($token);
        if (! $this->logged($request, $share)) {
            return $this->headers(redirect()->route('partner-portal.landing', $token));
        }
        if (! $portal->knownEmail($share->email)) { abort(403); }
        return $this->headers(response()->view('partner-portal.documents', [
            'share' => $share, 'token' => $token,
            'documents' => $share->documents()->where('active', true)->get(),
        ]));
    }

    public function file(Request $request, string $token, int $document, string $mode, PartnerPortalService $portal)
    {
        $share = $portal->findToken($token);
        abort_unless($this->logged($request, $share) && $portal->knownEmail($share->email), 403);
        $document = $share->documents()->where('active', true)->findOrFail($document);
        $path = $portal->path($document);
        $portal->accessEvent($share, $document, $mode === 'lire' ? 'lecture_demandée' : 'téléchargement_demandé');
        return $this->headers(response()->download($path, $document->download_name,
            ['Content-Type' => 'application/pdf'], $mode === 'lire' ? 'inline' : 'attachment'));
    }

    public function logout(Request $request, string $token, PartnerPortalService $portal)
    {
        $share = $portal->findToken($token);
        $request->session()->forget('partner_portal.auth.'.$share->id);
        $request->session()->regenerate();
        return $this->headers(redirect()->route('partner-portal.landing', $token));
    }
}
