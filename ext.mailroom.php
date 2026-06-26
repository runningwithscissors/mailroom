<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once __DIR__ . '/src/DTO/EmailMessage.php';
require_once __DIR__ . '/src/DTO/SendResult.php';
require_once __DIR__ . '/src/DTO/ValidationResult.php';
require_once __DIR__ . '/src/Services/SettingsService.php';
require_once __DIR__ . '/src/Services/ExtensionHookService.php';
require_once __DIR__ . '/src/Services/TransportRepository.php';
require_once __DIR__ . '/src/Services/TransportFactory.php';
require_once __DIR__ . '/src/Services/MailerService.php';
require_once __DIR__ . '/src/Services/Auth/OAuthClient.php';
require_once __DIR__ . '/src/Services/Auth/TokenStore.php';
require_once __DIR__ . '/src/Transports/TransportInterface.php';
require_once __DIR__ . '/src/Transports/AbstractTransport.php';
require_once __DIR__ . '/src/Transports/SmtpTransport.php';
require_once __DIR__ . '/src/Transports/MailpitTransport.php';
require_once __DIR__ . '/src/Transports/MicrosoftGraphTransport.php';

class Mailroom_ext
{
    public string $version = '0.2.2';
    public mixed $settings = '';
    private static bool $sendingThroughMailroom = false;

    public function __construct(mixed $settings = '')
    {
        $this->settings = $settings;
    }

    public function activate_extension(): void
    {
        $this->ensureHook();
    }

    public function update_extension(mixed $current = ''): bool
    {
        $this->ensureHook();

        return true;
    }

    public function disable_extension(): void
    {
        ee()->db
            ->where('class', __CLASS__)
            ->delete('extensions');
    }

    public function email_send(array $data): bool
    {
        if (self::$sendingThroughMailroom) {
            return false;
        }

        $settings = new \BisonDigital\Mailroom\Services\SettingsService();
        $devMode = (string) $settings->get('dev_mode', 'normal');

        if ($settings->get('intercept_core_email', 'n') !== 'y' && $devMode === 'normal') {
            return false;
        }

        $payload = $this->payloadFromEmailHook($data);

        self::$sendingThroughMailroom = true;

        try {
            $result = (new \BisonDigital\Mailroom\Services\MailerService($settings))->send($payload);
        } finally {
            self::$sendingThroughMailroom = false;
        }

        ee()->extensions->end_script = true;

        return $result->success;
    }

    private function ensureHook(): void
    {
        (new \BisonDigital\Mailroom\Services\ExtensionHookService())->ensureEmailHook($this->version);
    }

    private function payloadFromEmailHook(array $data): array
    {
        $headers = is_array($data['headers'] ?? null) ? $data['headers'] : [];
        [$fromEmail, $fromName] = $this->parseAddress((string) ($headers['From'] ?? ''));
        $body = $this->extractBody((string) ($data['finalbody'] ?? ''));

        return [
            'to' => $this->recipients($data['recipients'] ?? [], $headers['To'] ?? ''),
            'cc' => $this->recipients($data['cc_array'] ?? [], $headers['Cc'] ?? ''),
            'bcc' => $this->recipients($data['bcc_array'] ?? [], $headers['Bcc'] ?? ''),
            'from' => $fromEmail,
            'from_name' => $fromName,
            'reply_to' => $this->recipients([], $headers['Reply-To'] ?? ''),
            'subject' => (string) (($data['subject'] ?? '') ?: ($headers['Subject'] ?? '')),
            'html_body' => $body['html'],
            'text_body' => $body['text'],
            'headers' => $headers,
            'source' => 'expressionengine',
            'metadata' => [
                'source_label' => 'ExpressionEngine Email',
                'mailroom_hook' => 'email_send',
            ],
        ];
    }

    private function extractBody(string $finalBody): array
    {
        $body = ['html' => '', 'text' => ''];
        $normalized = str_replace("\r\n", "\n", $finalBody);

        if (preg_match('/boundary="([^"]+)"/i', $normalized, $boundaryMatch)) {
            $boundary = preg_quote($boundaryMatch[1], '/');

            if (preg_match('/Content-Type:\s*text\/html[^\n]*\n(?:[^\n]+:\s*[^\n]*\n)*\n(.*?)(?:\n--' . $boundary . ')/is', $normalized, $htmlMatch)) {
                $body['html'] = $this->decodeBodyPart($htmlMatch[0], $htmlMatch[1]);
            }

            if (preg_match('/Content-Type:\s*text\/plain[^\n]*\n(?:[^\n]+:\s*[^\n]*\n)*\n(.*?)(?:\n--' . $boundary . ')/is', $normalized, $textMatch)) {
                $body['text'] = $this->decodeBodyPart($textMatch[0], $textMatch[1]);
            }

            if ($body['html'] !== '' || $body['text'] !== '') {
                return $body;
            }
        }

        [$partHeaders, $partBody] = $this->splitHeadersAndBody($normalized);
        $decoded = $this->decodeBodyPart($partHeaders, $partBody);

        if (stripos($partHeaders, 'Content-Type: text/html') !== false) {
            $body['html'] = $decoded;
        } else {
            $body['text'] = $decoded;
        }

        return $body;
    }

    private function splitHeadersAndBody(string $content): array
    {
        if (! str_contains($content, "\n\n")) {
            return ['', $content];
        }

        return explode("\n\n", $content, 2);
    }

    private function decodeBodyPart(string $headers, string $body): string
    {
        $body = trim($body);

        if (stripos($headers, 'quoted-printable') !== false) {
            return quoted_printable_decode($body);
        }

        if (stripos($headers, 'base64') !== false) {
            $decoded = base64_decode($body, true);

            return $decoded === false ? $body : $decoded;
        }

        return $body;
    }

    private function recipients(mixed $addresses, mixed $fallback = ''): array
    {
        $list = [];

        if (is_array($addresses)) {
            $list = $addresses;
        } elseif (is_string($addresses) && $addresses !== '') {
            $list = explode(',', $addresses);
        }

        if ($list === [] && is_string($fallback) && $fallback !== '') {
            $list = explode(',', $fallback);
        }

        return array_values(array_filter(array_map(function (mixed $address): string {
            [$email] = $this->parseAddress(is_scalar($address) ? (string) $address : '');

            return $email;
        }, $list)));
    }

    private function parseAddress(string $address): array
    {
        $address = trim($address);

        if (preg_match('/^(.*?)<([^>]+)>$/', $address, $matches)) {
            return [
                trim($matches[2]),
                trim(trim($matches[1]), '" '),
            ];
        }

        return [trim($address, '<> '), ''];
    }
}
