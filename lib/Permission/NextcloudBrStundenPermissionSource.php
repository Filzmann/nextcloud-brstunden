<?php

declare(strict_types=1);

namespace OCA\BrStunden\Permission;

use OCA\BrStunden\Service\BrGroupsService;

final class NextcloudBrStundenPermissionSource implements BrStundenPermissionSourceInterface {
    public function __construct(private BrGroupsService $groups) {}

    public function memberGroupId(): string {
        return $this->groups->memberGroupName();
    }
}
