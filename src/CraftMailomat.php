<?php

namespace furbo\craftmailomat;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\MailerHelper;
use yii\base\Event;

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
    public string $schemaVersion = '1.0.0';

    public static function config(): array
    {
        return [
            'components' => [
                // Define component configs here...
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        // Only register webhook routes if Campaign is installed & enabled
        if (Craft::$app->plugins->isPluginEnabled('campaign')) {
            Craft::$app->getUrlManager()->addRules([
                'mailomat/webhook' => 'craft-mailomat/webhook/index',
            ], false);
        }

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
    }
}
