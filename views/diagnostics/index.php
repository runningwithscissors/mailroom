<div class="box panel">
    <div class="panel-body">
        <form action="<?=$action_url?>" method="post">
            <input type="hidden" name="csrf_token" value="<?=CSRF_TOKEN?>">
            <input type="hidden" name="mailroom_action" value="repair_hook">

            <table class="mainTable">
                <thead>
                    <tr>
                        <th><?=lang('mailroom_diagnostics_check')?></th>
                        <th><?=lang('mailroom_status')?></th>
                        <th><?=lang('mailroom_message')?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($checks as $check): ?>
                        <tr>
                            <td><strong><?=htmlspecialchars((string) ($check['label'] ?? ''), ENT_QUOTES, 'UTF-8')?></strong></td>
                            <td>
                                <span class="mailroom-status mailroom-status--<?=htmlspecialchars((string) ($check['status'] ?? 'fail'), ENT_QUOTES, 'UTF-8')?>">
                                    <?=($check['status'] ?? '') === 'pass' ? lang('mailroom_pass') : lang('mailroom_fail')?>
                                </span>
                            </td>
                            <td><?=htmlspecialchars((string) ($check['description'] ?? ''), ENT_QUOTES, 'UTF-8')?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <fieldset class="form-ctrls">
                <button class="btn action"><?=lang('mailroom_repair_email_hook')?></button>
            </fieldset>
        </form>
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

    .mailroom-status {
        display: inline-block;
        min-width: 56px;
        padding: 2px 8px;
        border-radius: 3px;
        text-align: center;
        font-weight: 700;
    }

    .mailroom-status--pass {
        background: #effaf2;
        color: #1f6b33;
    }

    .mailroom-status--fail {
        background: #fff2f2;
        color: #8f2626;
    }
</style>
