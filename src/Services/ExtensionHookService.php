<?php

namespace BisonDigital\Mailroom\Services;

class ExtensionHookService
{
    private const CLASS_NAME = 'Mailroom_ext';
    private const METHOD_NAME = 'email_send';
    private const HOOK_NAME = 'email_send';
    private const PRIORITY = 10;

    public function ensureEmailHook(string $version): void
    {
        $rows = ee()->db
            ->select('extension_id')
            ->where('class', self::CLASS_NAME)
            ->where('hook', self::HOOK_NAME)
            ->order_by('extension_id', 'desc')
            ->get('extensions')
            ->result_array();

        if (count($rows) > 1) {
            $keep = (int) $rows[0]['extension_id'];

            ee()->db
                ->where('class', self::CLASS_NAME)
                ->where('hook', self::HOOK_NAME)
                ->where('extension_id !=', $keep)
                ->delete('extensions');
        }

        $data = [
            'class' => self::CLASS_NAME,
            'method' => self::METHOD_NAME,
            'hook' => self::HOOK_NAME,
            'settings' => serialize([]),
            'priority' => self::PRIORITY,
            'version' => $version,
            'enabled' => 'y',
        ];

        $exists = (int) ee()->db
            ->where('class', self::CLASS_NAME)
            ->where('hook', self::HOOK_NAME)
            ->count_all_results('extensions');

        if ($exists > 0) {
            ee()->db
                ->where('class', self::CLASS_NAME)
                ->where('hook', self::HOOK_NAME)
                ->update('extensions', $data);

            return;
        }

        ee()->db->insert('extensions', $data);
    }
}
