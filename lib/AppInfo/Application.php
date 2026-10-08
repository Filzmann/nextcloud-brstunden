<?php

declare(strict_types=1);

namespace OCA\BrStunden\AppInfo;

use OCA\BrStunden\Privacy\BrStundenPrivacyProviderListener;
use OCA\BrStunden\Privacy\BrStundenProcessingMetadataProviderListener;
use OCA\BrStunden\Permission\BrStundenPermissionProviderListener;
use OCA\BrStunden\Permission\BrStundenPermissionSourceInterface;
use OCA\BrStunden\Permission\NextcloudBrStundenPermissionSource;
use OCA\FlzDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;
use OCA\FlzDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent;
use OCA\FlzPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;
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
        $context->registerEventListener(RegisterProcessingMetadataProvidersEvent::class, BrStundenProcessingMetadataProviderListener::class);
        $context->registerEventListener(RegisterPermissionProvidersEvent::class, BrStundenPermissionProviderListener::class);
        $context->registerServiceAlias(BrStundenPermissionSourceInterface::class, NextcloudBrStundenPermissionSource::class);
    }

    public function boot(IBootContext $context): void {
    }
}
