<?php

namespace BisonDigital\Mailroom\Transports;

use BisonDigital\Mailroom\DTO\EmailMessage;
use BisonDigital\Mailroom\DTO\SendResult;
use BisonDigital\Mailroom\DTO\ValidationResult;
use BisonDigital\Mailroom\Services\Auth\GoogleOAuthClient;
use BisonDigital\Mailroom\Services\Auth\TokenStore;
use RuntimeException;
use Throwable;

class GoogleGmailTransport extends AbstractTransport
{
    private const SCOPE = 'https://www.googleapis.com/auth/gmail.send';

    public function __construct(
        array $settings = [],
        private int $transportId = 0,
        private ?GoogleOAuthClient $oauth = null,
        private ?TokenStore $tokens = null,
    ) {
        parent::__construct($settings);
        $this->oauth ??= new GoogleOAuthClient();
        $this->tokens ??= new TokenStore();
    }

    public function getName(): string
    {
        return 'Google Gmail API';
    }

    public function getHandle(): string
    {
        return 'google_gmail';
    }

    public function validateSettings(array $settings): ValidationResult
    {
        $result = ValidationResult::valid();

        foreach ([
            'client_email' => 'Service account email is required.',
            'private_key' => 'Private key is required.',
            'sender' => 'Delegated sender mailbox is required.',
        ] as $field => $message) {
            if (trim((string) ($settings[$field] ?? '')) === '') {
                $result->addError($field, $message);
            }
        }

        foreach (['client_email', 'sender'] as $field) {
            $email = trim((string) ($settings[$field] ?? ''));
            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $result->addError($field, ucfirst(str_replace('_', ' ', $field)) . ' must be a valid email address.');
            }
        }

