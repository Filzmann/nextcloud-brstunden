<?php

declare(strict_types=1);

namespace OCA\BrStunden\AppInfo;

use OCA\BrStunden\Privacy\BrStundenPrivacyProviderListener;
use OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
    public const APP_ID = 'brstunden';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(RegisterPersonalDataProvidersEvent::class, BrStundenPrivacyProviderListener::class);
    }

    public function boot(IBootContext $context): void {
    }
}
