<div class="box panel">
    <div class="panel-body">
        <?php
            $settings = is_array($settings ?? null) ? $settings : [];
            $privacyMode = ($settings['privacy_mode'] ?? 'n') === 'y';
            $maskRecipients = ($settings['recipient_masking'] ?? 'n') === 'y';

            $maskEmail = static function (string $email): string {
                if ($email === '' || ! str_contains($email, '@')) {
                    return $email;
                }

                [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

                return substr($local, 0, 1) . '***@' . $domain;
            };

            $bodyPreview = static function (array $log): string {
                $body = (string) (($log['text_body'] ?? '') ?: strip_tags((string) ($log['html_body'] ?? '')));

                return trim(preg_replace('/\s+/', ' ', $body) ?? $body);
            };
        ?>
        <?php if (empty($logs)): ?>
            <p><?=lang('mailroom_no_logs')?></p>
        <?php else: ?>
            <table class="mainTable">
                <thead>
                    <tr>
                        <th><?=lang('mailroom_created')?></th>
                        <th><?=lang('mailroom_status')?></th>
                        <th><?=lang('mailroom_recipient')?></th>
                        <th><?=lang('mailroom_subject')?></th>
                        <th><?=lang('mailroom_transport')?></th>
                        <th><?=lang('mailroom_source')?></th>
                        <th><?=lang('mailroom_message')?></th>
                        <th>Email Body</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <?php
                            $message = (string) (($log['diagnostic_message'] ?? '') ?: ($log['error_message'] ?? '') ?: ($log['provider_response'] ?? ''));
                            $created = (int) ($log['created_at'] ?? 0);
                            $recipient = (string) ($log['to_email'] ?? '');
                            $subject = (string) ($log['subject'] ?? '');
                            $body = $bodyPreview($log);

                            if ($maskRecipients) {
                                $recipient = $maskEmail($recipient);
                            }

                            if ($privacyMode) {
                                $recipient = $recipient !== '' ? $maskEmail($recipient) : '';
                                $subject = '[hidden by privacy mode]';
                                $message = '[hidden by privacy mode]';
                                $body = '';
                            }
                        ?>
                        <tr>
                            <td><?=$created > 0 ? ee()->localize->human_time($created) : ''?></td>
                            <td><?=htmlspecialchars((string) ($log['status'] ?? ''), ENT_QUOTES, 'UTF-8')?></td>
                            <td><?=htmlspecialchars($recipient, ENT_QUOTES, 'UTF-8')?></td>
                            <td><?=htmlspecialchars($subject, ENT_QUOTES, 'UTF-8')?></td>
                            <td><?=htmlspecialchars((string) ($log['transport'] ?? ''), ENT_QUOTES, 'UTF-8')?></td>
                            <td><?=htmlspecialchars((string) ($log['source'] ?? ''), ENT_QUOTES, 'UTF-8')?></td>
                            <td><?=htmlspecialchars($message, ENT_QUOTES, 'UTF-8')?></td>
                            <td>
                                <?php if ($privacyMode): ?>
                                    [hidden by privacy mode]
                                <?php elseif ($body !== ''): ?>
                                    <details>
                                        <summary><?=htmlspecialchars(substr($body, 0, 80), ENT_QUOTES, 'UTF-8')?><?=$body !== substr($body, 0, 80) ? '...' : ''?></summary>
                                        <pre style="white-space: pre-wrap; max-width: 42rem;"><?=htmlspecialchars((string) (($log['text_body'] ?? '') ?: ($log['html_body'] ?? '')), ENT_QUOTES, 'UTF-8')?></pre>
                                    </details>
                                <?php else: ?>
                                    <span class="meta-info">Not stored</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
