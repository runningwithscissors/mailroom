# Mailroom Work Log

Update this file after completed work sessions so a restarted assistant or developer can quickly recover project state.

## 2026-06-23

- Built Mailroom as an ExpressionEngine add-on for transactional email routing, logging, transport settings, and diagnostics scaffolding.
- Initialized a local git repository on `main`.
- Created initial commit `cd47648 Initial Mailroom add-on`.
- Configured remote `git@github.com:runningwithscissors/mailroom.git`.
- Push was blocked by GitHub SSH auth: `Permission denied (publickey)`.
- Added EE `email_send` extension integration through `ext.mailroom.php`.
- Added extension lifecycle methods so install/update can register the hook.
- Confirmed local DB needed hook repair during development; current code now supports registration/deduping.
- Added SMTP transport using EE's email library.
- Added Mailpit/dev-capture transport using direct SMTP socket delivery because EE's email library failed against Mailpit despite socket connectivity.
- Verified Mailpit capture works locally; latest controlled test produced `status=captured`, `transport=mailpit`.
- Verified generic SMTP reaches Gmail/Office365 and negotiates STARTTLS; remaining failures were provider authentication/policy errors.
- Added SMTP password eye toggle in the CP settings view.
- Changed SMTP password POST field to `smtp_password`, keeping old `password` field as fallback.
- Fixed default transport consistency by adding `TransportRepository::setDefault()` and syncing transport `is_default` flags when settings are saved.
- Added real Email Log CP screen at `views/logs/index.php`.
- Updated dashboard recent-send count to include `captured` messages.
- Ran full PHP syntax checks across the add-on; all passed.

## 2026-06-26

- Added Microsoft 365 Graph OAuth transport as Mailroom version `0.2.0`.
- Implemented client-credentials OAuth token acquisition without new Composer dependencies.
- Added token caching in `mailroom_tokens`, with automatic refresh when a token is within five minutes of expiry.
- Added Graph `sendMail` transport using `POST /v1.0/users/{sender}/sendMail`.
- Added Graph CP settings screen with tenant ID, client ID, blank-on-save client secret, sender mailbox, save-to-sent option, and Send Test Email button.
- Added client-secret eye toggle in the Graph settings view.
- Added `microsoft_graph` to default transport seeding and the transports management screen.
- Updated CP test send flow so an explicitly tested transport is not overridden by dev capture/suppress mode.
- Cleared cached Graph tokens when tenant/client/secret/sender settings change.
- Filtered forwarded Graph internet headers to avoid sending reserved EE email headers.
- Installed Mailroom locally in DDEV; EE now reports Mailroom `0.2.0` installed and DB tables/transports exist.
- Ran full PHP syntax checks across the add-on; all passed.
- Graph delivery still needs live Azure app credentials with Graph application permission `Mail.Send` and admin consent to complete an end-to-end send test.
- Added `0.2.1` hook repair behavior after staging showed `Mailroom_ext` installed but disabled in `exp_extensions`.
- Added `ExtensionHookService` and wired Mailroom CP/settings saves to automatically register and enable the EE `email_send` hook.
- Added `0.2.2` log privacy behavior: Email Log now displays stored bodies, recipient masking is applied at view time, and privacy mode hides sensitive log fields and prevents future body/header/metadata storage.

## Current Push Commands

```bash
cd /Users/jtoney/ddev/ee-pb/system/user/addons/mailroom
ssh -T git@github.com
git push -u origin main
```

If SSH auth remains broken:

```bash
cd /Users/jtoney/ddev/ee-pb/system/user/addons/mailroom
git remote set-url origin https://github.com/runningwithscissors/mailroom.git
gh auth login -h github.com
git push -u origin main
```
