<?php

namespace BisonDigital\Mailroom\Services\Auth;

use RuntimeException;

class OAuthClient
{
    public function clientCredentials(string $tenantId, string $clientId, string $clientSecret, string $scope): array
    {
        $tenantId = trim($tenantId);
        $clientId = trim($clientId);
        $clientSecret = trim($clientSecret);
        $scope = trim($scope);

        if ($tenantId === '' || $clientId === '' || $clientSecret === '' || $scope === '') {
            throw new RuntimeException('Microsoft OAuth tenant ID, client ID, client secret, and scope are required.');
        }

        $url = 'https://login.microsoftonline.com/' . rawurlencode($tenantId) . '/oauth2/v2.0/token';
        $body = http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'client_credentials',
            'scope' => $scope,
        ], '', '&');

        $response = $this->postForm($url, $body);
        $data = json_decode($response['body'], true);

        if (! is_array($data)) {
            throw new RuntimeException('Microsoft OAuth returned an invalid JSON response.');
        }

        if ($response['status'] < 200 || $response['status'] >= 300) {
            $message = (string) ($data['error_description'] ?? $data['error'] ?? 'Microsoft OAuth token request failed.');

            throw new RuntimeException($this->sanitize($message));
        }

        if (empty($data['access_token'])) {
            throw new RuntimeException('Microsoft OAuth response did not include an access token.');
        }

        return [
            'access_token' => (string) $data['access_token'],
            'expires_in' => (int) ($data['expires_in'] ?? 3600),
            'scope' => (string) ($data['scope'] ?? $scope),
            'token_type' => (string) ($data['token_type'] ?? 'Bearer'),
        ];
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
                throw new RuntimeException('Microsoft OAuth request failed: ' . $this->sanitize($error));
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
            throw new RuntimeException('Microsoft OAuth request failed.');
        }

        return ['status' => $status, 'body' => (string) $responseBody];
    }

    private function sanitize(string $message): string
    {
        return preg_replace('/(client_secret=)[^&\s]+/i', '$1[redacted]', $message) ?? $message;
    }
}
