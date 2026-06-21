<div class="box panel">
    <div class="panel-body">
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
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <?php
                            $message = (string) (($log['diagnostic_message'] ?? '') ?: ($log['error_message'] ?? '') ?: ($log['provider_response'] ?? ''));
                            $created = (int) ($log['created_at'] ?? 0);
                        ?>
                        <tr>
                            <td><?=$created > 0 ? ee()->localize->human_time($created) : ''?></td>
                            <td><?=htmlspecialchars((string) ($log['status'] ?? ''), ENT_QUOTES, 'UTF-8')?></td>
                            <td><?=htmlspecialchars((string) ($log['to_email'] ?? ''), ENT_QUOTES, 'UTF-8')?></td>
                            <td><?=htmlspecialchars((string) ($log['subject'] ?? ''), ENT_QUOTES, 'UTF-8')?></td>
                            <td><?=htmlspecialchars((string) ($log['transport'] ?? ''), ENT_QUOTES, 'UTF-8')?></td>
                            <td><?=htmlspecialchars((string) ($log['source'] ?? ''), ENT_QUOTES, 'UTF-8')?></td>
                            <td><?=htmlspecialchars($message, ENT_QUOTES, 'UTF-8')?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
