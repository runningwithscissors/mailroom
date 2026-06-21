<?php

namespace BisonDigital\Mailroom\Transports;

use BisonDigital\Mailroom\DTO\EmailMessage;
use BisonDigital\Mailroom\DTO\SendResult;
use BisonDigital\Mailroom\DTO\ValidationResult;
use Throwable;

class SmtpTransport extends AbstractTransport
{
    public function getName(): string
    {
        return 'Generic SMTP';
    }

    public function getHandle(): string
    {
        return 'smtp';
    }

    public function validateSettings(array $settings): ValidationResult
    {
        $result = ValidationResult::valid();

        if (trim((string) ($settings['host'] ?? '')) === '') {
            $result->addError('host', 'SMTP host is required.');
        }

        $port = (int) ($settings['port'] ?? 0);
        if ($port < 1 || $port > 65535) {
            $result->addError('port', 'SMTP port must be between 1 and 65535.');
        }

        $encryption = (string) ($settings['encryption'] ?? 'tls');
        if (! in_array($encryption, ['tls', 'ssl', 'none'], true)) {
            $result->addError('encryption', 'SMTP encryption must be TLS, SSL, or none.');
        }

        $fromEmail = trim((string) ($settings['from_email'] ?? ''));
        if ($fromEmail !== '' && ! filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $result->addError('from_email', 'Default from email is invalid.');
        }

        $replyTo = trim((string) ($settings['reply_to'] ?? ''));
        if ($replyTo !== '' && ! filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $result->addError('reply_to', 'Default reply-to email is invalid.');
        }

        return $result;
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

        try {
            ee()->load->library('email');
            $email = new \EE_Email();
            $email->initialize($this->emailConfig());
            $email->clear(true);

            $from = $message->from !== '' ? $message->from : (string) $this->setting('from_email', '');
            $fromName = $message->fromName !== '' ? $message->fromName : (string) $this->setting('from_name', '');

            $email->from($from, $fromName);
            $email->to($this->addressList($message->to));

            if ($message->cc !== []) {
                $email->cc($this->addressList($message->cc));
            }

            if ($message->bcc !== []) {
                $email->bcc($this->addressList($message->bcc));
            }

            $replyTo = $message->replyTo !== []
                ? $this->firstAddress($message->replyTo)
                : (string) $this->setting('reply_to', '');

            if ($replyTo !== '') {
                $email->reply_to($replyTo);
            }

            foreach ($message->headers as $name => $value) {
                if (is_string($name) && $name !== '' && is_scalar($value)) {
                    $email->set_header($name, (string) $value);
                }
            }

            $email->subject($message->subject);

            if ($message->htmlBody !== '') {
                $email->set_mailtype('html');
                $email->message($message->htmlBody);

                if ($message->textBody !== '') {
                    $email->set_alt_message($message->textBody);
                }
            } else {
                $email->set_mailtype('text');
                $email->message($message->textBody);
            }

            foreach ($message->attachments as $attachment) {
                $this->attach($email, $attachment);
            }

            $sent = $email->send(false);
            $debug = (string) $email->print_debugger();

            if (! $sent) {
                return SendResult::failure(
                    $this->provider(),
                    'SMTP delivery failed.',
                    'smtp_send_failed',
                    $this->debugContext() . $debug,
                    true
                );
            }

            return SendResult::success($this->provider(), '', $debug);
        } catch (Throwable $throwable) {
            return SendResult::failure(
                $this->provider(),
                $throwable->getMessage(),
                'smtp_exception',
                $throwable->getFile() . ':' . $throwable->getLine(),
                true,
                $throwable
            );
        }
    }

    protected function provider(): string
    {
        return 'smtp';
    }

    protected function emailConfig(): array
    {
        $config = [
            'protocol' => 'smtp',
            'smtp_host' => (string) $this->setting('host', ''),
            'smtp_port' => (int) $this->setting('port', 587),
            'smtp_user' => (string) $this->setting('username', ''),
            'smtp_pass' => (string) $this->setting('password', ''),
            'mailtype' => 'html',
            'charset' => (string) (ee()->config->item('email_charset') ?: 'utf-8'),
            'newline' => "\r\n",
            'crlf' => "\r\n",
            'smtp_timeout' => (int) $this->setting('timeout', 30),
        ];

        $encryption = (string) $this->setting('encryption', 'tls');
        if (in_array($encryption, ['tls', 'ssl'], true)) {
            $config['smtp_crypto'] = $encryption;
        }

        return $config;
    }

    protected function debugContext(): string
    {
        return sprintf(
            'Mailroom SMTP config: provider=%s host=%s port=%s encryption=%s username=%s timeout=%s<br />',
            $this->provider(),
            (string) $this->setting('host', ''),
            (string) $this->setting('port', ''),
            (string) $this->setting('encryption', ''),
            (string) $this->setting('username', '') !== '' ? 'set' : 'blank',
            (string) $this->setting('timeout', '')
        );
    }

    protected function validateMessage(EmailMessage $message): ValidationResult
    {
        $result = ValidationResult::valid();
        $from = $message->from !== '' ? $message->from : (string) $this->setting('from_email', '');

        if ($message->to === []) {
            $result->addError('to', 'At least one recipient is required.');
        }

        if ($from === '' || ! filter_var($from, FILTER_VALIDATE_EMAIL)) {
            $result->addError('from', 'A valid from email is required.');
        }

        if ($message->subject === '') {
            $result->addError('subject', 'Subject is required.');
        }

        if ($message->htmlBody === '' && $message->textBody === '') {
            $result->addError('body', 'An HTML or text body is required.');
        }

        foreach (['to' => $message->to, 'cc' => $message->cc, 'bcc' => $message->bcc, 'reply_to' => $message->replyTo] as $field => $addresses) {
            foreach ($addresses as $address) {
                $email = $this->extractEmail($address);
                if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $result->addError($field, 'Invalid email address: ' . (is_scalar($address) ? (string) $address : json_encode($address)));
                }
            }
        }

        return $result;
    }

    protected function addressList(array $addresses): string
    {
        return implode(', ', array_values(array_filter(array_map(fn ($address): string => $this->extractEmail($address), $addresses))));
    }

    protected function firstAddress(array $addresses): string
    {
        foreach ($addresses as $address) {
            $email = $this->extractEmail($address);
            if ($email !== '') {
                return $email;
            }
        }

        return '';
    }

    protected function extractEmail(mixed $address): string
    {
        if (is_string($address)) {
            return trim($address);
        }

        if (! is_array($address)) {
            return '';
        }

        return trim((string) ($address['email'] ?? $address['address'] ?? $address[0] ?? ''));
    }

    private function attach(\EE_Email $email, mixed $attachment): void
    {
        if (is_string($attachment) && $attachment !== '') {
            $email->attach($attachment);

            return;
        }

        if (! is_array($attachment)) {
            return;
        }

        $path = (string) ($attachment['path'] ?? $attachment['file'] ?? '');
        if ($path === '') {
            return;
        }

        $email->attach(
            $path,
            (string) ($attachment['disposition'] ?? 'attachment'),
            $attachment['name'] ?? null,
            (string) ($attachment['mime'] ?? '')
        );
    }
}
