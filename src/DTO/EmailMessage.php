<?php

namespace BisonDigital\Mailroom\DTO;

class EmailMessage
{
    public function __construct(
        public array $to = [],
        public array $cc = [],
        public array $bcc = [],
        public string $from = '',
        public string $fromName = '',
        public array $replyTo = [],
        public string $subject = '',
        public string $htmlBody = '',
        public string $textBody = '',
        public array $headers = [],
        public array $attachments = [],
        public string $source = '',
        public array $metadata = [],
        public string $priority = 'normal',
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            to: self::normalizeAddressList($payload['to'] ?? []),
            cc: self::normalizeAddressList($payload['cc'] ?? []),
            bcc: self::normalizeAddressList($payload['bcc'] ?? []),
            from: (string) ($payload['from'] ?? ''),
            fromName: (string) ($payload['from_name'] ?? $payload['fromName'] ?? ''),
            replyTo: self::normalizeAddressList($payload['reply_to'] ?? $payload['replyTo'] ?? []),
            subject: (string) ($payload['subject'] ?? ''),
            htmlBody: (string) ($payload['html'] ?? $payload['html_body'] ?? $payload['htmlBody'] ?? ''),
            textBody: (string) ($payload['text'] ?? $payload['text_body'] ?? $payload['textBody'] ?? ''),
            headers: is_array($payload['headers'] ?? null) ? $payload['headers'] : [],
            attachments: is_array($payload['attachments'] ?? null) ? $payload['attachments'] : [],
            source: (string) ($payload['source'] ?? ''),
            metadata: is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
            priority: (string) ($payload['priority'] ?? 'normal'),
        );
    }

    public function toArray(): array
    {
        return [
            'to' => $this->to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'from' => $this->from,
            'from_name' => $this->fromName,
            'reply_to' => $this->replyTo,
            'subject' => $this->subject,
            'html_body' => $this->htmlBody,
            'text_body' => $this->textBody,
            'headers' => $this->headers,
            'attachments' => $this->attachments,
            'source' => $this->source,
            'metadata' => $this->metadata,
            'priority' => $this->priority,
        ];
    }

    private static function normalizeAddressList(mixed $value): array
    {
        if (is_string($value)) {
            return array_values(array_filter(array_map('trim', explode(',', $value))));
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static function ($address): bool {
            return is_string($address) ? trim($address) !== '' : is_array($address);
        }));
    }
}
