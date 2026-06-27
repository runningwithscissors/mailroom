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
            <form action="<?=$action_url?>" method="post">
                <input type="hidden" name="csrf_token" value="<?=CSRF_TOKEN?>">

                <div class="mailroom-log-actions">
                    <select name="mailroom_action">
                        <option value="delete_logs"><?=lang('mailroom_delete_selected_logs')?></option>
                        <option value="delete_log_bodies"><?=lang('mailroom_delete_selected_log_bodies')?></option>
                    </select>
                    <button class="btn action" onclick="return confirm('<?=htmlspecialchars(lang('mailroom_confirm_log_delete'), ENT_QUOTES, 'UTF-8')?>');"><?=lang('mailroom_apply')?></button>
                </div>

                <table class="mainTable">
                    <thead>
                        <tr>
                            <th><input type="checkbox" data-mailroom-select-all></th>
                            <th><?=lang('mailroom_created')?></th>
                            <th><?=lang('mailroom_status')?></th>
                            <th><?=lang('mailroom_recipient')?></th>
                            <th><?=lang('mailroom_subject')?></th>
                            <th><?=lang('mailroom_transport')?></th>
                            <th><?=lang('mailroom_source')?></th>
                            <th><?=lang('mailroom_message')?></th>
                            <th><?=lang('mailroom_email_body')?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <?php
                            $id = (int) ($log['id'] ?? 0);
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
                            <td><input type="checkbox" name="log_ids[]" value="<?=$id?>"></td>
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
            </form>
        <?php endif; ?>
    </div>
</div>

<style>
    .mailroom-notice {
        margin: 0 0 16px;
        padding: 12px 14px;
        border-radius: 4px;
        border: 1px solid #b7dec2;
        background: #effaf2;
        color: #1f6b33;
    }

    .mailroom-notice--issue {
        border-color: #f0c2c2;
        background: #fff2f2;
        color: #8f2626;
    }

    .mailroom-log-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        margin: 0 0 12px;
    }
</style>

<script>
    document.querySelectorAll('[data-mailroom-select-all]').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            document.querySelectorAll('input[name="log_ids[]"]').forEach(function (checkbox) {
                checkbox.checked = toggle.checked;
            });
        });
    });
</script>
