<?php

use ExpressionEngine\Service\Addon\Installer;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Mailroom_upd extends Installer
{
    public $has_cp_backend = 'y';
    public $has_publish_fields = 'n';
    public $methods = [
        [
            'hook' => 'email_send',
            'method' => 'email_send',
            'priority' => 10,
            'enabled' => true,
        ],
    ];

    public function install(): bool
    {
        parent::install();
        $this->createTables();
        $this->insertDefaultSettings();
        $this->insertDefaultTransports();

        return true;
    }

    public function uninstall(): bool
    {
        ee()->load->dbforge();
        ee()->dbforge->drop_table('mailroom_queue', true);
        ee()->dbforge->drop_table('mailroom_logs', true);
        ee()->dbforge->drop_table('mailroom_tokens', true);
        ee()->dbforge->drop_table('mailroom_transports', true);
        ee()->dbforge->drop_table('mailroom_settings', true);

        parent::uninstall();

        return true;
    }

    public function update($current = ''): bool
    {
        $this->createTables();
        $this->insertDefaultSettings();
        $this->insertDefaultTransports();
        $this->activate_extension();

        return true;
    }

    private function createTables(): void
    {
        $this->createSettingsTable();
        $this->createTransportsTable();
        $this->createTokensTable();
        $this->createLogsTable();
        $this->createQueueTable();
    }

    private function createSettingsTable(): void
    {
        ee()->load->dbforge();

        ee()->dbforge->add_field([
            'id' => $this->idField(),
            'site_id' => $this->intField(),
            'key' => [
                'type' => 'varchar',
                'constraint' => 128,
                'null' => false,
            ],
            'value' => [
                'type' => 'text',
                'null' => true,
            ],
            'serialized' => [
                'type' => 'char',
                'constraint' => 1,
                'default' => 'n',
                'null' => false,
            ],
            'created_at' => $this->timestampField(),
            'updated_at' => $this->timestampField(),
        ]);
        ee()->dbforge->add_key('id', true);
        ee()->dbforge->add_key('site_id');
        ee()->dbforge->add_key('key');
        ee()->dbforge->create_table('mailroom_settings', true);
    }

    private function createTransportsTable(): void
    {
        ee()->load->dbforge();

        ee()->dbforge->add_field([
            'id' => $this->idField(),
            'site_id' => $this->intField(),
            'handle' => [
                'type' => 'varchar',
                'constraint' => 128,
                'null' => false,
            ],
            'name' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => false,
            ],
            'provider' => [
                'type' => 'varchar',
                'constraint' => 64,
                'null' => false,
            ],
            'enabled' => [
                'type' => 'char',
                'constraint' => 1,
                'default' => 'n',
                'null' => false,
            ],
            'is_default' => [
                'type' => 'char',
                'constraint' => 1,
                'default' => 'n',
                'null' => false,
            ],
            'settings_json' => [
                'type' => 'mediumtext',
                'null' => true,
            ],
            'created_at' => $this->timestampField(),
            'updated_at' => $this->timestampField(),
        ]);
        ee()->dbforge->add_key('id', true);
        ee()->dbforge->add_key('site_id');
        ee()->dbforge->add_key('handle');
        ee()->dbforge->add_key('provider');
        ee()->dbforge->create_table('mailroom_transports', true);
    }

    private function createTokensTable(): void
    {
        ee()->load->dbforge();

        ee()->dbforge->add_field([
            'id' => $this->idField(),
            'site_id' => $this->intField(),
            'provider' => [
                'type' => 'varchar',
                'constraint' => 64,
                'null' => false,
            ],
            'transport_id' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'null' => true,
            ],
            'account_email' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => true,
            ],
            'access_token' => [
                'type' => 'mediumtext',
                'null' => true,
            ],
            'refresh_token' => [
                'type' => 'mediumtext',
                'null' => true,
            ],
            'expires_at' => $this->nullableTimestampField(),
            'scopes_json' => [
                'type' => 'text',
                'null' => true,
            ],
            'created_at' => $this->timestampField(),
            'updated_at' => $this->timestampField(),
        ]);
        ee()->dbforge->add_key('id', true);
        ee()->dbforge->add_key('site_id');
        ee()->dbforge->add_key('provider');
        ee()->dbforge->add_key('transport_id');
        ee()->dbforge->create_table('mailroom_tokens', true);
    }

    private function createLogsTable(): void
    {
        ee()->load->dbforge();

        ee()->dbforge->add_field([
            'id' => $this->idField(),
            'site_id' => $this->intField(),
            'message_uuid' => [
                'type' => 'varchar',
                'constraint' => 64,
                'null' => false,
            ],
            'source' => [
                'type' => 'varchar',
                'constraint' => 128,
                'null' => true,
            ],
            'source_label' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => true,
            ],
            'to_email' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => true,
            ],
            'to_name' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => true,
            ],
            'cc_json' => [
                'type' => 'text',
                'null' => true,
            ],
            'bcc_json' => [
                'type' => 'text',
                'null' => true,
            ],
            'subject' => [
                'type' => 'varchar',
                'constraint' => 512,
                'null' => true,
            ],
            'from_email' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => true,
            ],
            'from_name' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => true,
            ],
            'reply_to_json' => [
                'type' => 'text',
                'null' => true,
            ],
            'transport' => [
                'type' => 'varchar',
                'constraint' => 128,
                'null' => true,
            ],
            'status' => [
                'type' => 'varchar',
                'constraint' => 32,
                'default' => 'pending',
                'null' => false,
            ],
            'provider_message_id' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => true,
            ],
            'provider_response' => [
                'type' => 'mediumtext',
                'null' => true,
            ],
            'error_code' => [
                'type' => 'varchar',
                'constraint' => 128,
                'null' => true,
            ],
            'error_message' => [
                'type' => 'text',
                'null' => true,
            ],
            'diagnostic_message' => [
                'type' => 'text',
                'null' => true,
            ],
            'retry_count' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
                'null' => false,
            ],
            'max_retries' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
                'null' => false,
            ],
            'next_retry_at' => $this->nullableTimestampField(),
            'created_at' => $this->timestampField(),
            'sent_at' => $this->nullableTimestampField(),
            'failed_at' => $this->nullableTimestampField(),
            'last_retry_at' => $this->nullableTimestampField(),
            'html_body' => [
                'type' => 'mediumtext',
                'null' => true,
            ],
            'text_body' => [
                'type' => 'mediumtext',
                'null' => true,
            ],
            'headers_json' => [
                'type' => 'mediumtext',
                'null' => true,
            ],
            'attachments_json' => [
                'type' => 'mediumtext',
                'null' => true,
            ],
            'metadata_json' => [
                'type' => 'mediumtext',
                'null' => true,
            ],
        ]);
        ee()->dbforge->add_key('id', true);
        ee()->dbforge->add_key('site_id');
        ee()->dbforge->add_key('message_uuid');
        ee()->dbforge->add_key('to_email');
        ee()->dbforge->add_key('transport');
        ee()->dbforge->add_key('status');
        ee()->dbforge->add_key('created_at');
        ee()->dbforge->create_table('mailroom_logs', true);
    }

    private function createQueueTable(): void
    {
        ee()->load->dbforge();

        ee()->dbforge->add_field([
            'id' => $this->idField(),
            'site_id' => $this->intField(),
            'log_id' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'null' => false,
            ],
            'message_uuid' => [
                'type' => 'varchar',
                'constraint' => 64,
                'null' => false,
            ],
            'status' => [
                'type' => 'varchar',
                'constraint' => 32,
                'default' => 'pending',
                'null' => false,
            ],
            'attempts' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
                'null' => false,
            ],
            'max_attempts' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 3,
                'null' => false,
            ],
            'next_attempt_at' => $this->nullableTimestampField(),
            'last_attempt_at' => $this->nullableTimestampField(),
            'created_at' => $this->timestampField(),
            'updated_at' => $this->timestampField(),
        ]);
        ee()->dbforge->add_key('id', true);
        ee()->dbforge->add_key('site_id');
        ee()->dbforge->add_key('log_id');
        ee()->dbforge->add_key('message_uuid');
        ee()->dbforge->add_key('status');
        ee()->dbforge->add_key('next_attempt_at');
        ee()->dbforge->create_table('mailroom_queue', true);
    }

    private function insertDefaultSettings(): void
    {
        if (! ee()->db->table_exists('mailroom_settings')) {
            return;
        }

        $siteId = (int) ee()->config->item('site_id') ?: 1;
        $now = (int) ee()->localize->now;

        $defaults = [
            'default_transport' => '',
            'default_from_email' => '',
            'default_from_name' => '',
            'default_reply_to' => '',
            'logging_mode' => 'metadata',
            'privacy_mode' => 'n',
            'recipient_masking' => 'n',
            'log_retention_days' => '90',
            'auto_retry' => 'y',
            'max_retry_attempts' => '3',
            'retry_schedule' => '5,30,120',
            'alert_on_failure' => 'n',
            'alert_email' => '',
            'alert_webhook_url' => '',
            'dev_mode' => 'normal',
            'redirect_email' => '',
            'allowlist_domains' => '',
            'intercept_core_email' => 'n',
        ];

        foreach ($defaults as $key => $value) {
            $exists = (int) ee()->db
                ->where('site_id', $siteId)
                ->where('key', $key)
                ->count_all_results('mailroom_settings');

            if ($exists > 0) {
                continue;
            }

            ee()->db->insert('mailroom_settings', [
                'site_id' => $siteId,
                'key' => $key,
                'value' => $value,
                'serialized' => 'n',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function insertDefaultTransports(): void
    {
        if (! ee()->db->table_exists('mailroom_transports')) {
            return;
        }

        $siteId = (int) ee()->config->item('site_id') ?: 1;
        $now = (int) ee()->localize->now;
        $transports = [
            'smtp' => ['name' => 'Generic SMTP', 'provider' => 'smtp'],
            'mailpit' => ['name' => 'Mailpit / Dev Capture', 'provider' => 'dev_capture'],
            'microsoft_graph' => ['name' => 'Microsoft 365 Graph', 'provider' => 'microsoft_graph'],
        ];

        foreach ($transports as $handle => $transport) {
            $exists = (int) ee()->db
                ->where('site_id', $siteId)
                ->where('handle', $handle)
                ->count_all_results('mailroom_transports');

            if ($exists > 0) {
                continue;
            }

            ee()->db->insert('mailroom_transports', [
                'site_id' => $siteId,
                'handle' => $handle,
                'name' => $transport['name'],
                'provider' => $transport['provider'],
                'enabled' => 'n',
                'is_default' => 'n',
                'settings_json' => '{}',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function idField(): array
    {
        return [
            'type' => 'int',
            'constraint' => 10,
            'unsigned' => true,
            'auto_increment' => true,
        ];
    }

    private function intField(): array
    {
        return [
            'type' => 'int',
            'constraint' => 10,
            'unsigned' => true,
            'null' => false,
        ];
    }

    private function timestampField(): array
    {
        return [
            'type' => 'int',
            'constraint' => 10,
            'unsigned' => true,
            'default' => 0,
            'null' => false,
        ];
    }

    private function nullableTimestampField(): array
    {
        return [
            'type' => 'int',
            'constraint' => 10,
            'unsigned' => true,
            'null' => true,
        ];
    }
}
