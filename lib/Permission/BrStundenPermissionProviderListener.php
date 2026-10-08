<?php

declare(strict_types=1);

namespace OCA\BrStunden\Permission;

use OCA\FlzPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;

final class BrStundenPermissionProviderListener {
    public function __construct(private BrStundenPermissionProvider $provider) {}

    public function handle(object $event): void {
        if ($event instanceof RegisterPermissionProvidersEvent) $event->register($this->provider);
    }
}
