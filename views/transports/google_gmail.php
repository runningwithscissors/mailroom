<div class="box panel">
    <div class="panel-body">
        <form action="<?=$action_url?>" method="post">
            <input type="hidden" name="csrf_token" value="<?=CSRF_TOKEN?>">

            <fieldset>
                <div class="field-instruct">
                    <label for="client_email"><?=lang('mailroom_google_client_email')?></label>
                    <em><?=lang('mailroom_google_client_email_desc')?></em>
                </div>
                <div class="field-control">
                    <input id="client_email" type="email" name="client_email" value="<?=htmlspecialchars((string) ($settings['client_email'] ?? ''), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="private_key"><?=lang('mailroom_google_private_key')?></label>
                    <em><?=lang('mailroom_secret_leave_blank')?></em>
                    <em><?=lang('mailroom_google_private_key_desc')?></em>
                </div>
                <div class="field-control">
                    <textarea id="private_key" name="private_key" rows="8" autocomplete="new-password"></textarea>
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="sender"><?=lang('mailroom_google_sender')?></label>
                    <em><?=lang('mailroom_google_sender_desc')?></em>
                </div>
                <div class="field-control">
                    <input id="sender" type="email" name="sender" value="<?=htmlspecialchars((string) ($settings['sender'] ?? ''), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="from_name"><?=lang('mailroom_default_from_name')?></label>
                </div>
                <div class="field-control">
                    <input id="from_name" type="text" name="from_name" value="<?=htmlspecialchars((string) ($settings['from_name'] ?? ''), ENT_QUOTES, 'UTF-8')?>">
                </div>
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
                <button class="btn action" name="mailroom_action" value="save"><?=lang('save')?></button>
                <button class="button" name="mailroom_action" value="test"><?=lang('mailroom_send_test')?></button>
                <a class="button" href="<?=ee('CP/URL')->make('addons/settings/mailroom/transports')?>"><?=lang('cancel')?></a>
            </fieldset>
        </form>
    </div>
</div>
