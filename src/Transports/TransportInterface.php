<?php

namespace BisonDigital\Mailroom\Transports;

use BisonDigital\Mailroom\DTO\EmailMessage;
use BisonDigital\Mailroom\DTO\SendResult;
use BisonDigital\Mailroom\DTO\ValidationResult;

interface TransportInterface
{
    public function getName(): string;

    public function getHandle(): string;

    public function isConfigured(): bool;

    public function validateSettings(array $settings): ValidationResult;

    public function send(EmailMessage $message): SendResult;

    public function test(array $payload = []): SendResult;

    public function getDiagnostics(): array;
}
