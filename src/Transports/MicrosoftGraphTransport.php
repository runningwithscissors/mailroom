<?php

namespace BisonDigital\Mailroom\Transports;

use BisonDigital\Mailroom\DTO\EmailMessage;
use BisonDigital\Mailroom\DTO\SendResult;
use BisonDigital\Mailroom\DTO\ValidationResult;
use BisonDigital\Mailroom\Services\Auth\OAuthClient;
use BisonDigital\Mailroom\Services\Auth\TokenStore;
use RuntimeException;
use Throwable;

class MicrosoftGraphTransport extends AbstractTransport
{
    private const SCOPE = 'https://graph.microsoft.com/.default';

    public function __construct(
        array $settings = [],
        private int $transportId = 0,
        private ?OAuthClient $oauth = null,
        private ?TokenStore $tokens = null,
    ) {
        parent::__construct($settings);
        $this->oauth ??= new OAuthClient();
        $this->tokens ??= new TokenStore();
    }

    public function getName(): string
    {
        return 'Microsoft 365 Graph';
    }

    public function getHandle(): string
    {
        return 'microsoft_graph';
    }

    public function validateSettings(array $settings): ValidationResult
    {
        $result = ValidationResult::valid();

        foreach ([
            'tenant_id' => 'Tenant ID is required.',
            'client_id' => 'Client ID is required.',
            'client_secret' => 'Client secret is required.',
            'sender' => 'Sender mailbox is required.',
        ] as $field => $message) {
            if (trim((string) ($settings[$field] ?? '')) === '') {
                $result->addError($field, $message);
            }
        }

        $sender = trim((string) ($settings['sender'] ?? ''));
        if ($sender !== '' && ! filter_var($sender, FILTER_VALIDATE_EMAIL)) {
            $result->addError('sender', 'Sender mailbox must be a valid email address.');
        }

        return $result;
    }

