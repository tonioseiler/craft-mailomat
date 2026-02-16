# Release Notes for Craft Mailomat

## 1.0.6
- new feature: get bounced emails

## 1.0.5
- webhook secret

## 1.0.4
- Bug with php 8.4

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