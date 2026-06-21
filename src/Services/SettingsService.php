<?php

namespace BisonDigital\Mailroom\Services;

class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        if (! $this->tableExists()) {
            return $default;
        }

        $row = ee()->db
            ->where('site_id', $this->siteId())
            ->where('key', $key)
            ->get('mailroom_settings')
            ->row_array();

        if (! $row) {
            return $default;
        }

        if (($row['serialized'] ?? 'n') === 'y') {
            $decoded = json_decode((string) $row['value'], true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;
        }

        return $row['value'];
    }

    public function all(): array
    {
        if (! $this->tableExists()) {
            return [];
        }

        $rows = ee()->db
            ->where('site_id', $this->siteId())
            ->get('mailroom_settings')
            ->result_array();

        $settings = [];

        foreach ($rows as $row) {
            $settings[$row['key']] = ($row['serialized'] ?? 'n') === 'y'
                ? json_decode((string) $row['value'], true)
                : $row['value'];
        }

        return $settings;
    }

    public function set(string $key, mixed $value): void
    {
        if (! $this->tableExists()) {
            return;
        }

        $serialized = is_array($value) || is_object($value);
        $storedValue = $serialized ? json_encode($value) : (string) $value;
        $now = (int) ee()->localize->now;
        $siteId = $this->siteId();

        $exists = (int) ee()->db
            ->where('site_id', $siteId)
            ->where('key', $key)
            ->count_all_results('mailroom_settings');

        $data = [
            'value' => $storedValue,
            'serialized' => $serialized ? 'y' : 'n',
            'updated_at' => $now,
        ];

        if ($exists > 0) {
            ee()->db
                ->where('site_id', $siteId)
                ->where('key', $key)
                ->update('mailroom_settings', $data);

            return;
        }

        $data['site_id'] = $siteId;
        $data['key'] = $key;
        $data['created_at'] = $now;

        ee()->db->insert('mailroom_settings', $data);
    }

    private function tableExists(): bool
    {
        return function_exists('ee') && ee()->db->table_exists('mailroom_settings');
    }

    private function siteId(): int
    {
        return (int) ee()->config->item('site_id') ?: 1;
    }
}
