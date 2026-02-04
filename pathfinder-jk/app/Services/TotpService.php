<?php

declare(strict_types=1);

namespace App\Services;

/**
 * TOTP (Time-based One-Time Password) služba pro authenticator aplikace
 * Implementace RFC 6238
 */
final class TotpService
{
    private const DIGITS = 6;
    private const PERIOD = 30; // sekundy
    private const ALGORITHM = 'sha1';

    /**
     * Vygeneruje nový tajný klíč (Base32 encoded)
     */
    public function generateSecret(int $length = 16): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Ověří TOTP kód
     */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = str_pad($code, self::DIGITS, '0', STR_PAD_LEFT);
        $timestamp = time();

        // Kontrola aktuálního a okolních časových oken
        for ($i = -$window; $i <= $window; $i++) {
            $checkTime = $timestamp + ($i * self::PERIOD);
            if ($this->generateCode($secret, $checkTime) === $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vygeneruje TOTP kód pro daný čas
     */
    public function generateCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $counter = (int) floor($timestamp / self::PERIOD);

        // Dekóduj Base32 secret
        $secretBytes = $this->base32Decode($secret);

        // Counter jako 8-byte big-endian
        $counterBytes = pack('N*', 0, $counter);

        // HMAC-SHA1
        $hash = hash_hmac(self::ALGORITHM, $counterBytes, $secretBytes, true);

        // Dynamic truncation
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binary = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        $otp = $binary % pow(10, self::DIGITS);

        return str_pad((string) $otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Vygeneruje URL pro QR kód (otpauth://)
     */
    public function getQrCodeUrl(string $secret, string $email, string $issuer = 'Jižní Kříž'): string
    {
        $params = [
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => strtoupper(self::ALGORITHM),
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ];

        $label = rawurlencode($issuer . ':' . $email);

        return 'otpauth://totp/' . $label . '?' . http_build_query($params);
    }

    /**
     * Dekóduje Base32 string
     */
    private function base32Decode(string $input): string
    {
        $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper($input);
        $input = str_replace('=', '', $input);

        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0; $i < strlen($input); $i++) {
            $char = $input[$i];
            $val = strpos($map, $char);

            if ($val === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}
