<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class TotpService
{
    private const PERIOD = 30;
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(20));
    }

    public function uri(User $user, string $secret): string
    {
        $issuer = 'JNUAF.ORG';

        return 'otpauth://totp/'.rawurlencode($issuer).':'.rawurlencode($user->email)
            .'?secret='.$secret.'&issuer='.rawurlencode($issuer).'&algorithm=SHA1&digits=6&period=30';
    }

    public function qrSvg(string $uri): ?string
    {
        if (! class_exists(\BaconQrCode\Writer::class)) {
            return null;
        }

        try {
            $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(220, 1),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
            );
            $svg = (new \BaconQrCode\Writer($renderer))->writeString($uri);

            return preg_replace('/^<\?xml.*?\?>\s*/s', '', $svg);
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    public function pendingSecret(Session $session): ?string
    {
        $encrypted = $session->get('totp_pending');
        if (! $encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    /** Retourne le pas de temps correspondant au code, ou null. Fenetre : -1, 0, +1 (30 s). */
    public function match(string $secret, string $code, ?int $afterStep = null): ?int
    {
        $code = preg_replace('/\s+/', '', $code);
        if (! preg_match('/^\d{6}$/', $code)) {
            return null;
        }

        $key = $this->base32Decode($secret);
        if ($key === '') {
            return null;
        }

        $now = intdiv(time(), self::PERIOD);
        $found = null;

        foreach ([$now - 1, $now, $now + 1] as $step) {
            if ($afterStep !== null && $step <= $afterStep) {
                continue;
            }
            if (hash_equals($this->hotp($key, $step), $code)) {
                $found = $step;
            }
        }

        return $found;
    }

    /** Verification a la connexion, avec protection contre le rejeu. */
    public function verify(User $user, string $code): bool
    {
        $secret = $user->totp_secret;
        if (! $secret) {
            return false;
        }

        $last = $user->totp_last_step !== null ? (int) $user->totp_last_step : null;
        $step = $this->match($secret, $code, $last);
        if ($step === null) {
            return false;
        }

        $updated = DB::table('users')->where('id', $user->id)
            ->where(function ($query) use ($step) {
                $query->whereNull('totp_last_step')->orWhere('totp_last_step', '<', $step);
            })
            ->update(['totp_last_step' => $step]);

        return $updated === 1;
    }

    private function hotp(string $key, int $counter): string
    {
        $hash = hash_hmac('sha1', pack('N*', 0, $counter), $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $binary): string
    {
        $bits = '';
        foreach (str_split($binary) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }

        return $out;
    }

    private function base32Decode(string $text): string
    {
        $text = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $text));

        $bits = '';
        foreach (str_split($text) as $char) {
            $position = strpos(self::ALPHABET, $char);
            if ($position === false) {
                return '';
            }
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }
}
