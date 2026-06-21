<?php

namespace BisonDigital\Mailroom\Services;

use BisonDigital\Mailroom\Transports\TransportInterface;
use InvalidArgumentException;

class TransportManager
{
    /** @var array<string, TransportInterface> */
    private array $transports = [];

    public function register(TransportInterface $transport): void
    {
        $this->transports[$transport->getHandle()] = $transport;
    }

    public function get(string $handle): TransportInterface
    {
        if (! isset($this->transports[$handle])) {
            throw new InvalidArgumentException('Mailroom transport is not registered: ' . $handle);
        }

        return $this->transports[$handle];
    }

    public function has(string $handle): bool
    {
        return isset($this->transports[$handle]);
    }

    public function all(): array
    {
        return $this->transports;
    }
}
