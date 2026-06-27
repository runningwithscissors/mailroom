<div class="box panel">
    <div class="panel-body">
        <form action="<?=$action_url?>" method="post">
            <input type="hidden" name="csrf_token" value="<?=CSRF_TOKEN?>">

            <fieldset>
                <div class="field-instruct">
                    <label for="host"><?=lang('mailroom_smtp_host')?></label>
                </div>
                <div class="field-control">
                    <input id="host" type="text" name="host" value="<?=htmlspecialchars((string) ($settings['host'] ?? ''), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="port"><?=lang('mailroom_smtp_port')?></label>
                </div>
                <div class="field-control">
                    <input id="port" type="number" min="1" max="65535" name="port" value="<?=htmlspecialchars((string) ($settings['port'] ?? '587'), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="encryption"><?=lang('mailroom_smtp_encryption')?></label>
                </div>
                <div class="field-control">
                    <select id="encryption" name="encryption">
                        <?php foreach (['tls' => 'TLS', 'ssl' => 'SSL', 'none' => lang('mailroom_none')] as $value => $label): ?>
                            <option value="<?=$value?>" <?=($settings['encryption'] ?? 'tls') === $value ? 'selected' : ''?>><?=$label?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="username"><?=lang('mailroom_smtp_username')?></label>
                </div>
                <div class="field-control">
                    <input id="username" type="text" name="username" value="<?=htmlspecialchars((string) ($settings['username'] ?? ''), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="smtp_password"><?=lang('mailroom_smtp_password')?></label>
                    <em><?=lang('mailroom_secret_leave_blank')?></em>
                </div>
                <div class="field-control mailroom-password-control">
                    <input id="smtp_password" type="password" name="smtp_password" value="" autocomplete="new-password">
                    <button
                        type="button"
                        class="mailroom-password-toggle"
                        data-target="smtp_password"
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
                    <label for="from_email"><?=lang('mailroom_default_from_email')?></label>
                </div>
                <div class="field-control">
                    <input id="from_email" type="email" name="from_email" value="<?=htmlspecialchars((string) ($settings['from_email'] ?? ''), ENT_QUOTES, 'UTF-8')?>">
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
                    <label for="reply_to"><?=lang('mailroom_default_reply_to')?></label>
                </div>
                <div class="field-control">
                    <input id="reply_to" type="email" name="reply_to" value="<?=htmlspecialchars((string) ($settings['reply_to'] ?? ''), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="timeout"><?=lang('mailroom_timeout')?></label>
                </div>
                <div class="field-control">
                    <input id="timeout" type="number" min="1" max="120" name="timeout" value="<?=htmlspecialchars((string) ($settings['timeout'] ?? '30'), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset class="form-ctrls">
                <button class="btn action"><?=lang('save')?></button>
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
