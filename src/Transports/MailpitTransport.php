<?php

namespace BisonDigital\Mailroom\Transports;

use BisonDigital\Mailroom\DTO\EmailMessage;
use BisonDigital\Mailroom\DTO\SendResult;
use BisonDigital\Mailroom\DTO\ValidationResult;
use Throwable;

class MailpitTransport extends SmtpTransport
{
    public function __construct(array $settings = [])
    {
        parent::__construct($this->normalizeSettings($settings));
    }

    public function getName(): string
    {
        return 'Mailpit / Dev Capture';
    }

    public function getHandle(): string
    {
        return 'mailpit';
    }

    public function validateSettings(array $settings): ValidationResult
    {
        return parent::validateSettings($this->normalizeSettings($settings));
    }

    public function send(EmailMessage $message): SendResult
    {
        $validation = $this->validateMessage($message);

        if (! $validation->valid) {
            return SendResult::failure(
                $this->provider(),
                'Email message failed validation.',
                'validation_failed',
                json_encode($validation->errors) ?: '',
                false
            );
        }

        $host = (string) $this->setting('host', '127.0.0.1');
        $port = (int) $this->setting('port', 1025);
        $transcript = sprintf('Mailroom Mailpit SMTP: host=%s port=%s encryption=%s<br />', $host, $port, (string) $this->setting('encryption', 'none'));

        try {
            $errno = 0;
            $errstr = '';
            $socket = @stream_socket_client('tcp://' . $host . ':' . $port, $errno, $errstr, (int) $this->setting('timeout', 30));

            if (! is_resource($socket)) {
                return SendResult::failure(
                    $this->provider(),
                    'Unable to connect to Mailpit SMTP.',
                    'mailpit_connect_failed',
                    $transcript . 'Connection error: ' . $errno . ' ' . $errstr,
                    true
                );
            }

            stream_set_timeout($socket, (int) $this->setting('timeout', 30));
            $transcript .= $this->expect($socket, [220], 'connect');

            if ($this->setting('encryption', 'none') === 'tls') {
                $transcript .= $this->command($socket, 'EHLO ' . $this->hostname(), [250]);
                $transcript .= $this->command($socket, 'STARTTLS', [220]);

                if (stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
                    fclose($socket);

                    return SendResult::failure(
                        $this->provider(),
                        'Unable to start TLS for Mailpit SMTP.',
                        'mailpit_tls_failed',
                        $transcript,
                        true
                    );
                }
            }

            $transcript .= $this->command($socket, 'EHLO ' . $this->hostname(), [250]);
            $transcript .= $this->command($socket, 'MAIL FROM:<' . $this->extractEmail($message->from) . '>', [250]);

            foreach (array_merge($message->to, $message->cc, $message->bcc) as $recipient) {
                $transcript .= $this->command($socket, 'RCPT TO:<' . $this->extractEmail($recipient) . '>', [250, 251]);
            }

            $transcript .= $this->command($socket, 'DATA', [354]);
            fwrite($socket, $this->rawMessage($message) . "\r\n.\r\n");
            $transcript .= $this->expect($socket, [250], 'message');
            $transcript .= $this->command($socket, 'QUIT', [221]);
            fclose($socket);

            return SendResult::captured($this->provider(), $transcript);
        } catch (Throwable $throwable) {
            if (isset($socket) && is_resource($socket)) {
                fclose($socket);
            }

            return SendResult::failure(
                $this->provider(),
                $throwable->getMessage(),
                'mailpit_smtp_exception',
                $transcript . $throwable->getFile() . ':' . $throwable->getLine(),
                true,
                $throwable
            );
        }
    }

    protected function provider(): string
    {
        return 'dev_capture';
    }

    private function normalizeSettings(array $settings): array
    {
        return [
            'host' => (string) ($settings['mailpit_host'] ?? $settings['host'] ?? '127.0.0.1'),
            'port' => (string) ($settings['mailpit_port'] ?? $settings['port'] ?? '1025'),
            'encryption' => (($settings['mailpit_tls'] ?? 'n') === 'y') ? 'tls' : 'none',
            'username' => (string) ($settings['mailpit_username'] ?? $settings['username'] ?? ''),
            'password' => (string) ($settings['mailpit_password'] ?? $settings['password'] ?? ''),
            'timeout' => (string) ($settings['timeout'] ?? '30'),
            'from_email' => (string) ($settings['from_email'] ?? ''),
            'from_name' => (string) ($settings['from_name'] ?? ($settings['mode_label'] ?? '')),
            'reply_to' => (string) ($settings['reply_to'] ?? ''),
        ];
    }

    private function command(mixed $socket, string $command, array $expectedCodes): string
    {
        fwrite($socket, $command . "\r\n");

        return 'C: ' . htmlspecialchars($command, ENT_QUOTES, 'UTF-8') . '<br />' . $this->expect($socket, $expectedCodes, $command);
    }

    private function expect(mixed $socket, array $expectedCodes, string $context): string
    {
        $reply = $this->readReply($socket);
        $code = (int) substr($reply, 0, 3);
        $transcript = 'S: ' . htmlspecialchars(str_replace("\n", "\nS: ", trim($reply)), ENT_QUOTES, 'UTF-8') . '<br />';

        if (! in_array($code, $expectedCodes, true)) {
            throw new \RuntimeException('Unexpected Mailpit SMTP response during ' . $context . ': ' . trim($reply));
        }

        return $transcript;
    }

    private function readReply(mixed $socket): string
    {
        $reply = '';

        while (($line = fgets($socket, 512)) !== false) {
            $reply .= $line;

            if (preg_match('/^\d{3}\s/', $line)) {
                break;
            }
        }

        if ($reply === '') {
            throw new \RuntimeException('Mailpit SMTP did not return a response.');
        }

        return $reply;
    }

    private function rawMessage(EmailMessage $message): string
    {
        $headers = [
            'From' => $this->formatAddress($message->from, $message->fromName),
            'To' => $this->addressList($message->to),
            'Subject' => $message->subject,
            'Date' => date(DATE_RFC2822),
            'MIME-Version' => '1.0',
        ];

        if ($message->replyTo !== []) {
            $headers['Reply-To'] = $this->addressList($message->replyTo);
        }

        foreach ($message->headers as $name => $value) {
            if (is_string($name) && $name !== '' && is_scalar($value) && ! isset($headers[$name])) {
                $headers[$name] = (string) $value;
            }
        }

        if ($message->htmlBody !== '') {
            $headers['Content-Type'] = 'text/html; charset=utf-8';
            $body = $message->htmlBody;
        } else {
            $headers['Content-Type'] = 'text/plain; charset=utf-8';
            $body = $message->textBody;
        }

        $raw = '';

        foreach ($headers as $name => $value) {
            if ((string) $value !== '') {
                $raw .= $name . ': ' . str_replace(["\r", "\n"], '', (string) $value) . "\r\n";
            }
        }

        return $raw . "\r\n" . preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], "\n", $body));
    }

    private function formatAddress(string $email, string $name = ''): string
    {
        if ($name === '') {
            return $email;
        }

        return '"' . addcslashes($name, "\"\\") . '" <' . $email . '>';
    }

    private function hostname(): string
    {
        $siteUrl = (string) ee()->config->item('site_url');
        $host = $siteUrl !== '' ? parse_url($siteUrl, PHP_URL_HOST) : '';

        return is_string($host) && $host !== '' ? $host : 'localhost';
    }
}
