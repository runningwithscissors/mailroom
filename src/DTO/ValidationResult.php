<?php

namespace BisonDigital\Mailroom\DTO;

class ValidationResult
{
    public function __construct(
        public bool $valid = true,
        public array $errors = [],
        public array $warnings = [],
    ) {
    }

    public static function valid(array $warnings = []): self
    {
        return new self(true, [], $warnings);
    }

    public static function invalid(array $errors, array $warnings = []): self
    {
        return new self(false, $errors, $warnings);
    }

    public function addError(string $field, string $message): void
    {
        $this->valid = false;
        $this->errors[$field][] = $message;
    }

    public function addWarning(string $field, string $message): void
    {
        $this->warnings[$field][] = $message;
    }

    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