    public function send(EmailMessage $message): SendResult
    {
        $settingsValidation = $this->validateSettings($this->settings);
        if (! $settingsValidation->valid) {
            return SendResult::failure('microsoft_graph', 'Microsoft Graph is not configured.', 'graph_not_configured', json_encode($settingsValidation->errors) ?: '');
        }

        $messageValidation = $this->validateMessage($message);
        if (! $messageValidation->valid) {
            return SendResult::failure('microsoft_graph', 'Email message failed validation.', 'validation_failed', json_encode($messageValidation->errors) ?: '');
        }

        $sender = trim((string) $this->setting('sender', ''));

        try {
            // Client credentials is appropriate for unattended CMS transactional mail.
            // It sends as a configured mailbox through application permissions/admin consent;
            // delegated auth is only needed later if Mailroom must send as an interactive user.
            $token = $this->tokens->getAccessToken(
                'microsoft_graph',
                $this->transportId,
                $sender,
                [self::SCOPE],
                fn (): array => $this->oauth->clientCredentials(
                    (string) $this->setting('tenant_id', ''),
                    (string) $this->setting('client_id', ''),
                    (string) $this->setting('client_secret', ''),
                    self::SCOPE
                )
            );

            $response = $this->postJson(
                'https://graph.microsoft.com/v1.0/users/' . rawurlencode($sender) . '/sendMail',
                $token,
                $this->payload($message)
            );

            if ($response['status'] === 202) {
                return SendResult::success('microsoft_graph', '', 'Microsoft Graph accepted the message for delivery.');
            }

            return SendResult::failure(
                'microsoft_graph',
                'Microsoft Graph sendMail failed.',
                'graph_send_failed',
                $this->graphError($response),
                $response['status'] >= 500,
                $response
            );
        } catch (Throwable $throwable) {
            return SendResult::failure(
                'microsoft_graph',
                $this->sanitize($throwable->getMessage()),
                'graph_exception',
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

    private function payload(EmailMessage $message): array
    {
        $graphMessage = [
            'subject' => $message->subject,
            'body' => [
                'contentType' => $message->htmlBody !== '' ? 'HTML' : 'Text',
                'content' => $message->htmlBody !== '' ? $message->htmlBody : $message->textBody,
            ],
            'toRecipients' => $this->recipients($message->to),
        ];

        if ($message->cc !== []) {
            $graphMessage['ccRecipients'] = $this->recipients($message->cc);
        }

        if ($message->bcc !== []) {
            $graphMessage['bccRecipients'] = $this->recipients($message->bcc);
        }

        if ($message->replyTo !== []) {
            $graphMessage['replyTo'] = $this->recipients($message->replyTo);
        }

        $headers = $this->headers($message->headers);
        if ($headers !== []) {
            $graphMessage['internetMessageHeaders'] = $headers;
        }

        $attachments = $this->attachments($message->attachments);
        if ($attachments !== []) {
            $graphMessage['attachments'] = $attachments;
        }

        return [
            'message' => $graphMessage,
            'saveToSentItems' => ($this->setting('save_to_sent_items', 'n') === 'y'),
        ];
    }

    private function recipients(array $addresses): array
    {
        return array_values(array_map(fn ($address): array => [
            'emailAddress' => ['address' => $this->extractEmail($address)],
        ], array_filter($addresses, fn ($address): bool => $this->extractEmail($address) !== '')));
    }

    private function headers(array $headers): array
    {
        $blocked = [
            'authorization',
            'bcc',
            'cc',
            'content-transfer-encoding',
            'content-type',
            'cookie',
            'date',
            'from',
            'mime-version',
            'reply-to',
            'return-path',
            'sender',
            'subject',
            'to',
            'x-mailer',
            'x-priority',
            'x-sender',
        ];
        $out = [];

        foreach ($headers as $name => $value) {
            $name = (string) $name;
            $lowerName = strtolower($name);

            if ($name === '' || in_array($lowerName, $blocked, true) || ! is_scalar($value)) {
                continue;
            }

            if (! str_starts_with($lowerName, 'x-') && ! str_starts_with($lowerName, 'list-')) {
                continue;
            }

            $out[] = ['name' => $name, 'value' => (string) $value];
        }

        return $out;
    }

    private function attachments(array $attachments): array
    {
        $out = [];

        foreach ($attachments as $attachment) {
            $path = is_string($attachment) ? $attachment : (string) ($attachment['path'] ?? $attachment['file'] ?? '');
            if ($path === '' || ! is_readable($path) || ! is_file($path)) {
                continue;
            }

            $out[] = [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => is_array($attachment) && ! empty($attachment['name']) ? (string) $attachment['name'] : basename($path),
                'contentType' => is_array($attachment) && ! empty($attachment['mime']) ? (string) $attachment['mime'] : 'application/octet-stream',
                'contentBytes' => base64_encode((string) file_get_contents($path)),
            ];
        }

        return $out;
    }

    private function postJson(string $url, string $token, array $payload): array
    {
        $body = json_encode($payload);
        if ($body === false) {
            throw new RuntimeException('Unable to encode Microsoft Graph payload.');
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
                throw new RuntimeException('Microsoft Graph request failed: ' . $this->sanitize($error));
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
            throw new RuntimeException('Microsoft Graph request failed.');
        }

        return ['status' => $status, 'body' => (string) $responseBody];
    }

    private function graphError(array $response): string
    {
        $data = json_decode((string) ($response['body'] ?? ''), true);
        $status = (int) ($response['status'] ?? 0);

        if (is_array($data)) {
            $code = (string) ($data['error']['code'] ?? '');
            $message = (string) ($data['error']['message'] ?? '');
            return trim('HTTP ' . $status . ' ' . $code . ': ' . $this->sanitize($message));
        }

        return 'HTTP ' . $status . ': Microsoft Graph returned a non-JSON error response.';
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

    private function sanitize(string $message): string
    {
        $message = preg_replace('/Bearer\s+[A-Za-z0-9._~+\/=-]+/i', 'Bearer [redacted]', $message) ?? $message;
        $message = preg_replace('/(client_secret=)[^&\s]+/i', '$1[redacted]', $message) ?? $message;
        return $message;
    }
}
