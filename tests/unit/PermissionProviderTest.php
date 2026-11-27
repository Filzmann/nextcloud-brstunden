<?php

declare(strict_types=1);

namespace OCP\EventDispatcher {
    class Event {}
    interface IEventListener { public function handle(Event $event): void; }
}

namespace OCA\FlzPermissionMatrix\PublicApi\V1 {
    interface PermissionProvider { public function descriptor(): PermissionProviderDescriptor; public function collect(): PermissionProviderResult; }
    final class PermissionProviderDescriptor { public function __construct(...$arguments) {} }
    final class PermissionCondition {
        private function __construct(public string $operator, public ?string $groupId = null, public array $children = []) {}
        public static function group(string $groupId): self { return new self('group', $groupId); }
        public static function all(array $children): self { return new self('all', null, $children); }
        public static function any(array $children): self { return new self('any', null, $children); }
        public static function self(): self { return new self('self'); }
        public static function authenticated(): self { return new self('authenticated'); }
    }
    final class PermissionRule { public function __construct(public string $type, public string $name, public string $detail, public string $permission, public string $label, public string $effect, public string $scope, public PermissionCondition $condition, public string $source, public string $confidence) {} }
    final class PermissionProviderResult { public function __construct(public array $rules, public bool $complete = true, public array $warnings = []) {} }
    final class RegisterPermissionProvidersEvent extends \OCP\EventDispatcher\Event { public array $providers = []; public function register(PermissionProvider $provider): void { $this->providers[] = $provider; } }
}

namespace {
    use OCA\BrStunden\Permission\BrStundenPermissionProvider;
    use OCA\BrStunden\Permission\BrStundenPermissionProviderListener;
    use OCA\BrStunden\Permission\BrStundenPermissionSourceInterface;
    use OCA\BrStunden\Service\BrGroupsService;
    use OCA\BrStunden\Service\CapabilitySettingsService;
    use OCA\FlzPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;
    use function OCA\BrStunden\Tests\assertSameValue;

    $source = new class implements BrStundenPermissionSourceInterface {
        public function status(): array { return ['complete' => true, 'warning' => null]; }
        public function groupIds(): array { return ['member' => 'br-members', 'chair' => 'br-chair', 'deputy' => 'br-deputy']; }
        public function assignments(): array { return CapabilitySettingsService::DEFAULTS; }
    };
    $provider = new BrStundenPermissionProvider($source);
    $rules = $provider->collect()->rules;
    $byPermission = [];
    foreach ($rules as $rule) $byPermission[$rule->permission] = $rule;

    assertSameValue([
        'hours.overview.read',
        'hours.entry.manage-own',
        'hours.payroll.export-own',
        'hours.payroll.export-foreign',
        'hours.reminders.read',
        'hours.entry.manage-foreign',
    ], array_keys($byPermission), 'All six distinct BR-Stunden capabilities must be inventoried.');
    assertSameValue('br-members', $byPermission['hours.overview.read']->condition->groupId, 'Die Übersicht muss standardmäßig an alle Mitglieder gebunden sein.');
    assertSameValue('all', $byPermission['hours.entry.manage-own']->condition->operator, 'Eigene Einträge benötigen Mitgliedschaft UND Selbstbezug.');
    assertSameValue(['group', 'self'], array_map(static fn($condition): string => $condition->operator, $byPermission['hours.entry.manage-own']->condition->children), 'Der Selbstbezug darf die Mitgliedschaft nicht ersetzen.');
    assertSameValue('all', $byPermission['hours.payroll.export-own']->condition->operator, 'Own payroll export must retain self scope.');
    assertSameValue('any', $byPermission['hours.payroll.export-foreign']->condition->operator, 'Foreign payroll export must accept chair or deputy.');
    assertSameValue(['br-chair', 'br-deputy'], array_map(static fn($condition): ?string => $condition->groupId, $byPermission['hours.payroll.export-foreign']->condition->children), 'Foreign payroll export must use canonical role identities.');
    assertSameValue('deny', $byPermission['hours.entry.manage-foreign']->effect, 'BS5 must be explicitly inactive by default.');
    assertSameValue('authenticated', $byPermission['hours.entry.manage-foreign']->condition->operator, 'Inactive BS5 must not invent a group grant.');
    foreach ($rules as $rule) {
        if ($rule->condition->operator === 'nextcloud-admin') throw new RuntimeException('Der Provider darf keinen im Access-Service nicht vorhandenen Admin-Bypass behaupten.');
    }

    $incompleteSource = new class implements BrStundenPermissionSourceInterface {
        public function status(): array { return ['complete' => false, 'warning' => 'LocalBase unavailable']; }
        public function groupIds(): array { return []; }
        public function assignments(): array { return CapabilitySettingsService::DEFAULTS; }
    };
    $incomplete = (new BrStundenPermissionProvider($incompleteSource))->collect();
    assertSameValue(false, $incomplete->complete, 'An unavailable canonical role source must make permission coverage incomplete.');
    assertSameValue([], $incomplete->rules, 'An unavailable canonical role source must not publish guessed grants.');

    $event = new RegisterPermissionProvidersEvent();
    $listener = new BrStundenPermissionProviderListener($provider);
    if (!$listener instanceof \OCP\EventDispatcher\IEventListener) throw new RuntimeException('Permission-Listener erfüllt den Nextcloud-Eventvertrag nicht.');
    $listener->handle($event);
    assertSameValue($provider, $event->providers[0] ?? null, 'Der Provider muss lazy registriert werden.');

    echo 'BRStunden permission provider tests passed' . PHP_EOL;
}
