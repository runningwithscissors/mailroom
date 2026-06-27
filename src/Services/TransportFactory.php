<?php

namespace BisonDigital\Mailroom\Services;

use BisonDigital\Mailroom\Transports\GoogleGmailTransport;
use BisonDigital\Mailroom\Transports\MailpitTransport;
use BisonDigital\Mailroom\Transports\MicrosoftGraphTransport;
use BisonDigital\Mailroom\Transports\SmtpTransport;
use BisonDigital\Mailroom\Transports\TransportInterface;
use InvalidArgumentException;

class TransportFactory
{
    public function __construct(private ?TransportRepository $repository = null)
    {
        $this->repository ??= new TransportRepository();
    }

    public function make(string $handle): TransportInterface
    {
        $transport = $this->repository->findByHandle($handle);

        if (! $transport) {
            throw new InvalidArgumentException('Mailroom transport does not exist: ' . $handle);
        }

        $settings = json_decode((string) ($transport['settings_json'] ?? '{}'), true);
        $settings = is_array($settings) ? $settings : [];

        return match ((string) ($transport['provider'] ?? $handle)) {
            'smtp' => new SmtpTransport($settings),
            'dev_capture' => new MailpitTransport($settings),
            'microsoft_graph' => new MicrosoftGraphTransport($settings, (int) ($transport['id'] ?? 0)),
            'google_gmail' => new GoogleGmailTransport($settings, (int) ($transport['id'] ?? 0)),
            default => throw new InvalidArgumentException('Mailroom transport provider is unsupported: ' . (string) ($transport['provider'] ?? '')),
        };
    }
}
