<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once __DIR__ . '/ControlPanel/Sidebar.php';
require_once __DIR__ . '/src/DTO/EmailMessage.php';
require_once __DIR__ . '/src/DTO/SendResult.php';
require_once __DIR__ . '/src/DTO/ValidationResult.php';
require_once __DIR__ . '/src/Services/LogService.php';
require_once __DIR__ . '/src/Services/MailerService.php';
require_once __DIR__ . '/src/Services/SettingsService.php';
require_once __DIR__ . '/src/Services/ExtensionHookService.php';
require_once __DIR__ . '/src/Services/TransportFactory.php';
require_once __DIR__ . '/src/Services/TransportRepository.php';
require_once __DIR__ . '/src/Services/Auth/OAuthClient.php';
require_once __DIR__ . '/src/Services/Auth/TokenStore.php';
require_once __DIR__ . '/src/Transports/TransportInterface.php';
require_once __DIR__ . '/src/Transports/AbstractTransport.php';
require_once __DIR__ . '/src/Transports/SmtpTransport.php';
require_once __DIR__ . '/src/Transports/MailpitTransport.php';
require_once __DIR__ . '/src/Transports/MicrosoftGraphTransport.php';

class Mailroom_mcp
{
    private string $baseUrl;

    public function __construct()
    {
        ee()->lang->loadfile('mailroom');

        $this->baseUrl = ee('CP/URL')->make('addons/settings/mailroom')->compile();
        $this->ensureEmailHook();
    }

    public function index(): array
    {
        $this->renderSidebar('dashboard');

        $logs = new \BisonDigital\Mailroom\Services\LogService();
        $settings = new \BisonDigital\Mailroom\Services\SettingsService();
        $activeTransport = (string) $settings->get('default_transport', '');

        $vars = [
            'base_url' => $this->baseUrl,
            'cards' => [
                [
                    'label' => lang('mailroom_active_transport'),
                    'value' => $activeTransport !== '' ? $activeTransport : lang('mailroom_not_configured'),
                    'status' => 'warning',
                ],
                [
                    'label' => lang('mailroom_recent_sends'),
                    'value' => (string) $logs->countRecentSends(),
                    'status' => 'neutral',
                ],
                [
                    'label' => lang('mailroom_recent_failures'),
                    'value' => (string) $logs->countRecentFailures(),
                    'status' => 'neutral',
                ],
                [
                    'label' => lang('mailroom_retry_queue'),
                    'value' => (string) $logs->countQueuedRetries(),
                    'status' => 'neutral',
                ],
            ],
            'latest_failures' => $logs->latestFailures(),
        ];

        return [
            'heading' => lang('mailroom_module_name'),
            'breadcrumb' => [
                ee('CP/URL')->make('addons')->compile() => lang('addons_module_name'),
            ],
            'body' => ee('View')->make('mailroom:dashboard')->render($vars),
        ];
    }

    public function logs(): array
    {
        $this->renderSidebar('logs');

        $logs = new \BisonDigital\Mailroom\Services\LogService();
        $settings = new \BisonDigital\Mailroom\Services\SettingsService();

        return [
            'heading' => lang('mailroom_nav_logs'),
            'breadcrumb' => [
                ee('CP/URL')->make('addons')->compile() => lang('addons_module_name'),
                $this->baseUrl => lang('mailroom_module_name'),
            ],
            'body' => ee('View')->make('mailroom:logs/index')->render([
                'logs' => $logs->latest(),
                'settings' => $settings->all(),
            ]),
        ];
    }

    public function failed(): array
    {
        return $this->placeholder('failed', 'mailroom_nav_failed', 'mailroom_placeholder_failed');
    }

