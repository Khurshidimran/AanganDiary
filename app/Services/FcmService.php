<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin wrapper around Firebase Cloud Messaging's HTTP v1 API. Auth is a
 * service-account JWT-bearer OAuth2 flow (FCM's legacy server-key API is
 * deprecated) — implemented directly with PHP's native openssl_sign() rather
 * than pulling in a Firebase SDK, matching ShopifyClient's "thin wrapper, no
 * heavy dependency" approach. Fails silently (logs, doesn't throw) since a
 * push notification failure should never block the underlying business
 * operation (e.g. assigning a delivery).
 */
class FcmService
{
    public function isConfigured(): bool
    {
        $path = config('services.fcm.credentials_path');

        return filled(config('services.fcm.project_id')) && filled($path) && file_exists($path);
    }

    /**
     * @param  array<string, string>  $data
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        try {
            $accessToken = $this->accessToken();
            $projectId = config('services.fcm.project_id');

            Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => ['title' => $title, 'body' => $body],
                        'data' => $data,
                    ],
                ])
                ->throw();
        } catch (Throwable $e) {
            Log::warning('FCM push notification failed', ['error' => $e->getMessage()]);
        }
    }

    private function accessToken(): string
    {
        return Cache::remember('fcm_access_token', 3500, function () {
            $credentials = json_decode(file_get_contents(config('services.fcm.credentials_path')), true);
            $now = time();

            $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->base64UrlEncode(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            openssl_sign("{$header}.{$claims}", $signature, $credentials['private_key'], 'SHA256');
            $jwt = "{$header}.{$claims}.".$this->base64UrlEncode($signature);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ])->throw();

            return $response->json('access_token');
        });
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
