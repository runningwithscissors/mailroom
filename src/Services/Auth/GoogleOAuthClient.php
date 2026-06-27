<?php

namespace BisonDigital\Mailroom\Services\Auth;

use RuntimeException;

class GoogleOAuthClient
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public function serviceAccountToken(string $clientEmail, string $privateKey, string $delegatedUser, string $scope): array
    {
        $clientEmail = trim($clientEmail);
        $privateKey = trim($privateKey);
        $delegatedUser = trim($delegatedUser);
        $scope = trim($scope);

        if ($clientEmail === '' || $privateKey === '' || $delegatedUser === '' || $scope === '') {
            throw new RuntimeException('Google service account email, private key, delegated sender, and scope are required.');
        }

        $now = time();
        $assertion = $this->jwt([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ], [
            'iss' => $clientEmail,
            'sub' => $delegatedUser,
            'scope' => $scope,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ], $this->normalizePrivateKey($privateKey));

        $response = $this->postForm(self::TOKEN_URL, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion,
        ], '', '&'));

        $data = json_decode($response['body'], true);
        if (! is_array($data)) {
            throw new RuntimeException('Google OAuth returned an invalid JSON response.');
        }

        if ($response['status'] < 200 || $response['status'] >= 300) {
            $message = (string) ($data['error_description'] ?? $data['error'] ?? 'Google OAuth token request failed.');

            throw new RuntimeException($this->sanitize($message));
        }

        if (empty($data['access_token'])) {
            throw new RuntimeException('Google OAuth response did not include an access token.');
        }

        return [
            'access_token' => (string) $data['access_token'],
            'expires_in' => (int) ($data['expires_in'] ?? 3600),
            'scope' => $scope,
            'token_type' => (string) ($data['token_type'] ?? 'Bearer'),
        ];
    }

    private function jwt(array $header, array $claims, string $privateKey): string
    {
        $key = openssl_pkey_get_private($privateKey);
        if ($key === false) {
            throw new RuntimeException('Google service account private key could not be parsed. Paste the full PEM key including BEGIN PRIVATE KEY and END PRIVATE KEY lines.');
        }

        $segments = [
            $this->base64UrlEncode(json_encode($header) ?: ''),
            $this->base64UrlEncode(json_encode($claims) ?: ''),
        ];
        $signingInput = implode('.', $segments);
        $signature = '';

        if (! openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Unable to sign Google OAuth JWT. Check the service account private key.');
        }

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function postForm(string $url, string $body): array
    {
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: ' . strlen($body),
        ];

        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HEADER => false,
            ]);

            $responseBody = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            if ($responseBody === false) {
                throw new RuntimeException('Google OAuth request failed: ' . $this->sanitize($error));
            }

            return ['status' => $status, 'body' => (string) $responseBody];
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'timeout' => 30,
                'ignore_errors' => true,
            ],
        ]);

        $responseBody = file_get_contents($url, false, $context);
        $status = 0;

        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
                $status = (int) $matches[1];
                break;
            }
        }

        if ($responseBody === false) {
            throw new RuntimeException('Google OAuth request failed.');
        }

        return ['status' => $status, 'body' => (string) $responseBody];
    }

    private function normalizePrivateKey(string $privateKey): string
    {
        return str_replace('\n', "\n", $privateKey);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function sanitize(string $message): string
    {
        $message = preg_replace('/-----BEGIN PRIVATE KEY-----.+?-----END PRIVATE KEY-----/s', '[redacted private key]', $message) ?? $message;
        $message = preg_replace('/(assertion=)[^&\s]+/i', '$1[redacted]', $message) ?? $message;

        return $message;
    }
}
