<?php

namespace BisonDigital\Mailroom\ControlPanel;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Sidebar
{
    public function render(string $active = 'dashboard'): void
    {
        $sidebar = ee('CP/Sidebar')->make();

        $items = [
            'dashboard' => [
                'label' => lang('mailroom_nav_dashboard'),
                'url' => ee('CP/URL')->make('addons/settings/mailroom'),
            ],
            'logs' => [
                'label' => lang('mailroom_nav_logs'),
                'url' => ee('CP/URL')->make('addons/settings/mailroom/logs'),
            ],
            'failed' => [
                'label' => lang('mailroom_nav_failed'),
                'url' => ee('CP/URL')->make('addons/settings/mailroom/failed'),
            ],
            'transports' => [
                'label' => lang('mailroom_nav_transports'),
                'url' => ee('CP/URL')->make('addons/settings/mailroom/transports'),
            ],
            'diagnostics' => [
                'label' => lang('mailroom_nav_diagnostics'),
                'url' => ee('CP/URL')->make('addons/settings/mailroom/diagnostics'),
            ],
            'settings' => [
                'label' => lang('mailroom_nav_settings'),
                'url' => ee('CP/URL')->make('addons/settings/mailroom/settings'),
            ],
            'documentation' => [
                'label' => lang('mailroom_nav_documentation'),
                'url' => ee('CP/URL')->make('addons/settings/mailroom/documentation'),
            ],
        ];

        $section = $sidebar->addHeader(lang('mailroom_module_name'));
        $list = $section->addBasicList();

        foreach ($items as $key => $item) {
            $node = $list->addItem($item['label'], $item['url']);

            if ($key === $active && method_exists($node, 'isActive')) {
                $node->isActive();
            }
        }
    }
}