        return $result;
    }

    public function send(EmailMessage $message): SendResult
    {
        $settingsValidation = $this->validateSettings($this->settings);
        if (! $settingsValidation->valid) {
            return SendResult::failure('google_gmail', 'Google Gmail API is not configured.', 'google_not_configured', json_encode($settingsValidation->errors) ?: '');
        }

        $messageValidation = $this->validateMessage($message);
        if (! $messageValidation->valid) {
            return SendResult::failure('google_gmail', 'Email message failed validation.', 'validation_failed', json_encode($messageValidation->errors) ?: '');
        }

        $sender = trim((string) $this->setting('sender', ''));

        try {
            $token = $this->tokens->getAccessToken(
                'google_gmail',
                $this->transportId,
                $sender,
                [self::SCOPE],
                fn (): array => $this->oauth->serviceAccountToken(
                    (string) $this->setting('client_email', ''),
                    (string) $this->setting('private_key', ''),
                    $sender,
                    self::SCOPE
                )
            );

            $response = $this->postJson(
                'https://gmail.googleapis.com/gmail/v1/users/' . rawurlencode($sender) . '/messages/send',
                $token,
                ['raw' => $this->base64UrlEncode($this->mime($message, $sender))]
            );

            if ($response['status'] >= 200 && $response['status'] < 300) {
                $data = json_decode((string) $response['body'], true);

                return SendResult::success('google_gmail', (string) ($data['id'] ?? ''), 'Gmail API accepted the message for delivery.', $response);
            }

            return SendResult::failure(
                'google_gmail',
                'Gmail API send failed.',
                'google_send_failed',
                $this->googleError($response),
                $response['status'] >= 500,
                $response
            );
        } catch (Throwable $throwable) {
            return SendResult::failure(
                'google_gmail',
                $this->sanitize($throwable->getMessage()),
                'google_exception',
                '',
                true,
                null
            );
        }
    }

    private function validateMessage(EmailMessage $message): ValidationResult
    {
        $result = ValidationResult::valid();

        if ($message->to === []) {
            $result->addError('to', 'At least one recipient is required.');
        }

        if ($message->subject === '') {
            $result->addError('subject', 'Subject is required.');
        }

        if ($message->htmlBody === '' && $message->textBody === '') {
            $result->addError('body', 'An HTML or text body is required.');
        }

        foreach (['to' => $message->to, 'cc' => $message->cc, 'bcc' => $message->bcc, 'reply_to' => $message->replyTo] as $field => $addresses) {
            foreach ($addresses as $address) {
                $email = $this->extractEmail($address);
                if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $result->addError($field, 'Invalid email address: ' . (is_scalar($address) ? (string) $address : json_encode($address)));
                }
            }
        }

        return $result;
    }

    private function mime(EmailMessage $message, string $sender): string
    {
        $headers = [
            'From' => $this->addressHeader($sender, $message->fromName !== '' ? $message->fromName : (string) $this->setting('from_name', '')),
            'To' => $this->addressList($message->to),
            'Subject' => $this->encodeHeader($message->subject),
            'MIME-Version' => '1.0',
        ];

        if ($message->replyTo !== []) {
            $headers['Reply-To'] = $this->addressList($message->replyTo);
        } elseif ($message->from !== '' && $message->from !== $sender) {
            $headers['Reply-To'] = $this->addressHeader($message->from, $message->fromName);
        }

        if ($message->cc !== []) {
            $headers['Cc'] = $this->addressList($message->cc);
        }

        if ($message->bcc !== []) {
            $headers['Bcc'] = $this->addressList($message->bcc);
        }

        $body = $message->htmlBody !== '' ? $message->htmlBody : $message->textBody;
        $contentType = $message->htmlBody !== '' ? 'text/html' : 'text/plain';
        $headers['Content-Type'] = $contentType . '; charset=utf-8';
        $headers['Content-Transfer-Encoding'] = 'base64';

        $raw = '';
        foreach ($headers as $name => $value) {
            $raw .= $name . ': ' . $value . "\r\n";
        }

        return $raw . "\r\n" . chunk_split(base64_encode($body), 76, "\r\n");
    }

    private function postJson(string $url, string $token, array $payload): array
    {
        $body = json_encode($payload);
        if ($body === false) {
            throw new RuntimeException('Unable to encode Gmail API payload.');
        }

        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
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
                throw new RuntimeException('Gmail API request failed: ' . $this->sanitize($error));
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
            throw new RuntimeException('Gmail API request failed.');
        }

        return ['status' => $status, 'body' => (string) $responseBody];
    }

    private function googleError(array $response): string
    {
        $data = json_decode((string) ($response['body'] ?? ''), true);
        $status = (int) ($response['status'] ?? 0);

        if (is_array($data)) {
            return trim('HTTP ' . $status . ': ' . $this->sanitize((string) ($data['error']['message'] ?? 'Gmail API returned an error.')));
        }

        return 'HTTP ' . $status . ': Gmail API returned a non-JSON error response.';
    }

    private function addressList(array $addresses): string
    {
        return implode(', ', array_values(array_filter(array_map(fn ($address): string => $this->addressHeader($this->extractEmail($address), ''), $addresses))));
    }

    private function addressHeader(string $email, string $name): string
    {
        $email = trim($email);
        $name = trim($name);

        return $name !== '' ? $this->encodeHeader($name) . ' <' . $email . '>' : $email;
    }

    private function extractEmail(mixed $address): string
    {
        if (is_string($address)) {
            return trim($address);
        }

        if (! is_array($address)) {
            return '';
        }

        return trim((string) ($address['email'] ?? $address['address'] ?? $address[0] ?? ''));
    }

    private function encodeHeader(string $value): string
    {
        return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function sanitize(string $message): string
    {
        $message = preg_replace('/Bearer\s+[A-Za-z0-9._~+\/=-]+/i', 'Bearer [redacted]', $message) ?? $message;
        $message = preg_replace('/-----BEGIN PRIVATE KEY-----.+?-----END PRIVATE KEY-----/s', '[redacted private key]', $message) ?? $message;

        return $message;
    }
}
