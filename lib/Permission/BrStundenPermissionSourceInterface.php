<?php

declare(strict_types=1);

namespace OCA\BrStunden\Permission;

interface BrStundenPermissionSourceInterface {
    /** @return array{complete:bool,warning:?string} */
    public function status(): array;
    /** @return array<string,string> */
    public function groupIds(): array;
    /** @return array<string,list<string>> */
    public function assignments(): array;
}
