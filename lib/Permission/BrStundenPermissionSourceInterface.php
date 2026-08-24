<?php

declare(strict_types=1);

namespace OCA\BrStunden\Permission;

interface BrStundenPermissionSourceInterface {
    public function memberGroupId(): string;
}
