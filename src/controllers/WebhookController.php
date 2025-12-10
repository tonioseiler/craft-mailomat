<?php

namespace furbo\craftmailomat\controllers;

use Craft;
use craft\helpers\Json;
use craft\web\Controller;
use putyourlightson\campaign\Campaign;
use yii\log\Logger;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;

/**
 * Webhook controller
 */
class WebhookController extends \putyourlightson\campaign\controllers\WebhookController
{
    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false;

    protected int|bool|array $allowAnonymous = [
        'index',
    ];

    public function beforeAction($action): bool
    {
        // Verify API key
        $key = $this->request->getParam('key');
        $apiKey = Campaign::$plugin->settings->getApiKey();

        if ($key === null || empty($apiKey) || $key != $apiKey) {
            throw new ForbiddenHttpException('Unauthorised access.');
        }

        return parent::beforeAction($action);
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    public function actionIndex(): ?Response
    {
        $this->requirePostRequest();

        $body = Json::decodeIfJson($this->request->getRawBody());

        Campaign::$plugin->log('Received Mailomat Webhook request: ' . $this->request->getRawBody(), [], Logger::LEVEL_WARNING);

        if(is_array($body)) {
            $eventType = $body['eventType'] ?? null;
            $email = $body['email'] ?? $body['recipient'] ?? null;

            if($eventType === 'complained' || $eventType === 'spam') {
                return $this->callWebhook('complained', $email);
            }
            if($eventType === 'bounced' || $eventType === 'failure_perm') {
                return $this->callWebhook('bounced', $email);
            }
            if($eventType === 'unsubscribed') {
                return $this->callWebhook('unsubscribed', $email);
            }

            return $this->asRawSuccess();
        }

        return $this->asRawFailure('No event provided.');

    }

    /**
     * Returns a raw response success.
     */
    private function asRawSuccess(string $message = ''): Response
    {
        return $this->asRaw(Craft::t('campaign', $message));
    }

    /**
     * Returns a raw response failure.
     */
    private function asRawFailure(string $message = ''): Response
    {
        Campaign::$plugin->log($message, [], Logger::LEVEL_WARNING);

        return $this->asRaw(Craft::t('campaign', $message))
            ->setStatusCode(400);
    }

    /**
     * Calls a webhook.
     */
    private function callWebhook(string $event, string $email = null): Response
    {
        Campaign::$plugin->log('Webhook request: ' . $this->request->getRawBody(), [], Logger::LEVEL_WARNING);

        if ($email === null) {
            return $this->asRawFailure('No email provided.');
        }

        $contact = Campaign::$plugin->contacts->getContactByEmail($email);

        if ($contact === null) {
            return $this->asRawSuccess();
        }

        if ($event === 'complained') {
            Campaign::$plugin->webhook->complain($contact);
        } elseif ($event === 'bounced') {
            Campaign::$plugin->webhook->bounce($contact);
        } elseif ($event === 'unsubscribed') {
            Campaign::$plugin->webhook->unsubscribe($contact);
        }

        return $this->asRawSuccess();
    }

}
