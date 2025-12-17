<?php

namespace furbo\craftmailomat\controllers;

use Craft;
use craft\helpers\Json;
use craft\web\Controller;
use furbo\craftmailomat\CraftMailomat;
use furbo\craftmailomat\events\MailomatWebhookEvent;
use yii\log\Logger;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;

/**
 * Webhook controller
 */
class WebhookController extends Controller
{
    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false;

    protected int|bool|array $allowAnonymous = [
        'index',
    ];

    /**
     * @throws MethodNotAllowedHttpException
     */
    public function actionIndex()
    {
        $this->requirePostRequest();

        $body = Json::decodeIfJson($this->request->getRawBody());


        if(is_array($body)) {
            $eventType = $body['eventType'] ?? null;
            $email = $body['email'] ?? $body['recipient'] ?? null;

            $this->triggerCustomEvent($eventType, $email, $body);

        }

    }


    protected function triggerCustomEvent(string $evenType, string $email = null, ?array $payload = []) :void
    {
        $event = new MailomatWebhookEvent([
            'eventType' => $evenType,
            'email' => $email,
            'payload' => $payload,
        ]);
        CraftMailomat::$plugin->trigger(CraftMailomat::EVENT_MAILOMAT_WEBHOOK, $event);
    }

}
