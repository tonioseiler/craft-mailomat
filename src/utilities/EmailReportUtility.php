<?php

namespace furbo\craftmailomat\utilities;

use Craft;
use craft\base\Utility;

/**
 * Email Report Utility
 */
class EmailReportUtility extends Utility
{
    public static function displayName(): string
    {
        return Craft::t('craft-mailomat', 'Email Report');
    }

    public static function id(): string
    {
        return 'email-report';
    }

    public static function iconPath(): ?string
    {
        return null;
    }

    public static function contentHtml(): string
    {
        return Craft::$app->getView()->renderTemplate(
            'craft-mailomat/_utilities/email-report',
            []
        );
    }
}
