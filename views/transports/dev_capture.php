<div class="box panel">
    <div class="panel-body">
        <form action="<?=$action_url?>" method="post">
            <input type="hidden" name="csrf_token" value="<?=CSRF_TOKEN?>">

            <fieldset>
                <div class="field-instruct">
                    <label for="mode_label"><?=lang('mailroom_capture_label')?></label>
                </div>
                <div class="field-control">
                    <input id="mode_label" type="text" name="mode_label" value="<?=htmlspecialchars((string) ($settings['mode_label'] ?? 'Local Mailpit'), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="mailpit_host"><?=lang('mailroom_mailpit_host')?></label>
                </div>
                <div class="field-control">
                    <input id="mailpit_host" type="text" name="mailpit_host" value="<?=htmlspecialchars((string) ($settings['mailpit_host'] ?? '127.0.0.1'), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="mailpit_port"><?=lang('mailroom_mailpit_port')?></label>
                </div>
                <div class="field-control">
                    <input id="mailpit_port" type="number" min="1" max="65535" name="mailpit_port" value="<?=htmlspecialchars((string) ($settings['mailpit_port'] ?? '1025'), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <label class="choice block">
                    <input type="checkbox" name="mailpit_tls" value="1" <?=($settings['mailpit_tls'] ?? 'n') === 'y' ? 'checked' : ''?>>
                    <?=lang('mailroom_mailpit_tls')?>
                </label>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="mailpit_username"><?=lang('mailroom_mailpit_username')?></label>
                </div>
                <div class="field-control">
                    <input id="mailpit_username" type="text" name="mailpit_username" value="<?=htmlspecialchars((string) ($settings['mailpit_username'] ?? ''), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="mailpit_password"><?=lang('mailroom_mailpit_password')?></label>
                    <em><?=lang('mailroom_secret_leave_blank')?></em>
                </div>
                <div class="field-control">
                    <input id="mailpit_password" type="password" name="mailpit_password" value="" autocomplete="new-password">
                </div>
            </fieldset>

            <fieldset class="form-ctrls">
                <button class="btn action"><?=lang('save')?></button>
                <a class="button" href="<?=ee('CP/URL')->make('addons/settings/mailroom/transports')?>"><?=lang('cancel')?></a>
            </fieldset>
        </form>
    </div>
</div>
