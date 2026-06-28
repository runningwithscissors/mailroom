<div class="box panel">
    <div class="panel-body">
        <p><?=lang('mailroom_dashboard_intro')?></p>
    </div>
</div>

<div class="box panel">
    <div class="panel-body">
        <div class="mailroom-dashboard-grid">
            <?php foreach ($cards as $card): ?>
                <div class="mailroom-dashboard-card">
                    <div class="mailroom-dashboard-card__label"><?=htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8')?></div>
                    <div class="mailroom-dashboard-card__value"><?=htmlspecialchars($card['value'], ENT_QUOTES, 'UTF-8')?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="box panel">
    <div class="panel-heading">
        <h2><?=lang('mailroom_recent_failures')?></h2>
    </div>
    <div class="panel-body">
        <?php if (empty($latest_failures)): ?>
            <p><?=lang('mailroom_no_recent_failures')?></p>
        <?php else: ?>
            <table class="mainTable">
                <thead>
                    <tr>
                        <th><?=lang('mailroom_recipient')?></th>
                        <th><?=lang('mailroom_subject')?></th>
                        <th><?=lang('mailroom_error')?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latest_failures as $failure): ?>
                        <?php $error = (string) (($failure['diagnostic_message'] ?? '') ?: ($failure['error_message'] ?? '')); ?>
                        <tr>
                            <td><?=htmlspecialchars((string) ($failure['to_email'] ?? ''), ENT_QUOTES, 'UTF-8')?></td>
                            <td><?=htmlspecialchars((string) ($failure['subject'] ?? ''), ENT_QUOTES, 'UTF-8')?></td>
                            <td><?=htmlspecialchars($error, ENT_QUOTES, 'UTF-8')?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
    .mailroom-dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
    }

    .mailroom-dashboard-card {
        border: 1px solid #dfe3e6;
        border-radius: 4px;
        padding: 14px;
        background: #fff;
    }

    .mailroom-dashboard-card__label {
        color: #63717a;
        font-size: 12px;
        margin-bottom: 6px;
    }

    .mailroom-dashboard-card__value {
        color: #1f2a33;
        font-size: 22px;
        font-weight: 600;
    }
</style>
