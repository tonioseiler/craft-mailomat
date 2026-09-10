# Release Notes for Craft Mailomat

## 1.0.9
- Fixed plugin updates failing with `Your requirements could not be resolved`: `symfony/mailomat-mailer` was pinned to the exact version 7.4.0, which Composer refuses to install because of security advisory PKSA-9y9v-rcsm-h82j (CVE-2026-48747). The constraint is now `^7.4.13`.

## 1.0.6
- new feature: show bounced emails in a craft backend utility

## 1.0.5
- protect webhook by a configurable secret

## 1.0.4
- Bugfix with php 8.4

## 1.0.3
- Added Mailomat webhook support for email delivery events.
- Introduced a custom Craft event `EVENT_MAILOMAT_WEBHOOK` triggered on every webhook call.
- Webhook event now exposes `eventType`, `email` and full `payload`.
- Supported Mailomat webhook event types:
    - `accepted`
    - `not_accepted`
    - `delivered`
    - `failure_tmp`
    - `failure_perm`
- Added automatic integration with **Craft Campaign** when installed and enabled.
- Campaign contacts are now updated automatically for:
    - Hard bounces (`failure_perm`)
    - Spam complaints (if provided by Mailomat)
    - Unsubscribes (if provided by Mailomat)
- Improved Email Settings UI to display Campaign webhook information when applicable.
- General stability and integration improvements.

## 1.0.0
- Initial release
