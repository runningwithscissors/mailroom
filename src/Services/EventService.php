<?php

namespace BisonDigital\Mailroom\Services;

class EventService
{
    public const TYPES = [
        'accepted',
        'delivered',
        'bounced',
        'deferred',
        'rejected',
        'complained',
        'suppressed',
        'opened',
        'clicked',
        'unknown',
    ];

    public function record(array $event): int
    {
        if (! $this->tableExists()) {
            return 0;
        }

        $now = $this->now();
        $type = (string) ($event['event_type'] ?? 'unknown');
        if (! in_array($type, self::TYPES, true)) {
            $type = 'unknown';
        }

        ee()->db->insert('mailroom_events', [
            'site_id' => $this->siteId(),
            'provider' => (string) ($event['provider'] ?? 'generic'),
            'event_type' => $type,
            'provider_event_id' => $event['provider_event_id'] ?? null,
            'provider_message_id' => $event['provider_message_id'] ?? null,
            'message_uuid' => $event['message_uuid'] ?? null,
            'recipient' => $event['recipient'] ?? null,
            'severity' => (string) ($event['severity'] ?? 'info'),
            'event_at' => (int) ($event['event_at'] ?? $now),
            'payload_json' => json_encode($event['payload'] ?? []),
            'signature_valid' => ! empty($event['signature_valid']) ? 'y' : 'n',
            'created_at' => $now,
        ]);

        return (int) ee()->db->insert_id();
    }

    public function countRecent(int $seconds = 86400): int
    {
        if (! $this->tableExists()) {
            return 0;
        }

        return (int) ee()->db
            ->where('site_id', $this->siteId())
            ->where('created_at >=', $this->now() - $seconds)
            ->count_all_results('mailroom_events');
    }

    public function isReady(): bool
    {
        return $this->tableExists();
    }

    private function tableExists(): bool
    {
        return function_exists('ee') && ee()->db->table_exists('mailroom_events');
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
