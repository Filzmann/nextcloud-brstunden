<?php

declare(strict_types=1);

namespace OCP { interface IGroupManager { public function groupExists($gid); public function createGroup($gid); public function get($gid); } interface IAppConfig { public function getValueString(string $appId, string $key, string $default = ""): string; public function setValueString(string $appId, string $key, string $value): void; } }
namespace {

    use OCA\BrStunden\Service\BrGroupsService;
    use OCA\LocalBase\Organization\BrGroupDefinition;
    use OCA\LocalBase\Organization\BrGroupSettingsService;
    use OCA\LocalBase\Service\GroupProvisioningService;
    use OCP\IAppConfig;
    use OCP\IGroupManager;
    use function OCA\BrStunden\Tests\assertSameValue;

    $newGroupManager = static function (array $membersByGroup): IGroupManager {
        return new class($membersByGroup) implements IGroupManager {
            public array $groups = [];
            public function __construct(array $membersByGroup) {
                foreach ($membersByGroup as $groupId => $uids) $this->groups[$groupId] = $this->group($uids);
            }
            public function groupExists($gid): bool { return isset($this->groups[$gid]); }
            public function get($gid): ?object { return $this->groups[$gid] ?? null; }
            public function createGroup($gid): object { return $this->groups[$gid] = $this->group([]); }
            private function group(array $uids): object {
                $users = array_map(static fn(string $uid): object => new class($uid) {
                    public function __construct(public string $uid) {}
                }, $uids);
                return new class($users) {
                    public function __construct(private array $users) {}
                    public function getUsers(): array { return $this->users; }
                    public function inGroup(object $user): bool {
                        foreach ($this->users as $candidate) if ($candidate->uid === $user->uid) return true;
                        return false;
                    }
                };
            }
        };
    };
    $newConfig = static function (string $initial = ''): IAppConfig {
        return new class($initial) implements IAppConfig {
            public array $values = [];
            public array $writes = [];
            public function __construct(string $initial) { if ($initial !== '') $this->values['br_group_definition'] = $initial; }
            public function getValueString(string $appId, string $key, string $default = ''): string { return $this->values[$key] ?? $default; }
            public function setValueString(string $appId, string $key, string $value): void { $this->values[$key] = $value; $this->writes[] = [$appId, $key, $value]; }
        };
    };

    $groups = $newGroupManager(['Betriebsrat' => [], 'Betriebsrat-Vorsitzende' => []]);
    $config = $newConfig();
    $provisioning = new GroupProvisioningService($groups);
    $contract = new BrGroupSettingsService($config, $provisioning);
    $service = new BrGroupsService($provisioning, $contract);
    assertSameValue(['Betriebsrat-Stellvertreter'], $service->ensureRequiredGroups(), 'Fresh install should create only missing default groups.');
    assertSameValue(BrGroupDefinition::defaults()->groups(), $contract->validatedDefinition()->groups(), 'Fresh install should initialize the shared default contract.');
    assertSameValue([], $service->ensureRequiredGroups(), 'A valid persisted contract should be idempotent.');

    $customJson = json_encode(['version' => 1, 'revision' => 3, 'groups' => ['member' => 'BR Mitglieder', 'chair' => 'BR Vorsitz', 'deputy' => 'BR Vize']], JSON_THROW_ON_ERROR);
    $customGroups = $newGroupManager(['BR Mitglieder' => ['chair'], 'BR Vorsitz' => ['chair'], 'BR Vize' => []]);
    $customConfig = $newConfig($customJson);
    $customProvisioning = new GroupProvisioningService($customGroups);
    $customService = new BrGroupsService($customProvisioning, new BrGroupSettingsService($customConfig, $customProvisioning));
    assertSameValue(['BR Mitglieder', 'BR Vorsitz', 'BR Vize'], $customService->requiredGroups(), 'Runtime group names must come from LocalBase.');
    assertSameValue('BR Mitglieder', $customService->memberGroupName(), 'Member queries must use the semantic member group.');
    assertSameValue([], $customService->ensureRequiredGroups(), 'Persisted group names must never be recreated or renamed by the consumer.');

    $invalidGroups = $newGroupManager(['Betriebsrat' => ['member'], 'Betriebsrat-Vorsitzende' => ['outsider'], 'Betriebsrat-Stellvertreter' => []]);
    $invalidConfig = $newConfig();
    $invalidProvisioning = new GroupProvisioningService($invalidGroups);
    $invalidService = new BrGroupsService($invalidProvisioning, new BrGroupSettingsService($invalidConfig, $invalidProvisioning));
    try {
        $invalidService->ensureRequiredGroups();
        throw new \RuntimeException('Contradictory chair membership should be rejected.');
    } catch (\DomainException) {
    }
    assertSameValue([], $invalidConfig->writes, 'Contradictory group membership must not persist a contract.');

    echo "BrGroupsService tests passed\n";
}
