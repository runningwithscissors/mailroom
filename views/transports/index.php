<div class="box panel">
    <div class="panel-body">
        <form action="<?=$action_url?>" method="post">
            <input type="hidden" name="csrf_token" value="<?=CSRF_TOKEN?>">

            <table class="mainTable">
                <thead>
                    <tr>
                        <th><?=lang('mailroom_transport')?></th>
                        <th><?=lang('mailroom_provider')?></th>
                        <th><?=lang('mailroom_enabled')?></th>
                        <th><?=lang('mailroom_default')?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transports as $transport): ?>
                        <?php $handle = (string) $transport['handle']; ?>
                        <tr>
                            <td>
                                <strong><?=htmlspecialchars((string) $transport['name'], ENT_QUOTES, 'UTF-8')?></strong>
                                <div class="meta-info"><?=htmlspecialchars($handle, ENT_QUOTES, 'UTF-8')?></div>
                            </td>
                            <td><?=htmlspecialchars((string) $transport['provider'], ENT_QUOTES, 'UTF-8')?></td>
                            <td>
                                <label class="choice">
                                    <input
                                        type="checkbox"
                                        name="enabled[]"
                                        value="<?=htmlspecialchars($handle, ENT_QUOTES, 'UTF-8')?>"
                                        <?=($transport['enabled'] ?? 'n') === 'y' ? 'checked' : ''?>
                                    >
                                    <?=lang('mailroom_enabled')?>
                                </label>
                            </td>
                            <td>
                                <label class="choice">
                                    <input
                                        type="radio"
                                        name="default_transport"
                                        value="<?=htmlspecialchars($handle, ENT_QUOTES, 'UTF-8')?>"
                                        <?=$default_transport === $handle || ($transport['is_default'] ?? 'n') === 'y' ? 'checked' : ''?>
                                    >
                                    <?=lang('mailroom_default')?>
                                </label>
                            </td>
                            <td>
                                <?php if ($handle === 'smtp'): ?>
                                    <a class="button button--small" href="<?=ee('CP/URL')->make('addons/settings/mailroom/smtp')?>"><?=lang('mailroom_manage')?></a>
                                <?php elseif ($handle === 'mailpit'): ?>
                                    <a class="button button--small" href="<?=ee('CP/URL')->make('addons/settings/mailroom/dev_capture')?>"><?=lang('mailroom_manage')?></a>
                                <?php elseif ($handle === 'microsoft_graph'): ?>
                                    <a class="button button--small" href="<?=ee('CP/URL')->make('addons/settings/mailroom/microsoft_graph')?>"><?=lang('mailroom_manage')?></a>
                                <?php elseif ($handle === 'google_gmail'): ?>
                                    <a class="button button--small" href="<?=ee('CP/URL')->make('addons/settings/mailroom/google_gmail')?>"><?=lang('mailroom_manage')?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <fieldset class="form-ctrls">
                <button class="btn action"><?=lang('save')?></button>
            </fieldset>
        </form>
    </div>
</div>
