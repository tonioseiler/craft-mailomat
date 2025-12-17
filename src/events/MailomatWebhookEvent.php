<?php

namespace furbo\craftmailomat\events;

use yii\base\Event;

class MailomatWebhookEvent extends Event
{
    public string $eventType;
    public ?string $email = null;
    public ?array $payload = [];
}