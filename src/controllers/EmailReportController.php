<?php

namespace furbo\craftmailomat\controllers;

use Craft;
use craft\helpers\MailerHelper;
use craft\web\Controller;
use furbo\craftmailomat\MailomatAdapter;
use yii\web\Response;

/**
 * Email Report Controller
 */
class EmailReportController extends Controller
{
    /**
     * Fetch delivered emails from Mailomat API
     */
    public function actionFetchDelivered(): Response
    {
        return $this->fetchEmailsByEventType('delivered');
    }

    /**
     * Fetch bounced emails from Mailomat API
     */
    public function actionFetchBounced(): Response
    {
        $hoursBack = Craft::$app->getRequest()->getBodyParam('hoursBack', 24);
        return $this->fetchEmailsByEventType('failure_perm', (int)$hoursBack);
    }

    /**
     * Fetch emails by event type from Mailomat API
     */
    private function fetchEmailsByEventType(string $eventType, int $hoursBack = 24): Response
    {
        $this->requirePermission('utility:email-report');

        // Get the API key from mailer settings
        $apiKey = $this->getMailomatApiKey();

        if (!$apiKey) {
            return $this->asJson([
                'success' => false,
                'error' => Craft::t('craft-mailomat', 'Mailomat API key not configured')
            ]);
        }

        // Calculate the date filter (we'll filter client-side since API doesn't support it)
        $afterDate = new \DateTime();
        $afterDate->modify("-{$hoursBack} hours");

        // Make API request to Mailomat - fetch all results with pagination
        try {
            $allEvents = [];
            $page = 1;
            $itemsPerPage = 100;
            $stopFetching = false;

            do {
                $url = 'https://api.mailomat.swiss/events?'
                    . 'itemsPerPage=' . $itemsPerPage
                    . '&page=' . $page
                    . '&order[occurredAt]=desc'
                    . '&eventType[]=' . urlencode($eventType);

                $client = Craft::createGuzzleClient([
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Accept' => 'application/json',
                    ]
                ]);

                $response = $client->get($url);
                $data = json_decode($response->getBody()->getContents(), true);

                // The response is a flat array of events
                $events = is_array($data) ? $data : [];

                if (empty($events)) {
                    break;
                }

                // Filter events by date and stop fetching if we've gone past the timeframe
                foreach ($events as $event) {
                    $eventDate = new \DateTime($event['occurredAt'] ?? '');

                    if ($eventDate >= $afterDate) {
                        $allEvents[] = $event;
                    } else {
                        // Events are ordered by occurredAt desc, so if we hit an old one, stop
                        $stopFetching = true;
                        break;
                    }
                }

                if ($stopFetching) {
                    break;
                }

                $page++;

                // Continue until we get less than itemsPerPage results
            } while (count($events) === $itemsPerPage);

            $formattedEvents = array_map(function($event) {
                return [
                    'id' => $event['id'] ?? '',
                    'email' => $event['recipient'] ?? '',
                    'subject' => $event['payload']['message']['headers']['subject'] ?? '',
                    'occurredAt' => $event['occurredAt'] ?? '',
                    'eventType' => $event['eventType'] ?? '',
                    'statusMessage' => $event['payload']['delivery-status']['message'] ?? '',
                ];
            }, $allEvents);

            return $this->asJson([
                'success' => true,
                'events' => $formattedEvents,
                'totalItems' => count($formattedEvents)
            ]);

        } catch (\Exception $e) {
            Craft::error('Failed to fetch emails from Mailomat: ' . $e->getMessage(), 'craft-mailomat');

            return $this->asJson([
                'success' => false,
                'error' => Craft::t('craft-mailomat', 'Failed to fetch emails: {message}', [
                    'message' => $e->getMessage()
                ])
            ]);
        }
    }
    
    /**
     * Get Mailomat API key from mailer settings
     */
    private function getMailomatApiKey(): ?string
    {
        $mailerConfig = Craft::$app->getProjectConfig()->get('email');
        
        if (!$mailerConfig || !isset($mailerConfig['transportType'])) {
            return null;
        }
        
        // Check if Mailomat adapter is configured
        if ($mailerConfig['transportType'] !== MailomatAdapter::class) {
            return null;
        }
        
        return $mailerConfig['transportSettings']['apiKey'] ?? null;
    }
}
