<?php

namespace BisonDigital\Mailroom\Services;

class LogService
{
    public function countRecentSends(int $seconds = 86400): int
    {
        if (! $this->tableExists()) {
            return 0;
        }

        return (int) ee()->db
            ->where('site_id', $this->siteId())
            ->where_in('status', ['sent', 'captured'])
            ->where('created_at >=', $this->now() - $seconds)
            ->count_all_results('mailroom_logs');
    }

    public function countRecentFailures(int $seconds = 86400): int
    {
        if (! $this->tableExists()) {
            return 0;
        }

        return (int) ee()->db
            ->where('site_id', $this->siteId())
            ->where_in('status', ['failed', 'abandoned'])
            ->where('created_at >=', $this->now() - $seconds)
            ->count_all_results('mailroom_logs');
    }

    public function countQueuedRetries(): int
    {
        if (! $this->queueTableExists()) {
            return 0;
        }

        return (int) ee()->db
            ->where('site_id', $this->siteId())
            ->where('status', 'pending')
            ->count_all_results('mailroom_queue');
    }

    public function latestFailures(int $limit = 5): array
    {
        if (! $this->tableExists()) {
            return [];
        }

        return ee()->db
            ->where('site_id', $this->siteId())
            ->where_in('status', ['failed', 'abandoned'])
            ->order_by('created_at', 'desc')
            ->limit($limit)
            ->get('mailroom_logs')
            ->result_array();
    }

    public function latest(int $limit = 25): array
    {
        if (! $this->tableExists()) {
            return [];
        }

        return ee()->db
            ->where('site_id', $this->siteId())
            ->order_by('created_at', 'desc')
            ->limit($limit)
            ->get('mailroom_logs')
            ->result_array();
    }

    private function tableExists(): bool
    {
        return function_exists('ee') && ee()->db->table_exists('mailroom_logs');
    }

    private function queueTableExists(): bool
    {
        return function_exists('ee') && ee()->db->table_exists('mailroom_queue');
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
