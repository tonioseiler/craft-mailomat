<?php

namespace furbo\craftmailomat;

use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\mail\transportadapters\BaseTransportAdapter;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Bridge\Mailomat\Transport\MailomatApiTransport;
use Symfony\Component\Mailer\Transport\AbstractTransport;use function Symfony\Component\String\u;

class MailomatAdapter extends BaseTransportAdapter
{

    /**
     * @var string The API key that should be used
     */
    public string $apiKey = '';

    public static function displayName(): string
    {
        return 'Mailomat';
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['parser'] = [
            'class' => EnvAttributeParserBehavior::class,
            'attributes' => [
                'apiKey',
            ],
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'apiKey' => Craft::t('craft-mailomat', 'API Key')
        ];
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        return [
            [['apiKey'], 'required']
        ];
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('craft-mailomat/settings', [
            'adapter' => $this,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function defineTransport(): array|\Symfony\Component\Mailer\Bridge\Mailomat\Transport\MailomatApiTransport
    {
        return new MailomatApiTransport(
            $this->apiKey,
            HttpClient::create(),
        );
    }

    public function getCampaignWebhookUrl(): ?string
    {
        // Campaign must be installed + enabled
        if (!Craft::$app->plugins->isPluginEnabled('campaign')) {
            return null;
        }

        $apiKey = \putyourlightson\campaign\Campaign::$plugin->settings->getApiKey();

        // Devuelve la URL del webhook final
        return Craft::$app->getSites()->getCurrentSite()->getBaseUrl()
            . 'mailomat/webhook?key=' . $apiKey;
    }

}