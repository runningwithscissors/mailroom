<?php

namespace BisonDigital\Mailroom\Services;

class TransportRepository
{
    public function all(): array
    {
        if (! $this->tableExists()) {
            return [];
        }

        return ee()->db
            ->where('site_id', $this->siteId())
            ->order_by('provider', 'asc')
            ->order_by('name', 'asc')
            ->get('mailroom_transports')
            ->result_array();
    }

    public function findByHandle(string $handle): ?array
    {
        if (! $this->tableExists()) {
            return null;
        }

        $row = ee()->db
            ->where('site_id', $this->siteId())
            ->where('handle', $handle)
            ->get('mailroom_transports')
            ->row_array();

        return $row ?: null;
    }

    public function enabledChoices(): array
    {
        $choices = ['' => lang('mailroom_not_configured')];

        foreach ($this->all() as $transport) {
            if (($transport['enabled'] ?? 'n') !== 'y') {
                continue;
            }

            $choices[$transport['handle']] = $transport['name'];
        }

        return $choices;
    }

    public function seedDefaults(): void
    {
        if (! $this->tableExists()) {
            return;
        }

        $this->ensureTransport('smtp', 'Generic SMTP', 'smtp');
        $this->ensureTransport('mailpit', 'Mailpit / Dev Capture', 'dev_capture');
    }

    public function updateState(string $handle, bool $enabled, bool $default): void
    {
        if (! $this->tableExists()) {
            return;
        }

        $siteId = $this->siteId();
        $now = $this->now();

        if ($default) {
            ee()->db
                ->where('site_id', $siteId)
                ->update('mailroom_transports', [
                    'is_default' => 'n',
                    'updated_at' => $now,
                ]);
        }

        ee()->db
            ->where('site_id', $siteId)
            ->where('handle', $handle)
            ->update('mailroom_transports', [
                'enabled' => $enabled ? 'y' : 'n',
                'is_default' => $default ? 'y' : 'n',
                'updated_at' => $now,
            ]);
    }

    public function setDefault(string $handle): void
    {
        if (! $this->tableExists()) {
            return;
        }

        $siteId = $this->siteId();
        $now = $this->now();

        ee()->db
            ->where('site_id', $siteId)
            ->update('mailroom_transports', [
                'is_default' => 'n',
                'updated_at' => $now,
            ]);

        if ($handle === '') {
            return;
        }

        ee()->db
            ->where('site_id', $siteId)
            ->where('handle', $handle)
            ->update('mailroom_transports', [
                'enabled' => 'y',
                'is_default' => 'y',
                'updated_at' => $now,
            ]);
    }

    public function settingsFor(string $handle): array
    {
        $transport = $this->findByHandle($handle);

        if (! $transport) {
            return [];
        }

        $settings = json_decode((string) ($transport['settings_json'] ?? '{}'), true);

        return is_array($settings) ? $settings : [];
    }

    public function updateSettings(string $handle, array $settings): void
    {
        if (! $this->tableExists()) {
            return;
        }

        ee()->db
            ->where('site_id', $this->siteId())
            ->where('handle', $handle)
            ->update('mailroom_transports', [
                'settings_json' => json_encode($settings),
                'updated_at' => $this->now(),
            ]);
    }

    private function ensureTransport(string $handle, string $name, string $provider): void
    {
        $exists = (int) ee()->db
            ->where('site_id', $this->siteId())
            ->where('handle', $handle)
            ->count_all_results('mailroom_transports');

        if ($exists > 0) {
            return;
        }

        $now = $this->now();

        ee()->db->insert('mailroom_transports', [
            'site_id' => $this->siteId(),
            'handle' => $handle,
            'name' => $name,
            'provider' => $provider,
            'enabled' => 'n',
            'is_default' => 'n',
            'settings_json' => '{}',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function tableExists(): bool
    {
        return function_exists('ee') && ee()->db->table_exists('mailroom_transports');
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
