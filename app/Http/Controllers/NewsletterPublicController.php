<?php
namespace App\Http\Controllers;
use App\Models\NewsletterAttachment;
use App\Services\NewsletterService;
use Illuminate\Http\Request;

class NewsletterPublicController extends Controller
{
    private function secure($response)
    {
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        return $response;
    }
    private function readable($delivery): void
    {
        abort_unless($delivery->campaign->status !== 'draft'
            && $delivery->campaign->web_expires_at
            && $delivery->campaign->web_expires_at->isFuture(), 410, 'La version web de cette newsletter a expiré.');
    }
    public function show(string $token, NewsletterService $service)
    {
        $delivery = $service->delivery($token); $this->readable($delivery);
        return $this->secure(response()->view('newsletter.web', compact('delivery', 'token')));
    }
    public function file(string $token, NewsletterAttachment $attachment, NewsletterService $service)
    {
        $delivery = $service->delivery($token); $this->readable($delivery);
        abort_unless($attachment->newsletter_campaign_id == $delivery->newsletter_campaign_id, 404);
        return $this->secure(response()->download($service->attachmentPath($attachment), $attachment->download_name));
    }
    public function unsubscribe(string $token, NewsletterService $service)
    {
        $delivery = $service->delivery($token);
        return $this->secure(response()->view('newsletter.unsubscribe', compact('delivery', 'token')));
    }
    public function confirm(string $token, NewsletterService $service)
    {
        $service->unsubscribe($service->delivery($token));
        return $this->secure(redirect()->route('newsletter.unsubscribe', $token)->with('newsletter_status', 'Votre désinscription a bien été enregistrée.'));
    }
}
