<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

class GoogleDriveService
{
    private const FOLDER_MIME_TYPE = 'application/vnd.google-apps.folder';

    /**
     * @return array{url: string, created: bool}
     */
    public function findOrCreateFolder(string $parentLocation, string $folderName): array
    {
        $parentId = $this->extractFolderId($parentLocation);
        $token = $this->accessToken();
        $escapedName = str_replace(['\\', "'"], ['\\\\', "\\'"], $folderName);
        $escapedParentId = str_replace(['\\', "'"], ['\\\\', "\\'"], $parentId);

        $response = Http::withToken($token)
            ->acceptJson()
            ->get('https://www.googleapis.com/drive/v3/files', [
                'q' => "'{$escapedParentId}' in parents and name = '{$escapedName}' and mimeType = '".self::FOLDER_MIME_TYPE."' and trashed = false",
                'fields' => 'files(id,webViewLink)',
                'pageSize' => 1,
                'supportsAllDrives' => 'true',
                'includeItemsFromAllDrives' => 'true',
            ]);

        $this->ensureSuccessful($response, 'Unable to search Google Drive folders.');

        if ($folder = $response->json('files.0')) {
            return [
                'url' => $folder['webViewLink'] ?? $this->folderUrl($folder['id']),
                'created' => false,
            ];
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->post('https://www.googleapis.com/drive/v3/files?supportsAllDrives=true&fields=id,webViewLink', [
                'name' => $folderName,
                'mimeType' => self::FOLDER_MIME_TYPE,
                'parents' => [$parentId],
            ]);

        $this->ensureSuccessful($response, 'Unable to create a Google Drive folder.');

        $folderId = $response->json('id');

        if (blank($folderId)) {
            throw new RuntimeException('Google Drive did not return the created folder ID.');
        }

        return [
            'url' => $response->json('webViewLink') ?? $this->folderUrl($folderId),
            'created' => true,
        ];
    }

    public function extractFolderId(string $location): string
    {
        $location = trim($location);

        if (preg_match('~/folders/([^/?#]+)~', $location, $matches)) {
            return $matches[1];
        }

        if (preg_match('/[?&]id=([^&#]+)/', $location, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^[a-zA-Z0-9_-]+$/', $location)) {
            return $location;
        }

        throw new RuntimeException('The schedule does not contain a valid Google Drive folder URL.');
    }

    private function accessToken(): string
    {
        if (filled(config('services.google_drive.refresh_token'))) {
            return $this->oauthAccessToken();
        }

        $credentials = $this->credentials();
        $cacheKey = 'google-drive-token:'.sha1($credentials['client_email']);

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($credentials): string {
            $now = time();
            $header = $this->base64UrlEncode(json_encode([
                'alg' => 'RS256',
                'typ' => 'JWT',
            ], JSON_THROW_ON_ERROR));
            $claims = $this->base64UrlEncode(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/drive',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ], JSON_THROW_ON_ERROR));
            $unsignedToken = "{$header}.{$claims}";

            if (! openssl_sign($unsignedToken, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Unable to sign the Google Drive authentication token.');
            }

            $assertion = $unsignedToken.'.'.$this->base64UrlEncode($signature);
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);

            $this->ensureSuccessful($response, 'Google Drive authentication failed.');

            $accessToken = $response->json('access_token');

            if (blank($accessToken)) {
                throw new RuntimeException('Google Drive authentication did not return an access token.');
            }

            return $accessToken;
        });
    }

    private function oauthAccessToken(): string
    {
        $clientId = trim((string) config('services.google_drive.client_id'));
        $clientSecret = trim((string) config('services.google_drive.client_secret'));
        $refreshToken = trim((string) config('services.google_drive.refresh_token'));

        if ($clientId === '' || $clientSecret === '' || $refreshToken === '') {
            throw new RuntimeException('Google Drive OAuth credentials are incomplete.');
        }

        return Cache::remember('google-drive-oauth-token:'.sha1($clientId), now()->addMinutes(50), function () use ($clientId, $clientSecret, $refreshToken): string {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            $this->ensureSuccessful($response, 'Google Drive OAuth authentication failed.');

            $accessToken = $response->json('access_token');

            if (blank($accessToken)) {
                throw new RuntimeException('Google Drive OAuth did not return an access token.');
            }

            return $accessToken;
        });
    }

    /**
     * @return array{client_email: string, private_key: string}
     */
    private function credentials(): array
    {
        $configuredLocation = trim((string) config('services.google_drive.service_account_json'));

        if ($configuredLocation === '') {
            throw new RuntimeException('GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON is not configured.');
        }

        $path = is_file($configuredLocation) ? $configuredLocation : base_path($configuredLocation);

        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('The configured Google Drive service-account file cannot be read.');
        }

        try {
            $credentials = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RuntimeException('The Google Drive service-account file contains invalid JSON.');
        }

        if (blank($credentials['client_email'] ?? null) || blank($credentials['private_key'] ?? null)) {
            throw new RuntimeException('The Google Drive service-account file is missing required credentials.');
        }

        return [
            'client_email' => $credentials['client_email'],
            'private_key' => $credentials['private_key'],
        ];
    }

    private function ensureSuccessful(Response $response, string $fallbackMessage): void
    {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException($response->json('error.message') ?: $fallbackMessage);
    }

    private function folderUrl(string $folderId): string
    {
        return "https://drive.google.com/drive/folders/{$folderId}";
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
