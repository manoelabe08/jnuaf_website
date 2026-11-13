<?php
// Contrôle de rendu et de structure, sans email envoyé et sans insertion en base.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    foreach (['admin.newsletter.index','admin.newsletter.queue','admin.newsletter.preview','newsletter.web','newsletter.unsubscribe','newsletter.unsubscribe.confirm'] as $name) {
        if (! Illuminate\Support\Facades\Route::has($name)) { throw new RuntimeException('Route absente : '.$name); }
    }
    $campaign = new App\Models\NewsletterCampaign([
        'title' => 'Contrôle local', 'subject' => 'Newsletter de test', 'body' => "Bonjour,\n<script>NEWSLETTER_TEST</script>\nDeuxième paragraphe.",
        'sources' => ['manual'], 'status' => 'draft',
    ]);
    $campaign->setRelation('attachments', new Illuminate\Database\Eloquent\Collection());
    $token = str_repeat('a', 64);
    $delivery = new App\Models\NewsletterDelivery([
        'email' => 'test@example.test', 'token_hash' => hash('sha256', $token),
        'token_ciphertext' => Illuminate\Support\Facades\Crypt::encryptString($token),
    ]);
    $mail = new App\Mail\NewsletterMessage($campaign, $delivery);
    $html = $mail->render();
    if (str_contains($html, '<script>NEWSLETTER_TEST</script>')) { throw new RuntimeException('Le contenu HTML doit être échappé.'); }
    if (! str_contains($html, 'Lire dans votre navigateur') && ! str_contains($html, 'Lire dans mon navigateur')) {
        throw new RuntimeException('Le composant mail-layout doit accepter webUrl et afficher le lien de version web.');
    }
    if (! str_contains($html, route('newsletter.unsubscribe', $token))) {
        throw new RuntimeException('Le composant mail-layout doit afficher son slot footer (lien de désinscription absent).');
    }
    $text = view('mail.newsletter-text', [
        'campaign' => $campaign, 'webUrl' => route('newsletter.web', $token),
        'unsubscribeUrl' => route('newsletter.unsubscribe', $token),
    ])->render();
    if (! str_contains($text, 'Me désinscrire')) { throw new RuntimeException('Version texte incomplète.'); }
    $mail->headers();
    $mail->attachments();
    foreach (['newsletters','newsletter_campaigns','newsletter_attachments','newsletter_deliveries'] as $table) {
        if (! Illuminate\Support\Facades\Schema::hasTable($table)) { throw new RuntimeException('Table absente : '.$table.' (installer SQL ou migration).'); }
    }
    app(App\Services\NewsletterService::class)->root();
    app(App\Services\NewsletterService::class)->smtp();
    echo "Routes, modèles HTML/texte, échappement, liens, tables et dossier privé : OK.\n";
    echo "Configuration du transport chargée ; connexion SMTP non testée.\nAucun email envoyé et aucune insertion en base.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage().PHP_EOL);
    exit(1);
}
