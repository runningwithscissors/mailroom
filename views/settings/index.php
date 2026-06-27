<div class="box panel">
    <div class="panel-body">
        <form action="<?=$action_url?>" method="post">
            <input type="hidden" name="csrf_token" value="<?=CSRF_TOKEN?>">

            <fieldset>
                <div class="field-instruct">
                    <label for="default_transport"><?=lang('mailroom_default_transport')?></label>
                    <em><?=lang('mailroom_default_transport_desc')?></em>
                </div>
                <div class="field-control">
                    <select id="default_transport" name="default_transport">
                        <?php foreach ($transport_choices as $value => $label): ?>
                            <option value="<?=htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8')?>" <?=($settings['default_transport'] ?? '') === (string) $value ? 'selected' : ''?>>
                                <?=htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8')?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="default_from_email"><?=lang('mailroom_default_from_email')?></label>
                </div>
                <div class="field-control">
                    <input id="default_from_email" type="email" name="default_from_email" value="<?=htmlspecialchars((string) ($settings['default_from_email'] ?? ''), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="default_from_name"><?=lang('mailroom_default_from_name')?></label>
                </div>
                <div class="field-control">
                    <input id="default_from_name" type="text" name="default_from_name" value="<?=htmlspecialchars((string) ($settings['default_from_name'] ?? ''), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="default_reply_to"><?=lang('mailroom_default_reply_to')?></label>
                </div>
                <div class="field-control">
                    <input id="default_reply_to" type="email" name="default_reply_to" value="<?=htmlspecialchars((string) ($settings['default_reply_to'] ?? ''), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="logging_mode"><?=lang('mailroom_logging_mode')?></label>
                    <em><?=lang('mailroom_logging_mode_desc')?></em>
                </div>
                <div class="field-control">
                    <select id="logging_mode" name="logging_mode">
                        <?php foreach (['metadata' => 'mailroom_logging_metadata', 'full' => 'mailroom_logging_full', 'privacy' => 'mailroom_logging_privacy'] as $value => $label): ?>
                            <option value="<?=$value?>" <?=($settings['logging_mode'] ?? 'metadata') === $value ? 'selected' : ''?>><?=lang($label)?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </fieldset>

            <fieldset>
                <label class="choice block">
                    <input type="checkbox" name="privacy_mode" value="1" <?=($settings['privacy_mode'] ?? 'n') === 'y' ? 'checked' : ''?>>
                    <?=lang('mailroom_privacy_mode')?>
                </label>
                <label class="choice block">
                    <input type="checkbox" name="recipient_masking" value="1" <?=($settings['recipient_masking'] ?? 'n') === 'y' ? 'checked' : ''?>>
                    <?=lang('mailroom_recipient_masking')?>
                </label>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="log_retention_days"><?=lang('mailroom_log_retention')?></label>
                </div>
                <div class="field-control">
                    <select id="log_retention_days" name="log_retention_days">
                        <?php foreach (['7', '14', '30', '60', '90', '180', '365', '0'] as $days): ?>
                            <option value="<?=$days?>" <?=($settings['log_retention_days'] ?? '90') === $days ? 'selected' : ''?>>
                                <?=$days === '0' ? lang('mailroom_keep_forever') : sprintf(lang('mailroom_days'), $days)?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </fieldset>

            <fieldset>
                <label class="choice block">
                    <input type="checkbox" name="auto_retry" value="1" <?=($settings['auto_retry'] ?? 'y') === 'y' ? 'checked' : ''?>>
                    <?=lang('mailroom_auto_retry')?>
                </label>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="max_retry_attempts"><?=lang('mailroom_max_retry_attempts')?></label>
                </div>
                <div class="field-control">
                    <input id="max_retry_attempts" type="number" min="0" max="10" name="max_retry_attempts" value="<?=htmlspecialchars((string) ($settings['max_retry_attempts'] ?? '3'), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="retry_schedule"><?=lang('mailroom_retry_schedule')?></label>
                    <em><?=lang('mailroom_retry_schedule_desc')?></em>
                </div>
                <div class="field-control">
                    <input id="retry_schedule" type="text" name="retry_schedule" value="<?=htmlspecialchars((string) ($settings['retry_schedule'] ?? '5,30,120'), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <label class="choice block">
                    <input type="checkbox" name="alert_on_failure" value="1" <?=($settings['alert_on_failure'] ?? 'n') === 'y' ? 'checked' : ''?>>
                    <?=lang('mailroom_alert_on_failure')?>
                </label>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="alert_email"><?=lang('mailroom_alert_email')?></label>
                </div>
                <div class="field-control">
                    <input id="alert_email" type="email" name="alert_email" value="<?=htmlspecialchars((string) ($settings['alert_email'] ?? ''), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="alert_webhook_url"><?=lang('mailroom_alert_webhook_url')?></label>
                </div>
                <div class="field-control">
                    <input id="alert_webhook_url" type="url" name="alert_webhook_url" value="<?=htmlspecialchars((string) ($settings['alert_webhook_url'] ?? ''), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="dev_mode"><?=lang('mailroom_dev_mode')?></label>
                </div>
                <div class="field-control">
                    <select id="dev_mode" name="dev_mode">
                        <?php foreach (['normal' => 'mailroom_dev_mode_normal', 'capture' => 'mailroom_dev_mode_capture', 'redirect' => 'mailroom_dev_mode_redirect', 'suppress' => 'mailroom_dev_mode_suppress'] as $value => $label): ?>
                            <option value="<?=$value?>" <?=($settings['dev_mode'] ?? 'normal') === $value ? 'selected' : ''?>><?=lang($label)?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="redirect_email"><?=lang('mailroom_redirect_email')?></label>
                </div>
                <div class="field-control">
                    <input id="redirect_email" type="email" name="redirect_email" value="<?=htmlspecialchars((string) ($settings['redirect_email'] ?? ''), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="intercept_core_email"><?=lang('mailroom_intercept_core_email')?></label>
                    <em><?=lang('mailroom_intercept_core_email_desc')?></em>
                </div>
                <label class="choice block">
                    <input id="intercept_core_email" type="checkbox" name="intercept_core_email" value="1" <?=($settings['intercept_core_email'] ?? 'n') === 'y' ? 'checked' : ''?>>
                    <?=lang('mailroom_enabled')?>
                </label>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="allowlist_domains"><?=lang('mailroom_allowlist_domains')?></label>
                    <em><?=lang('mailroom_allowlist_domains_desc')?></em>
                </div>
                <div class="field-control">
                    <textarea id="allowlist_domains" name="allowlist_domains" rows="4"><?=htmlspecialchars((string) ($settings['allowlist_domains'] ?? ''), ENT_QUOTES, 'UTF-8')?></textarea>
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="webhook_events_enabled"><?=lang('mailroom_webhook_events')?></label>
                    <em><?=lang('mailroom_webhook_events_desc')?></em>
                </div>
                <label class="choice block">
                    <input id="webhook_events_enabled" type="checkbox" name="webhook_events_enabled" value="1" <?=($settings['webhook_events_enabled'] ?? 'n') === 'y' ? 'checked' : ''?>>
                    <?=lang('mailroom_enabled')?>
                </label>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label for="webhook_secret"><?=lang('mailroom_webhook_secret')?></label>
                    <em><?=lang('mailroom_webhook_secret_desc')?></em>
                </div>
                <div class="field-control">
                    <input id="webhook_secret" type="text" name="webhook_secret" value="<?=htmlspecialchars((string) ($settings['webhook_secret'] ?? ''), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset>
                <div class="field-instruct">
                    <label><?=lang('mailroom_webhook_url')?></label>
                    <em><?=lang('mailroom_webhook_url_desc')?></em>
                </div>
                <div class="field-control">
                    <input type="text" readonly value="<?=htmlspecialchars((string) ($webhook_url ?? ''), ENT_QUOTES, 'UTF-8')?>">
                </div>
            </fieldset>

            <fieldset class="form-ctrls">
                <button class="btn action"><?=lang('save')?></button>
            </fieldset>
        </form>
    </div>
</div>
