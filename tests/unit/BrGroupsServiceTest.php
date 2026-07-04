<?php

declare(strict_types=1);

namespace {
    if (!interface_exists(\OCP\IGroupManager::class)) {
        eval('namespace OCP; interface IGroupManager { public function groupExists($gid); public function createGroup($gid); }');
    }

    require __DIR__ . '/../../lib/Service/BrGroupsService.php';

    use OCA\BrStunden\Service\BrGroupsService;
    use OCP\IGroupManager;

    function assertBrGroupsSame(array $expected, array $actual, string $message): void {
        if ($expected !== $actual) {
            throw new RuntimeException($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
        }
    }

    $groupManager = new class(['Betriebsrat']) implements IGroupManager {
        public array $groups = [];

        public function __construct(array $groups) {
            foreach ($groups as $group) {
                $this->groups[$group] = true;
            }
        }

        public function groupExists($gid): bool {
            return isset($this->groups[$gid]);
        }

        public function createGroup($gid): object {
            $this->groups[$gid] = true;

            return new stdClass();
        }
    };

    $service = new BrGroupsService($groupManager);

    assertBrGroupsSame(
        ['Betriebsrat-Vorsitzende', 'Betriebsrat-Stellvertreter'],
        $service->ensureRequiredGroups(),
        'Only missing BR groups should be created.'
    );
    assertBrGroupsSame(
        [],
        $service->ensureRequiredGroups(),
        'BR group creation should be idempotent.'
    );

    echo "BrGroupsService tests passed\n";
}
