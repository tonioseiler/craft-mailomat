<p align="center"><img src="./src/icon.svg" width="100" height="100" alt="Mailomat for Craft CMS icon"></p>

<h1 align="center">Mailomat for Craft CMS</h1>

This plugin provides a [Mailomat](https://mailomat.swiss/) integration for [Craft CMS](https://craftcms.com/).

---

## Requirements

This plugin requires **Craft CMS 4.0.0+ or 5.0.0+**.

---

## Installation

You can install this plugin from the Plugin Store or with Composer.

### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for **“Mailomat”**.  
Then click on the **“Install”** button.

### With Composer

```bash
# go to the project directory
cd /path/to/my-project

# tell Composer to load the plugin
composer require furbo/craft-mailomat

# tell Craft to install the plugin
./craft install/plugin craft-mailomat
```

---

## Setup

Once Mailomat is installed, go to:

**Settings → Email → Transport Type → Mailomat**

Enter your **API Key** (available in your Mailomat domain settings) and click **Save**.

> **Tip:** The API Key can be set via environment variables.  
> See [Environmental Configuration](https://docs.craftcms.com/config/environments.html).

---

## Bounced emails

This plugin adds a utility to list the bounced emails. 

It can export the list in csv. 

You can find the utility in Utilities -> Email Report

---

## Webhooks

This plugin exposes a webhook endpoint that can be configured in Mailomat to receive email delivery events.

### Webhook Endpoint

```
https://your-domain.test/mailomat/webhook
```

---

## Webhook Events

When Mailomat sends a webhook request, the plugin triggers a **custom Craft event** that you can listen to in your own plugin or module.

### Available Event Types

Mailomat currently sends the following event types:

| Event Type      | Description |
|-----------------|-------------|
| `accepted`      | Email was accepted by Mailomat |
| `not_accepted` | Email was rejected before delivery |
| `delivered`     | Email was successfully delivered |
| `failure_tmp`   | Temporary delivery failure (soft bounce) |
| `failure_perm`  | Permanent delivery failure (hard bounce) |

---

## Listening to the Webhook Event

The plugin triggers the event:

```php
CraftMailomat::EVENT_MAILOMAT_WEBHOOK
```

You can listen to it like this:

```php
use furbo\craftmailomat\CraftMailomat;
use furbo\craftmailomat\events\MailomatWebhookEvent;
use yii\base\Event;

Event::on(
    CraftMailomat::class,
    CraftMailomat::EVENT_MAILOMAT_WEBHOOK,
    function (MailomatWebhookEvent $event) {
        $eventType = $event->eventType;
        $email = $event->email;
        $payload = $event->payload;

        // Your custom logic here
    }
);
```

### Event Properties

The `$event` object provides:

- `$event->eventType` — The Mailomat event type
- `$event->email` — Recipient email address
- `$event->payload` — Full webhook payload sent by Mailomat

---

## Craft Campaign Integration

If **Craft Campaign** is installed and enabled, the plugin integrates automatically.

### How to Enable

1. Install **Craft Campaign**
2. Go to **Campaign → Settings → Email Settings**
3. Select **Mailomat** as the transport type

### Supported Mappings

| Mailomat Event | Campaign Action |
|----------------|-----------------|
| `failure_perm` | Mark contact as **bounced** |
| spam complaint | Mark contact as **complained** (if provided by Mailomat) |
| unsubscribe    | Mark contact as **unsubscribed** (if provided by Mailomat) |

> Note: Spam complaints and unsubscribe events depend on Mailomat webhook support.

---

## Collaboration

This plugin is developed independently.  
If you are part of the Mailomat team or interested in collaboration, feel free to reach out.

---

## License

This plugin is released under the Craft CMS License.
