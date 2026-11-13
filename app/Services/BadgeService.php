<?php

namespace App\Services;

use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

class BadgeService
{
    /**
     * Contenu du QR code : l'URL de pointage portant un jeton opaque.
     * Aucune donnée personnelle n'y figure ; scanner sans être connecté mène à l'écran de connexion.
     */
    public function qrPayload(Registration $registration): string
    {
        return route('admin.checkin.token', $registration->qr_token);
    }

    public function qrDataUri(Registration $registration, int $size = 320): string
    {
        return (new Builder(
            writer: new PngWriter,
            data: $this->qrPayload($registration),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 8,
        ))->build()->getDataUri();
    }

    public function pdf(Registration $registration): string
    {
        $registration->loadMissing('territory');

        return Pdf::loadView('pdf.badge', [
            'registration' => $registration,
            'qr' => $this->qrDataUri($registration),
        ])->setOption('isFontSubsettingEnabled', true)
            ->setPaper([0, 0, 297.64, 419.53]) // A6 portrait, en points
            ->output();
    }

    public function filename(Registration $registration): string
    {
        return "badge-{$registration->badge_code}.pdf";
    }

    /** Attestation de participation (A4 paysage), réservée aux personnes pointées à l'entrée. */
    public function attestationPdf(Registration $registration): string
    {
        $registration->loadMissing('territory');

        return Pdf::loadView('pdf.attestation', [
            'registration' => $registration,
            'eventDate' => $registration->eventDate(),
            'logo' => $this->logoDataUri(),
            'signature' => $this->signatureDataUri(),
            'signer' => config('jnuaf_signature'),
        ])->setOption('isFontSubsettingEnabled', true)
            ->setPaper('a4', 'landscape')
            ->output();
    }


    private function logoDataUri(): ?string

    {

        $path = public_path('images/logo.jpg');



        return is_file($path)

            ? 'data:image/jpeg;base64,'.base64_encode((string) file_get_contents($path))

            : null;

    }



    private function signatureDataUri(): ?string

    {

        $file = (string) config('jnuaf_signature.file');



        return $file !== '' && is_file($file)

            ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($file))

            : null;

    }


    public function attestationFilename(Registration $registration): string
    {
        return 'attestation-'.\Illuminate\Support\Str::slug($registration->eventTitle()).'-'.\Illuminate\Support\Str::slug($registration->fullName()).'.pdf';
    }
}
