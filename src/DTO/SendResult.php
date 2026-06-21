<?php

namespace BisonDigital\Mailroom\DTO;

class SendResult
{
    public function __construct(
        public bool $success = false,
        public string $status = 'failed',
        public string $provider = '',
        public string $providerMessageId = '',
        public string $providerResponse = '',
        public string $errorCode = '',
        public string $errorMessage = '',
        public string $diagnosticMessage = '',
        public mixed $rawResponse = null,
        public bool $retryable = false,
        public int $timestamp = 0,
    ) {
        if ($this->timestamp === 0 && function_exists('ee')) {
            $this->timestamp = (int) ee()->localize->now;
        }

        if ($this->timestamp === 0) {
            $this->timestamp = time();
        }
    }

    public static function success(
        string $provider,
        string $providerMessageId = '',
        string $providerResponse = '',
        mixed $rawResponse = null
    ): self {
        return new self(
            success: true,
            status: 'sent',
            provider: $provider,
            providerMessageId: $providerMessageId,
            providerResponse: $providerResponse,
            rawResponse: $rawResponse,
            retryable: false,
        );
    }

    public static function failure(
        string $provider,
        string $errorMessage,
        string $errorCode = '',
        string $diagnosticMessage = '',
        bool $retryable = false,
        mixed $rawResponse = null
    ): self {
        return new self(
            success: false,
            status: 'failed',
            provider: $provider,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            diagnosticMessage: $diagnosticMessage,
            rawResponse: $rawResponse,
            retryable: $retryable,
        );
    }

    public static function captured(string $provider, string $message = ''): self
    {
        return new self(
            success: true,
            status: 'captured',
            provider: $provider,
            providerResponse: $message,
            retryable: false,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'status' => $this->status,
            'provider' => $this->provider,
            'provider_message_id' => $this->providerMessageId,
            'provider_response' => $this->providerResponse,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
            'diagnostic_message' => $this->diagnosticMessage,
            'raw_response' => $this->rawResponse,
            'retryable' => $this->retryable,
            'timestamp' => $this->timestamp,
        ];
    }
}
