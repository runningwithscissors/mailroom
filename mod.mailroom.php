<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once __DIR__ . '/src/Services/EventService.php';
require_once __DIR__ . '/src/Services/SettingsService.php';

use ExpressionEngine\Service\Addon\Module;

class Mailroom extends Module
{
    protected $addon_name = 'mailroom';

    public function provider_webhook(): void
    {
        $settings = new \BisonDigital\Mailroom\Services\SettingsService();

        if ($settings->get('webhook_events_enabled', 'n') !== 'y') {
            $this->respond(404, ['ok' => false, 'error' => 'webhooks_disabled']);
        }

        $secret = trim((string) $settings->get('webhook_secret', ''));
        $provided = (string) (ee()->input->get_post('secret') ?: ($_SERVER['HTTP_X_MAILROOM_SECRET'] ?? ''));

        if ($secret === '' || ! hash_equals($secret, $provided)) {
            $this->respond(401, ['ok' => false, 'error' => 'invalid_secret']);
        }

        $provider = preg_replace('/[^a-z0-9_\-]/i', '', (string) ee()->input->get_post('provider')) ?: 'generic';
        $raw = (string) file_get_contents('php://input');
        $payload = json_decode($raw, true);

        if (! is_array($payload)) {
            $payload = ['raw' => $raw];
        }

        $id = (new \BisonDigital\Mailroom\Services\EventService())->record([
            'provider' => $provider,
            'event_type' => (string) ($payload['event_type'] ?? $payload['event'] ?? 'unknown'),
            'provider_event_id' => $payload['id'] ?? $payload['event_id'] ?? null,
            'provider_message_id' => $payload['message_id'] ?? $payload['MessageID'] ?? null,
            'message_uuid' => $payload['message_uuid'] ?? null,
            'recipient' => $payload['recipient'] ?? $payload['email'] ?? null,
            'severity' => 'info',
            'payload' => $payload,
            'signature_valid' => true,
        ]);

        $this->respond(202, ['ok' => true, 'event_id' => $id]);
    }

    private function respond(int $status, array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }
}
