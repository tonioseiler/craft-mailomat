<?php

namespace furbo\craftmailomat\services;

use Craft;
use craft\base\Component;
use furbo\craftmailomat\CraftMailomat;
use putyourlightson\campaign\Campaign;
use yii\log\Logger;

class WebhookCampaignService extends Component
{


    public function verifyApiKey(): bool
    {
        $key = Craft::$app->request->getParam('key');
        $apiKey = Campaign::$plugin->settings->getApiKey();

        if ($key === null || empty($apiKey) || $key != $apiKey) {
            return false;
        }

        return true;
    }

    public function callWebhook(string $event, string $email = null): bool
    {
        CraftMailomat::$plugin->log('Webhook request: ' . Craft::$app->request->getRawBody(), [], Logger::LEVEL_WARNING);

        if ($email === null) {
            return true;
        }

        $contact = Campaign::$plugin->contacts->getContactByEmail($email);

        if ($contact === null) {
            return true;
        }

        match ($event) {
            'complained' => Campaign::$plugin->webhook->complain($contact),
            'failure_tmp', 'failure_perm' => Campaign::$plugin->webhook->bounce($contact),
            'unsubscribed' => Campaign::$plugin->webhook->unsubscribe($contact),
            default => null,
        };

        return true;

    }
}