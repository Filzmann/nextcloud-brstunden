<?php

declare(strict_types=1);

use OCA\BrStunden\Service\BrGroupsService;
use OCA\BrStunden\Service\BrMemberService;

return [
    'providerRegistrations' => [
        'filzmann_data_protection' => [
            OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent::class,
            OCA\FilzmannDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent::class,
        ],
        'filzmann_permission_matrix' => [
            OCA\FilzmannPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent::class,
        ],
    ],
    'uiPath' => '/index.php/apps/brstunden/',
    'preGrantUiStatuses' => [200],
    'postGrantUiStatuses' => [200],
    // BR-Stunden grants membership through native groups, without an admin bypass.
    'grantService' => static function(string $uid): void {
        $groups = OCP\Server::get(BrGroupsService::class);
        $groups->ensureRequiredGroups();
        $group = OCP\Server::get(OCP\IGroupManager::class)->get($groups->memberGroupName());
        $user = OCP\Server::get(OCP\IUserManager::class)->get($uid);
        if ($group === null || $user === null) {
            throw new RuntimeException('Compatibility membership prerequisites are missing.');
        }
        $group->addUser($user);
    },
    'permissionProbe' => static function(string $uid): bool {
        OCP\Server::get(BrGroupsService::class)->ensureRequiredGroups();
        try {
            OCP\Server::get(BrMemberService::class)->assertCurrentUserIsMember();
            return true;
        } catch (DomainException) {
            return false;
        }
    },
    'apiSmokes' => [
        ['/index.php/apps/brstunden/api/state', [200]],
    ],
];
