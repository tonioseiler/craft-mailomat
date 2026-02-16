<?php

namespace furbo\craftmailomat;

use Craft;
use craft\base\Plugin;
use craft\elements\User;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\MailerHelper;
use craft\log\MonologTarget;
use craft\services\Utilities;
use furbo\craftmailomat\events\MailomatWebhookEvent;
use furbo\craftmailomat\services\WebhookCampaignService;
use furbo\craftmailomat\utilities\EmailReportUtility;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LogLevel;
use yii\base\Event;
use yii\log\Dispatcher;
use yii\log\Logger;

/**
 * Craft mailomat plugin
 *
 * @method static CraftMailomat getInstance()
 * @author Furbo GmbH <support@furbo.ch>
 * @copyright Furbo GmbH
 * @license https://craftcms.github.io/license/ Craft License
 */
class CraftMailomat extends Plugin
{
    public const EVENT_MAILOMAT_WEBHOOK = 'mailomatWebhook';

    public string $schemaVersion = '1.0.0';

    public static CraftMailomat $plugin;

    public static function config(): array
    {
        return [
            'components' => [
                'webhookCampaignService' => ['class' => WebhookCampaignService::class]
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->registerLogTarget();

        Craft::$app->getUrlManager()->addRules([
            'mailomat/webhook' => 'craft-mailomat/webhook/index',
        ], false);


        $this->attachEventHandlers();

        // Any code that creates an element query or loads Twig should be deferred until
        // after Craft is fully initialized, to avoid conflicts with other plugins/modules
        Craft::$app->onInit(function() {
            // ...
        });
    }

    private function attachEventHandlers(): void
    {
        $eventName = defined(sprintf('%s::EVENT_REGISTER_MAILER_TRANSPORT_TYPES', MailerHelper::class))
            ? MailerHelper::EVENT_REGISTER_MAILER_TRANSPORT_TYPES // Craft 4
            /** @phpstan-ignore-next-line */
            : MailerHelper::EVENT_REGISTER_MAILER_TRANSPORTS; // Craft 5+

        Event::on(
            MailerHelper::class,
            $eventName,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = MailomatAdapter::class;
            }
        );

        // Only register webhook event if Campaign is installed & enabled
        if (Craft::$app->plugins->isPluginEnabled('campaign')) {
            Event::on(
                CraftMailomat::class,
                CraftMailomat::EVENT_MAILOMAT_WEBHOOK,
                    function(MailomatWebhookEvent $event) {
                        if(CraftMailomat::getInstance()->webhookCampaignService->verifyApiKey()){
                            CraftMailomat::getInstance()->webhookCampaignService->callWebhook($event->eventType, $event->email);
                        }
                    }
            );
        }

        // Register utility
        $utilityEventName = defined(sprintf('%s::EVENT_REGISTER_UTILITY_TYPES', Utilities::class))
            ? Utilities::EVENT_REGISTER_UTILITY_TYPES // Craft 4
            /** @phpstan-ignore-next-line */
            : Utilities::EVENT_REGISTER_UTILITIES; // Craft 5+

        Event::on(
            Utilities::class,
            $utilityEventName,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = EmailReportUtility::class;
            }
        );


    }

    /**
     * Registers a custom log target, keeping the format as simple as possible.
     *
     * @see LineFormatter::SIMPLE_FORMAT
     */
    private function registerLogTarget(): void
    {
        if (Craft::getLogger()->dispatcher instanceof Dispatcher) {
            Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
                'name' => 'craft-mailomat',
                'categories' => ['craft-mailomat'],
                'level' => LogLevel::INFO,
                'logContext' => false,
                'allowLineBreaks' => false,
                'formatter' => new LineFormatter(
                    format: "[%datetime%] %message%\n",
                    dateFormat: 'Y-m-d H:i:s',
                ),
            ]);
        }
    }


    /**
     * Logs a message.
     */
    public function log(string $message, array $params = [], int $type = Logger::LEVEL_INFO): void
    {
        /** @var User|null $user */
        $user = Craft::$app->getUser()->getIdentity();

        if ($user !== null) {
            $params['username'] = $user->username;
        }

        $message = Craft::t('craft-mailomat', $message, $params);

        Craft::getLogger()->log($message, $type, 'craft-mailomat');
    }


}
