<?php

// Vérification locale, sans envoi d'email et sans écriture en base.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$url = route('admin.partner-portal.index');
$partnerUrl = route('partner-portal.landing', str_repeat('a', 64));
$fixtures = [
    ['kind' => 'invitation', 'name' => 'Partenaire test', 'email' => 'test@example.test', 'url' => $partnerUrl],
    ['kind' => 'code', 'name' => 'Partenaire test', 'code' => '123456', 'valid_minutes' => 5],
    ['kind' => 'partner_revoked', 'name' => 'Partenaire test', 'reason' => 'MOTIF_INTERNE_TEST', 'url' => $url],
    ['kind' => 'admin_revoked', 'name' => 'Partenaire test', 'email' => 'test@example.test', 'reason' => 'Révocation de test', 'url' => $url],
    ['kind' => 'admin_document', 'name' => 'Partenaire test', 'email' => 'test@example.test', 'document' => 'Document test', 'action' => 'Consultation', 'date' => '22/10/2026', 'url' => $url],
    ['kind' => 'generic_admin', 'url' => $url],
    ['kind' => 'generic_partner'],
];

try {
    foreach ($fixtures as $data) {
        $html = (new App\Mail\PartnerPortalMessage('Test local', $data))->render();
        $text = view('mail.partner-portal-text', ['data' => $data])->render();
        if (! str_contains($html, '#14592A') || trim($text) === '') {
            throw new RuntimeException('Rendu incomplet : '.$data['kind']);
        }
        if ($data['kind'] === 'partner_revoked'
            && (str_contains($html, 'MOTIF_INTERNE_TEST') || str_contains($text, 'MOTIF_INTERNE_TEST')
                || str_contains($html, $url) || str_contains($text, $url))) {
            throw new RuntimeException('Détail interne visible dans le message partenaire.');
        }
        echo $data['kind']." : HTML et texte OK\n";
    }
    $hostile = $fixtures[0];
    $hostile['name'] = '<script>MAIL_TEST</script>';
    $html = (new App\Mail\PartnerPortalMessage('Test local', $hostile))->render();
    if (str_contains($html, '<script>MAIL_TEST</script>')) {
        throw new RuntimeException('Le nom doit être échappé dans le HTML.');
    }
    echo "Contrôle d’échappement HTML : OK\nAucun email envoyé.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage().PHP_EOL);
    exit(1);
}