    public function transports(): array
    {
        $this->renderSidebar('transports');

        $repository = new \BisonDigital\Mailroom\Services\TransportRepository();
        $settings = new \BisonDigital\Mailroom\Services\SettingsService();
        $repository->seedDefaults();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $default = (string) ee()->input->post('default_transport');
            $enabled = ee()->input->post('enabled');
            $enabledHandles = is_array($enabled) ? array_map('strval', $enabled) : [];

            foreach ($repository->all() as $transport) {
                $handle = (string) $transport['handle'];
                $isDefault = $default !== '' && $handle === $default;
                $repository->updateState(
                    $handle,
                    $isDefault || in_array($handle, $enabledHandles, true),
                    $isDefault
                );
            }

            $settings->set('default_transport', $default);

            ee('CP/Alert')->makeInline('mailroom-transports-saved')
                ->asSuccess()
                ->withTitle(lang('mailroom_transports_saved'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/mailroom/transports'));
        }

        return [
            'heading' => lang('mailroom_nav_transports'),
            'breadcrumb' => [
                ee('CP/URL')->make('addons')->compile() => lang('addons_module_name'),
                $this->baseUrl => lang('mailroom_module_name'),
            ],
            'body' => ee('View')->make('mailroom:transports/index')->render([
                'action_url' => ee('CP/URL')->make('addons/settings/mailroom/transports'),
                'transports' => $repository->all(),
                'default_transport' => (string) $settings->get('default_transport', ''),
            ]),
        ];
    }

    public function diagnostics(): array
    {
        return $this->placeholder('diagnostics', 'mailroom_nav_diagnostics', 'mailroom_placeholder_diagnostics');
    }

    public function smtp(): array
    {
        $this->renderSidebar('transports');

        $repository = new \BisonDigital\Mailroom\Services\TransportRepository();
        $repository->seedDefaults();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = (string) (ee()->input->post('smtp_password') ?: ee()->input->post('password'));
            $current = $repository->settingsFor('smtp');

            $settings = [
                'host' => (string) ee()->input->post('host'),
                'port' => (string) ee()->input->post('port'),
                'encryption' => (string) ee()->input->post('encryption'),
                'username' => (string) ee()->input->post('username'),
                'password' => $password !== '' ? $password : (string) ($current['password'] ?? ''),
                'from_email' => (string) ee()->input->post('from_email'),
                'from_name' => (string) ee()->input->post('from_name'),
                'reply_to' => (string) ee()->input->post('reply_to'),
                'timeout' => (string) ee()->input->post('timeout'),
            ];

            $repository->updateSettings('smtp', $settings);

            ee('CP/Alert')->makeInline('mailroom-smtp-saved')
                ->asSuccess()
                ->withTitle(lang('mailroom_smtp_saved'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/mailroom/smtp'));
        }

        return [
            'heading' => lang('mailroom_smtp_settings'),
            'breadcrumb' => [
                ee('CP/URL')->make('addons')->compile() => lang('addons_module_name'),
                $this->baseUrl => lang('mailroom_module_name'),
                ee('CP/URL')->make('addons/settings/mailroom/transports')->compile() => lang('mailroom_nav_transports'),
            ],
            'body' => ee('View')->make('mailroom:transports/smtp')->render([
                'action_url' => ee('CP/URL')->make('addons/settings/mailroom/smtp'),
                'settings' => $repository->settingsFor('smtp'),
            ]),
        ];
    }

    public function dev_capture(): array
    {
        $this->renderSidebar('transports');

        $repository = new \BisonDigital\Mailroom\Services\TransportRepository();
        $repository->seedDefaults();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = (string) ee()->input->post('mailpit_password');
            $current = $repository->settingsFor('mailpit');

            $settings = [
                'mode_label' => (string) ee()->input->post('mode_label'),
                'mailpit_host' => (string) ee()->input->post('mailpit_host'),
                'mailpit_port' => (string) ee()->input->post('mailpit_port'),
                'mailpit_tls' => ee()->input->post('mailpit_tls') ? 'y' : 'n',
                'mailpit_username' => (string) ee()->input->post('mailpit_username'),
                'mailpit_password' => $password !== '' ? $password : (string) ($current['mailpit_password'] ?? ''),
            ];

            $repository->updateSettings('mailpit', $settings);

            ee('CP/Alert')->makeInline('mailroom-dev-capture-saved')
                ->asSuccess()
                ->withTitle(lang('mailroom_dev_capture_saved'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/mailroom/dev_capture'));
        }

        return [
            'heading' => lang('mailroom_dev_capture_settings'),
            'breadcrumb' => [
                ee('CP/URL')->make('addons')->compile() => lang('addons_module_name'),
                $this->baseUrl => lang('mailroom_module_name'),
                ee('CP/URL')->make('addons/settings/mailroom/transports')->compile() => lang('mailroom_nav_transports'),
            ],
            'body' => ee('View')->make('mailroom:transports/dev_capture')->render([
                'action_url' => ee('CP/URL')->make('addons/settings/mailroom/dev_capture'),
                'settings' => $repository->settingsFor('mailpit'),
            ]),
        ];
    }

    public function microsoft_graph(): array
    {
        $this->renderSidebar('transports');

        $repository = new \BisonDigital\Mailroom\Services\TransportRepository();
        $repository->seedDefaults();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $current = $repository->settingsFor('microsoft_graph');
            $clientSecret = (string) ee()->input->post('client_secret');

            $settings = [
                'tenant_id' => (string) ee()->input->post('tenant_id'),
                'client_id' => (string) ee()->input->post('client_id'),
                'client_secret' => $clientSecret !== '' ? $clientSecret : (string) ($current['client_secret'] ?? ''),
                'sender' => (string) ee()->input->post('sender'),
                'save_to_sent_items' => ee()->input->post('save_to_sent_items') ? 'y' : 'n',
                'test_recipient' => (string) ee()->input->post('test_recipient'),
            ];

            $repository->updateSettings('microsoft_graph', $settings);

            if ($this->graphCredentialsChanged($current, $settings)) {
                (new \BisonDigital\Mailroom\Services\Auth\TokenStore())->clear(
                    'microsoft_graph',
                    $repository->idFor('microsoft_graph')
                );
            }

            if ((string) ee()->input->post('mailroom_action') === 'test') {
                $recipient = trim((string) ee()->input->post('test_recipient'));

                if ($recipient === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                    ee('CP/Alert')->makeInline('mailroom-graph-test')
                        ->asIssue()
                        ->withTitle(lang('mailroom_graph_test_failed'))
                        ->addToBody(lang('mailroom_graph_test_recipient_required'))
                        ->defer();
                } else {
                    $result = (new \BisonDigital\Mailroom\Services\MailerService())->send([
                        'to' => [$recipient],
                        'from' => (string) ee()->input->post('sender'),
                        'from_name' => lang('mailroom_module_name'),
                        'subject' => lang('mailroom_graph_test_subject'),
                        'text' => lang('mailroom_graph_test_body'),
                        'source' => 'mailroom_cp',
                        'metadata' => ['source_label' => 'Microsoft Graph Test'],
                    ], 'microsoft_graph');

                    $alert = ee('CP/Alert')->makeInline('mailroom-graph-test');

                    if ($result->success) {
                        $alert->asSuccess()
                            ->withTitle(lang('mailroom_graph_test_sent'))
                            ->addToBody($result->providerResponse !== '' ? $result->providerResponse : lang('mailroom_graph_test_sent_desc'))
                            ->defer();
                    } else {
                        $alert->asIssue()
                            ->withTitle(lang('mailroom_graph_test_failed'))
                            ->addToBody($this->safeDiagnostic($result->errorMessage, $result->diagnosticMessage))
                            ->defer();
                    }
                }
            } else {
                ee('CP/Alert')->makeInline('mailroom-graph-saved')
                    ->asSuccess()
                    ->withTitle(lang('mailroom_graph_saved'))
                    ->defer();
            }

            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/mailroom/microsoft_graph'));
        }

        return [
            'heading' => lang('mailroom_graph_settings'),
            'breadcrumb' => [
                ee('CP/URL')->make('addons')->compile() => lang('addons_module_name'),
                $this->baseUrl => lang('mailroom_module_name'),
                ee('CP/URL')->make('addons/settings/mailroom/transports')->compile() => lang('mailroom_nav_transports'),
            ],
            'body' => ee('View')->make('mailroom:transports/microsoft_graph')->render([
                'action_url' => ee('CP/URL')->make('addons/settings/mailroom/microsoft_graph'),
                'settings' => $repository->settingsFor('microsoft_graph'),
            ]),
        ];
    }

    public function settings(): array
    {
        $this->renderSidebar('settings');

        $settings = new \BisonDigital\Mailroom\Services\SettingsService();
        $repository = new \BisonDigital\Mailroom\Services\TransportRepository();
        $repository->seedDefaults();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $keys = [
                'default_transport',
                'default_from_email',
                'default_from_name',
                'default_reply_to',
                'logging_mode',
                'log_retention_days',
                'max_retry_attempts',
                'retry_schedule',
                'alert_email',
                'alert_webhook_url',
                'dev_mode',
                'redirect_email',
                'allowlist_domains',
                'intercept_core_email',
            ];

            foreach ($keys as $key) {
                $settings->set($key, (string) ee()->input->post($key));
            }

            $repository->setDefault((string) ee()->input->post('default_transport'));

            foreach (['privacy_mode', 'recipient_masking', 'auto_retry', 'alert_on_failure', 'intercept_core_email'] as $key) {
                $settings->set($key, ee()->input->post($key) ? 'y' : 'n');
            }

            $this->ensureEmailHook();

            ee('CP/Alert')->makeInline('mailroom-settings-saved')
                ->asSuccess()
                ->withTitle(lang('mailroom_settings_saved'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/mailroom/settings'));
        }

        return [
            'heading' => lang('mailroom_nav_settings'),
            'breadcrumb' => [
                ee('CP/URL')->make('addons')->compile() => lang('addons_module_name'),
                $this->baseUrl => lang('mailroom_module_name'),
            ],
            'body' => ee('View')->make('mailroom:settings/index')->render([
                'action_url' => ee('CP/URL')->make('addons/settings/mailroom/settings'),
                'settings' => $settings->all(),
                'transport_choices' => $repository->enabledChoices(),
            ]),
        ];
    }

    private function placeholder(string $active, string $headingKey, string $messageKey): array
    {
        $this->renderSidebar($active);

        return [
            'heading' => lang($headingKey),
            'breadcrumb' => [
                ee('CP/URL')->make('addons')->compile() => lang('addons_module_name'),
                $this->baseUrl => lang('mailroom_module_name'),
            ],
            'body' => ee('View')->make('mailroom:placeholder')->render([
                'message' => lang($messageKey),
            ]),
        ];
    }

    private function renderSidebar(string $active): void
    {
        (new \BisonDigital\Mailroom\ControlPanel\Sidebar())->render($active);
    }

    private function ensureEmailHook(): void
    {
        (new \BisonDigital\Mailroom\Services\ExtensionHookService())->ensureEmailHook('0.2.3');
    }

    private function safeDiagnostic(string $errorMessage, string $diagnosticMessage): string
    {
        $message = trim($errorMessage . ($diagnosticMessage !== '' ? "\n" . $diagnosticMessage : ''));
        $message = preg_replace('/Bearer\s+[A-Za-z0-9._~+\/=-]+/i', 'Bearer [redacted]', $message) ?? $message;
        $message = preg_replace('/(client_secret=)[^&\s]+/i', '$1[redacted]', $message) ?? $message;

        return $message !== '' ? $message : lang('mailroom_graph_test_failed_desc');
    }

    private function graphCredentialsChanged(array $current, array $settings): bool
    {
        foreach (['tenant_id', 'client_id', 'client_secret', 'sender'] as $key) {
            if ((string) ($current[$key] ?? '') !== (string) ($settings[$key] ?? '')) {
                return true;
            }
        }

        return false;
    }
}
