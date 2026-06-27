<div class="box panel">
    <div class="panel-body">
        <form action="<?=$action_url?>" method="post">
            <input type="hidden" name="csrf_token" value="<?=CSRF_TOKEN?>">

            <fieldset>
                <div class="field-instruct">
                    <label for="tenant_id"><?=lang('mailroom_graph_tenant_id')?></label>
                </div>
                <div class="field-control">
                    <input id="tenant_id" type="text" name="tenant_id" value="<?=htmlspecialchars((string) ($settings['tenant_id'] ?? ''), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="client_id"><?=lang('mailroom_graph_client_id')?></label>
                </div>
                <div class="field-control">
                    <input id="client_id" type="text" name="client_id" value="<?=htmlspecialchars((string) ($settings['client_id'] ?? ''), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="client_secret"><?=lang('mailroom_graph_client_secret')?></label>
                    <em><?=lang('mailroom_secret_leave_blank')?></em>
                </div>
                <div class="field-control mailroom-password-control">
                    <input id="client_secret" type="password" name="client_secret" value="" autocomplete="new-password">
                    <button
                        type="button"
                        class="mailroom-password-toggle"
                        data-target="client_secret"
                        aria-label="<?=htmlspecialchars(lang('mailroom_show_password'), ENT_QUOTES, 'UTF-8')?>"
                        title="<?=htmlspecialchars(lang('mailroom_show_password'), ENT_QUOTES, 'UTF-8')?>"
                    >
                        <svg class="mailroom-eye" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </button>
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="sender"><?=lang('mailroom_graph_sender')?></label>
                    <em><?=lang('mailroom_graph_sender_desc')?></em>
                </div>
                <div class="field-control">
                    <input id="sender" type="email" name="sender" value="<?=htmlspecialchars((string) ($settings['sender'] ?? ''), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <label class="choice block">
                    <input type="checkbox" name="save_to_sent_items" value="1" <?=($settings['save_to_sent_items'] ?? 'n') === 'y' ? 'checked' : ''?>>
                    <?=lang('mailroom_graph_save_to_sent')?>
                </label>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="test_recipient"><?=lang('mailroom_graph_test_recipient')?></label>
                </div>
                <div class="field-control">
                    <input id="test_recipient" type="email" name="test_recipient" value="<?=htmlspecialchars((string) ($settings['test_recipient'] ?? ''), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset class="form-ctrls">
                <button class="btn action" type="submit" name="mailroom_action" value="save"><?=lang('save')?></button>
                <button class="button" type="submit" name="mailroom_action" value="test"><?=lang('mailroom_send_test')?></button>
                <a class="button" href="<?=ee('CP/URL')->make('addons/settings/mailroom/transports')?>"><?=lang('cancel')?></a>
            </fieldset>
        </form>
    </div>
</div>

<style>
    .mailroom-password-control {
        position: relative;
    }

    .mailroom-password-control input {
        padding-right: 42px;
    }

    .mailroom-password-toggle {
        align-items: center;
        background: transparent;
        border: 0;
        color: #6b7280;
        cursor: pointer;
        display: flex;
        height: 32px;
        justify-content: center;
        padding: 0;
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        width: 32px;
    }

    .mailroom-password-toggle:hover {
        color: #111827;
    }
</style>

<script>
    var mailroomEyeShow = '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2"/>';
    var mailroomEyeHide = '<path d="M3 3l18 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10.6 10.6A3 3 0 0 0 13.4 13.4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a18.4 18.4 0 0 1-2.3 3.2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.6 6.6C3.7 8.5 2 12 2 12s3.5 7 10 7a10.8 10.8 0 0 0 4.2-.8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>';

    document.querySelectorAll('.mailroom-password-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.getAttribute('data-target'));
            var icon = button.querySelector('.mailroom-eye');

            if (! input || ! icon) {
                return;
            }

            var visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            icon.innerHTML = visible ? mailroomEyeShow : mailroomEyeHide;
            button.classList.toggle('is-visible', ! visible);
            button.setAttribute('aria-label', visible ? '<?=addslashes(lang('mailroom_show_password'))?>' : '<?=addslashes(lang('mailroom_hide_password'))?>');
            button.setAttribute('title', visible ? '<?=addslashes(lang('mailroom_show_password'))?>' : '<?=addslashes(lang('mailroom_hide_password'))?>');
        });
    });
</script>
