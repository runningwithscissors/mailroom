<?php

namespace BisonDigital\Mailroom\Services\Auth;

class TokenStore
{
    private const REFRESH_WINDOW_SECONDS = 300;

    public function getAccessToken(
        string $provider,
        int $transportId,
        string $accountEmail,
        array $scopes,
        callable $refresh
    ): string {
        $row = $this->find($provider, $transportId, $accountEmail);
        $now = $this->now();

        if ($row && ! empty($row['access_token']) && (int) ($row['expires_at'] ?? 0) > ($now + self::REFRESH_WINDOW_SECONDS)) {
            return (string) $row['access_token'];
        }

        $token = $refresh();
        $accessToken = (string) ($token['access_token'] ?? '');
        $expiresIn = (int) ($token['expires_in'] ?? 3600);

        if ($accessToken === '') {
            throw new \RuntimeException('Microsoft OAuth token refresh did not return an access token.');
        }

        $this->store($provider, $transportId, $accountEmail, $accessToken, $now + $expiresIn, $scopes);

        return $accessToken;
    }

    public function clear(string $provider, int $transportId = 0, string $accountEmail = ''): void
    {
        if (! $this->tableExists()) {
            return;
        }

        ee()->db
            ->where('site_id', $this->siteId())
            ->where('provider', $provider);

        if ($transportId > 0) {
            ee()->db->where('transport_id', $transportId);
        }

        if ($accountEmail !== '') {
            ee()->db->where('account_email', $accountEmail);
        }

        ee()->db->delete('mailroom_tokens');
    }

    private function find(string $provider, int $transportId, string $accountEmail): ?array
    {
        if (! $this->tableExists()) {
            return null;
        }

        $row = ee()->db
            ->where('site_id', $this->siteId())
            ->where('provider', $provider)
            ->where('transport_id', $transportId)
            ->where('account_email', $accountEmail)
            ->get('mailroom_tokens')
            ->row_array();

        return $row ?: null;
    }

    private function store(string $provider, int $transportId, string $accountEmail, string $accessToken, int $expiresAt, array $scopes): void
    {
        if (! $this->tableExists()) {
            return;
        }

        $siteId = $this->siteId();
        $now = $this->now();
        $data = [
            'site_id' => $siteId,
            'provider' => $provider,
            'transport_id' => $transportId,
            'account_email' => $accountEmail,
            'access_token' => $accessToken,
            'refresh_token' => null,
            'expires_at' => $expiresAt,
            'scopes_json' => json_encode(array_values($scopes)),
            'updated_at' => $now,
        ];

        $exists = (int) ee()->db
            ->where('site_id', $siteId)
            ->where('provider', $provider)
            ->where('transport_id', $transportId)
            ->where('account_email', $accountEmail)
            ->count_all_results('mailroom_tokens');

        if ($exists > 0) {
            ee()->db
                ->where('site_id', $siteId)
                ->where('provider', $provider)
                ->where('transport_id', $transportId)
                ->where('account_email', $accountEmail)
                ->update('mailroom_tokens', $data);

            return;
        }

        $data['created_at'] = $now;
        ee()->db->insert('mailroom_tokens', $data);
    }

    private function tableExists(): bool
    {
        return function_exists('ee') && ee()->db->table_exists('mailroom_tokens');
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
