<?php

namespace BisonDigital\Mailroom\Services;

use BisonDigital\Mailroom\DTO\EmailMessage;
use BisonDigital\Mailroom\DTO\SendResult;
use Throwable;

class MailerService
{
    public function __construct(
        private ?SettingsService $settings = null,
        private ?TransportRepository $transports = null,
        private ?TransportFactory $factory = null,
    ) {
        $this->settings ??= new SettingsService();
        $this->transports ??= new TransportRepository();
        $this->factory ??= new TransportFactory($this->transports);
    }

    public function send(array|EmailMessage $payload, string $transportHandle = ''): SendResult
    {
        $message = $payload instanceof EmailMessage ? $payload : EmailMessage::fromArray($payload);
        $message = $this->applyDefaults($this->applyDevMode($message));
        $devMode = (string) $this->settings->get('dev_mode', 'normal');
        $transportHandle = $transportHandle !== '' ? $transportHandle : (string) $this->settings->get('default_transport', '');

        if ($devMode === 'suppress') {
            $result = SendResult::captured('mailroom', 'Email suppressed by Mailroom dev mode.');
            $this->log($message, $transportHandle, $result);

            return $result;
        }

        if ($devMode === 'capture') {
            $transportHandle = 'mailpit';
        }

        if ($transportHandle === '') {
            $result = SendResult::failure('mailroom', 'No default Mailroom transport is configured.', 'transport_missing');
            $this->log($message, $transportHandle, $result);

            return $result;
        }

        try {
            $transportRecord = $this->transports->findByHandle($transportHandle);

            if (! $transportRecord || ($transportRecord['enabled'] ?? 'n') !== 'y') {
                $result = SendResult::failure('mailroom', 'Mailroom transport is not enabled: ' . $transportHandle, 'transport_disabled');
                $this->log($message, $transportHandle, $result);

                return $result;
            }

            $result = $this->factory->make($transportHandle)->send($message);
        } catch (Throwable $throwable) {
            $result = SendResult::failure(
                'mailroom',
                $throwable->getMessage(),
                'transport_exception',
                $throwable->getFile() . ':' . $throwable->getLine(),
                true,
                $throwable
            );
        }

        $log = $this->log($message, $transportHandle, $result);

        if (! $result->success && $result->retryable && $this->settings->get('auto_retry', 'y') === 'y') {
            $this->queueRetry($log['id'], $log['message_uuid'], $result);
        }

        return $result;
    }

    private function applyDefaults(EmailMessage $message): EmailMessage
    {
        if ($message->from === '') {
            $message->from = (string) $this->settings->get('default_from_email', '');
        }

        if ($message->fromName === '') {
            $message->fromName = (string) $this->settings->get('default_from_name', '');
        }

        if ($message->replyTo === []) {
            $replyTo = (string) $this->settings->get('default_reply_to', '');
            $message->replyTo = $replyTo !== '' ? [$replyTo] : [];
        }

        return $message;
    }

    private function applyDevMode(EmailMessage $message): EmailMessage
    {
        $mode = (string) $this->settings->get('dev_mode', 'normal');

        if ($mode === 'redirect') {
            $redirectEmail = (string) $this->settings->get('redirect_email', '');
            if ($redirectEmail !== '') {
                $message->metadata['mailroom_original_to'] = $message->to;
                $message->to = [$redirectEmail];
                $message->cc = [];
                $message->bcc = [];
            }
        }

        return $message;
    }

    private function log(EmailMessage $message, string $transportHandle, SendResult $result): array
    {
        if (! $this->tableExists('mailroom_logs')) {
            return ['id' => 0, 'message_uuid' => ''];
        }

        $uuid = $this->messageUuid();
        $now = $this->now();
        $to = $this->firstAddress($message->to);
        $loggingMode = (string) $this->settings->get('logging_mode', 'metadata');
        $storeBodies = $loggingMode === 'full';

        ee()->db->insert('mailroom_logs', [
            'site_id' => $this->siteId(),
            'message_uuid' => $uuid,
            'source' => $message->source,
            'source_label' => (string) ($message->metadata['source_label'] ?? ''),
            'to_email' => $this->maskRecipient($to),
            'to_name' => '',
            'cc_json' => json_encode($message->cc),
            'bcc_json' => json_encode($message->bcc),
            'subject' => $message->subject,
            'from_email' => $message->from,
            'from_name' => $message->fromName,
            'reply_to_json' => json_encode($message->replyTo),
            'transport' => $transportHandle,
            'status' => $result->status,
            'provider_message_id' => $result->providerMessageId,
            'provider_response' => $result->providerResponse,
            'error_code' => $result->errorCode,
            'error_message' => $result->errorMessage,
            'diagnostic_message' => $result->diagnosticMessage,
            'retry_count' => 0,
            'max_retries' => (int) $this->settings->get('max_retry_attempts', 3),
            'next_retry_at' => null,
            'created_at' => $now,
            'sent_at' => $result->success ? $now : null,
            'failed_at' => $result->success ? null : $now,
            'last_retry_at' => null,
            'html_body' => $storeBodies ? $message->htmlBody : null,
            'text_body' => $storeBodies ? $message->textBody : null,
            'headers_json' => json_encode($message->headers),
            'attachments_json' => json_encode($message->attachments),
            'metadata_json' => json_encode($message->metadata),
        ]);

        return ['id' => (int) ee()->db->insert_id(), 'message_uuid' => $uuid];
    }

    private function queueRetry(int $logId, string $messageUuid, SendResult $result): void
    {
        if ($logId <= 0 || ! $this->tableExists('mailroom_queue')) {
            return;
        }

        $maxAttempts = (int) $this->settings->get('max_retry_attempts', 3);

        if ($maxAttempts <= 0) {
            return;
        }

        $schedule = $this->retrySchedule();
        $nextAttempt = $this->now() + (($schedule[0] ?? 5) * 60);

        ee()->db->insert('mailroom_queue', [
            'site_id' => $this->siteId(),
            'log_id' => $logId,
            'message_uuid' => $messageUuid,
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => $maxAttempts,
            'next_attempt_at' => $nextAttempt,
            'last_attempt_at' => null,
            'created_at' => $result->timestamp,
            'updated_at' => $this->now(),
        ]);
    }

    private function retrySchedule(): array
    {
        $raw = (string) $this->settings->get('retry_schedule', '5,30,120');
        $minutes = array_map('intval', array_filter(array_map('trim', explode(',', $raw)), 'strlen'));

        return array_values(array_filter($minutes, fn (int $minute): bool => $minute > 0));
    }

    private function firstAddress(array $addresses): string
    {
        foreach ($addresses as $address) {
            if (is_string($address)) {
                return $address;
            }

            if (is_array($address)) {
                return (string) ($address['email'] ?? $address['address'] ?? $address[0] ?? '');
            }
        }

        return '';
    }

    private function maskRecipient(string $email): string
    {
        if ($email === '' || $this->settings->get('recipient_masking', 'n') !== 'y') {
            return $email;
        }

        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return substr($local, 0, 1) . '***@' . $domain;
    }

    private function messageUuid(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function tableExists(string $table): bool
    {
        return function_exists('ee') && ee()->db->table_exists($table);
    }

    private function siteId(): int
    {
        return (int) ee()->config->item('site_id') ?: 1;
    }

    private function now(): int
    {
        return (int) ee()->localize->now ?: time();
    }
}
