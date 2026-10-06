<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ApnsVoipService
{
    private const TOKEN_TTL_SECONDS = 3000;

    private static ?string $cachedJwt = null;
    private static int $cachedAt = 0;
    private static ?string $cachedFingerprint = null;

    public function sendIncomingCall(string $deviceToken, array $data): bool
    {
        if (!config('services.apns.enabled')) {
            return false;
        }

        $jwt = $this->jwt();
        $bundleId = (string) config('services.apns.bundle_id');

        if (!$jwt || !$bundleId) {
            Log::warning('[APNs VoIP] Credentials are not configured');
            return false;
        }

        $host = config('services.apns.environment') === 'sandbox'
            ? 'https://api.sandbox.push.apple.com'
            : 'https://api.push.apple.com';

        try {
            $response = Http::withToken($jwt)
                ->withHeaders([
                    'apns-push-type' => 'voip',
                    'apns-topic' => $bundleId . '.voip',
                    'apns-priority' => '10',
                    'apns-expiration' => '0',
                ])
                ->withOptions(['version' => 2.0])
                ->connectTimeout(1.5)
                ->timeout(3)
                ->post($host . '/3/device/' . $deviceToken, [
                    'aps' => ['content-available' => 1],
                ] + $data);

            if ($response->status() !== 200) {
                Log::error('[APNs VoIP] Delivery failed', [
                    'status' => $response->status(),
                    'reason' => $response->json('reason'),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $error) {
            Log::error('[APNs VoIP] Request failed', ['error' => $error->getMessage()]);
            return false;
        }
    }

    private function jwt(): ?string
    {
        $teamId = (string) config('services.apns.team_id');
        $keyId = (string) config('services.apns.key_id');
        $configuredPath = (string) config('services.apns.private_key_path');

        if (!$teamId || !$keyId || !$configuredPath) {
            return null;
        }

        $path = $this->absolutePath($configuredPath);
        $fingerprint = implode(':', [$teamId, $keyId, $path]);
        $now = time();

        if (
            self::$cachedJwt
            && self::$cachedFingerprint === $fingerprint
            && $now - self::$cachedAt < self::TOKEN_TTL_SECONDS
        ) {
            return self::$cachedJwt;
        }

        try {
            $privateKeyContents = @file_get_contents($path);
            if ($privateKeyContents === false) {
                throw new RuntimeException('Private key file is unavailable');
            }

            $privateKey = openssl_pkey_get_private($privateKeyContents);
            if ($privateKey === false) {
                throw new RuntimeException('Private key is invalid');
            }

            $header = $this->base64UrlEncode(json_encode([
                'alg' => 'ES256',
                'kid' => $keyId,
            ], JSON_THROW_ON_ERROR));
            $claims = $this->base64UrlEncode(json_encode([
                'iss' => $teamId,
                'iat' => $now,
            ], JSON_THROW_ON_ERROR));
            $unsignedToken = $header . '.' . $claims;

            if (!openssl_sign($unsignedToken, $derSignature, $privateKey, OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Unable to sign APNs token');
            }

            $jwt = $unsignedToken . '.' . $this->base64UrlEncode(
                $this->derSignatureToJose($derSignature)
            );

            self::$cachedJwt = $jwt;
            self::$cachedAt = $now;
            self::$cachedFingerprint = $fingerprint;

            return $jwt;
        } catch (\Throwable $error) {
            Log::error('[APNs VoIP] Token generation failed', ['error' => $error->getMessage()]);
            return null;
        }
    }

    private function absolutePath(string $path): string
    {
        if (preg_match('/^(?:[A-Za-z]:[\\\\\/]|\/)/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function derSignatureToJose(string $signature): string
    {
        $offset = 0;
        if ($this->readByte($signature, $offset) !== 0x30) {
            throw new RuntimeException('Invalid ECDSA signature sequence');
        }
        $this->readDerLength($signature, $offset);

        if ($this->readByte($signature, $offset) !== 0x02) {
            throw new RuntimeException('Invalid ECDSA signature R value');
        }
        $rLength = $this->readDerLength($signature, $offset);
        $r = substr($signature, $offset, $rLength);
        $offset += $rLength;

        if ($this->readByte($signature, $offset) !== 0x02) {
            throw new RuntimeException('Invalid ECDSA signature S value');
        }
        $sLength = $this->readDerLength($signature, $offset);
        $s = substr($signature, $offset, $sLength);

        return $this->normalizeInteger($r) . $this->normalizeInteger($s);
    }

    private function readDerLength(string $value, int &$offset): int
    {
        $length = $this->readByte($value, $offset);
        if (($length & 0x80) === 0) {
            return $length;
        }

        $byteCount = $length & 0x7f;
        if ($byteCount < 1 || $byteCount > 4) {
            throw new RuntimeException('Invalid DER length');
        }

        $length = 0;
        for ($index = 0; $index < $byteCount; $index++) {
            $length = ($length << 8) | $this->readByte($value, $offset);
        }
        return $length;
    }

    private function readByte(string $value, int &$offset): int
    {
        if ($offset >= strlen($value)) {
            throw new RuntimeException('Unexpected end of DER value');
        }

        return ord($value[$offset++]);
    }

    private function normalizeInteger(string $value): string
    {
        $value = ltrim($value, "\0");
        if (strlen($value) > 32) {
            $value = substr($value, -32);
        }

        return str_pad($value, 32, "\0", STR_PAD_LEFT);
    }
}
