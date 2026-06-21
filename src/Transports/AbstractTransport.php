<?php

namespace BisonDigital\Mailroom\Transports;

use BisonDigital\Mailroom\DTO\EmailMessage;
use BisonDigital\Mailroom\DTO\SendResult;
use BisonDigital\Mailroom\DTO\ValidationResult;

abstract class AbstractTransport implements TransportInterface
{
    public function __construct(protected array $settings = [])
    {
    }

    public function isConfigured(): bool
    {
        return $this->validateSettings($this->settings)->valid;
    }

    public function validateSettings(array $settings): ValidationResult
    {
        return ValidationResult::valid();
    }

    public function test(array $payload = []): SendResult
    {
        return $this->send(EmailMessage::fromArray($payload));
    }

    public function getDiagnostics(): array
    {
        return [];
    }

    protected function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }
}
