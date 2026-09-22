# Changelog

All notable changes to Mailroom will be documented in this file.

## Unreleased

### Added

- Added a Generic SMTP configuration source option that can read existing ExpressionEngine email config values at send time.
- Added a read-only Generic SMTP preview of resolved ExpressionEngine email config values without exposing passwords.
- Added a Mailpit / Dev Capture action for saving DDEV Mailpit defaults.

## 1.0.0 - 2026-06-28

### Added

- Added a Mailroom Documentation Control Panel page covering installation, configuration, usage, transports, logging/privacy, provider webhook scaffolding, and troubleshooting.

### Changed

- Removed stale scaffold-era Foundation Status and Next Steps panels from the dashboard.

## 0.4.1 - 2026-06-26

### Added

- Added Google private-key validation so malformed service account keys return a controlled Mailroom error instead of an OpenSSL warning.
- Added Google Gmail API field helper copy so admins can distinguish JSON `client_email`, JSON `private_key`, and the real delegated sender mailbox.

## 0.4.0 - 2026-06-26

### Added

- Added Google Gmail API transport using Google Workspace service account authentication with domain-wide delegation.
- Added Gmail `users.messages.send` delivery support.
- Added Google OAuth token caching.
- Added Google Gmail API Control Panel settings and test-send flow.
- Added Google Gmail API diagnostics and transport registration.

## 0.3.0 - 2026-06-26

### Added

- Added normalized `mailroom_events` table for provider events.
- Added generic shared-secret webhook action.
- Added settings fields for webhook enablement, webhook shared secret, and endpoint display.
- Added diagnostics checks for provider event webhook configuration.

### Notes

- Provider-specific webhook signature verification and event mapping are intentionally deferred.

## 0.2.6 - 2026-06-26

### Changed

- Polished SMTP and Microsoft Graph secret visibility toggles to use a single swapped SVG icon and avoid browser/Control Panel ghosting.

## 0.2.5 - 2026-06-26

### Changed

- Hardened Generic SMTP sender behavior.
- Generic SMTP now uses the configured From Email, or SMTP username when it is an email address, as the sender.
- The original form sender is preserved as Reply-To for strict relays.

## 0.2.4 - 2026-06-26

### Added

- Added inline save/test notices across Control Panel flows.
- Added real Diagnostics screen with hook, database table, default transport, and provider configuration checks.
- Added Diagnostics repair action for the ExpressionEngine email hook.
- Added Email Log bulk actions for deleting selected logs or deleting only stored message bodies.

## 0.2.3 - 2026-06-26

### Changed

- Lowered the ExpressionEngine manifest PHP requirement from `8.2` to `8.1`.

### Fixed

- Fixed stricter manifest requirements that could hide Mailroom on remote ExpressionEngine installs even though the code supports PHP 8.1+.

## 0.2.2 - 2026-06-26

### Added

- Added Email Log display for stored message bodies.
- Added recipient masking at view time.
- Added privacy mode handling for sensitive log fields.

### Changed

- Privacy mode prevents future body, header, and metadata storage.

## 0.2.1 - 2026-06-26

### Added

- Added hook repair behavior for installs where `Mailroom_ext` exists but the `email_send` hook is disabled.
- Added `ExtensionHookService` and wired Mailroom Control Panel/settings saves to automatically register and enable the hook.

## 0.2.0 - 2026-06-26

### Added

- Added Microsoft 365 Graph OAuth transport.
- Added client-credentials OAuth token acquisition without new Composer dependencies.
- Added token caching in `mailroom_tokens`, with automatic refresh when a token is within five minutes of expiry.
- Added Graph `sendMail` delivery using `POST /v1.0/users/{sender}/sendMail`.
- Added Microsoft Graph Control Panel settings for tenant ID, client ID, client secret, sender mailbox, save-to-sent option, and test recipient.
- Added Send Test Email support for Microsoft Graph.
- Added client-secret visibility toggle in the Graph settings view.
- Added `microsoft_graph` to default transport seeding and the transports management screen.

### Changed

- Updated Control Panel test-send flow so explicitly tested transports are not overridden by dev capture or suppress mode.
- Cached Graph tokens are cleared when tenant, client, secret, or sender settings change.
- Forwarded Graph internet headers are filtered to avoid sending reserved ExpressionEngine email headers.

### Notes

- End-to-end Graph delivery requires live Azure app credentials with Microsoft Graph application permission `Mail.Send` and admin consent.

## 0.1.0 - 2026-06-23

### Added

- Built Mailroom as an ExpressionEngine add-on for transactional email routing, logging, transport settings, and diagnostics scaffolding.
- Added ExpressionEngine `email_send` extension integration through `ext.mailroom.php`.
- Added extension lifecycle methods so install/update can register the email hook.
- Added SMTP transport using ExpressionEngine's email library.
- Added Mailpit/dev-capture transport using direct SMTP socket delivery.
- Added real Email Log Control Panel screen.
- Added dashboard recent-send counts, including captured messages.
- Added SMTP password visibility toggle.

### Changed

- Changed SMTP password POST field to `smtp_password`, while keeping the old `password` field as a fallback.
- Fixed default transport consistency by adding `TransportRepository::setDefault()` and syncing transport `is_default` flags when settings are saved.

### Verified

- Verified Mailpit capture works locally.
- Verified generic SMTP reaches Gmail/Office365 and negotiates STARTTLS.
- Ran full PHP syntax checks across the add-on.
