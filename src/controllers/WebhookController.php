<?php

namespace furbo\craftmailomat\controllers;

use Craft;
use craft\helpers\App;
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
    public function actionIndex(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        // 1. Required headers
        $event = $request->getHeaders()->get('X-MOM-Webhook-Event');
        $timestamp = $request->getHeaders()->get('X-MOM-Webhook-Timestamp');
        $uuid = $request->getHeaders()->get('X-MOM-Webhook-Id');
        $signature = $request->getHeaders()->get('X-MOM-Webhook-Signature');

        if (!$event || !$timestamp || !$uuid || !$signature) {
            Craft::warning('Mailomat webhook: missing headers', __METHOD__);
            return $this->asJson(['error' => 'Missing headers'])->setStatusCode(400);
        }

        // 2. Timestamp validation (anti-replay protection)
        $tolerance = 300; // 5 minutes
        if (abs(time() - (int)$timestamp) > $tolerance) {
            Craft::warning('Mailomat webhook: timestamp outside tolerance', __METHOD__);
            return $this->asJson(['error' => 'Invalid timestamp'])->setStatusCode(400);
        }

        // 3. UUID replay protection
        $cache = Craft::$app->getCache();
        $cacheKey = "mailomat_webhook_$uuid";

        if ($cache->exists($cacheKey)) {
            Craft::warning('Mailomat webhook: replay detected', __METHOD__);
            return $this->asJson(['error' => 'Replay detected'])->setStatusCode(400);
        }

        // Store UUID for the duration of the tolerance window
        $cache->set($cacheKey, true, $tolerance);

        // 4. Signature verification
        if (!str_contains($signature, '=')) {
            Craft::warning('Mailomat webhook: malformed signature header', __METHOD__);
            return $this->asJson(['error' => 'Malformed signature'])->setStatusCode(400);
        }

        [$algo, $hash] = explode('=', $signature, 2);

        if ($algo !== 'sha256') {
            Craft::warning('Mailomat webhook: invalid signature algorithm', __METHOD__);
            return $this->asJson(['error' => 'Invalid signature algorithm'])->setStatusCode(400);
        }

        $secret = App::mailSettings()['transportSettings']['webhookSecret'];

        if (!$secret) {
            Craft::error('Mailomat webhook: missing webhook secret', __METHOD__);
            throw new ForbiddenHttpException('Webhook secret not configured');
        }

        // Build the signed payload: "<uuid>.<event>.<timestamp>"
        $payload = implode('.', [$uuid, $event, $timestamp]);

        // Generate the expected HMAC hash
        $expectedHash = hash_hmac('sha256', $payload, $secret);

        // Constant-time comparison to avoid timing attacks
        if (!hash_equals($expectedHash, $hash)) {
            Craft::warning('Mailomat webhook: signature mismatch', __METHOD__);
            return $this->asJson(['error' => 'Invalid signature'])->setStatusCode(401);
        }

        // 5. Process the event (request is now trusted)
        $body = Json::decodeIfJson($request->getRawBody());

        Craft::info('Mailomat webhook verified', __METHOD__);

        if (is_array($body)) {
            $eventType = $body['eventType'] ?? null;
            $email     = $body['email'] ?? $body['recipient'] ?? null;

            $this->triggerCustomEvent($eventType, $email, $body);
        }

        return $this->asJson(['status' => 'ok'])->setStatusCode(200);
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
