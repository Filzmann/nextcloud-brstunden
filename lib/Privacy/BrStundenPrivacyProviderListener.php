<?php

declare(strict_types=1);

namespace OCA\BrStunden\Privacy;

use OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

final class BrStundenPrivacyProviderListener implements IEventListener {
    public function __construct(private BrStundenPersonalDataProvider $provider) {
    }

    public function handle(Event $event): void {
        if ($event instanceof RegisterPersonalDataProvidersEvent) $event->register($this->provider);
    }
}
