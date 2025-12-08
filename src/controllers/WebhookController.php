<?php

namespace furbo\craftmailomat\controllers;

use Craft;
use craft\web\Controller;
use putyourlightson\campaign\Campaign;
use yii\web\ForbiddenHttpException;
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
        'mailomat',
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

    public function actionIndex(): ?Response
    {
        dd(1);
        //$this->requirePostRequest();
    }

}
