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
        public static function self(): self { return new self('self'); }
    }
    final class PermissionRule { public function __construct(public string $type, public string $name, public string $detail, public string $permission, public string $label, public string $effect, public string $scope, public PermissionCondition $condition, public string $source, public string $confidence) {} }
    final class PermissionProviderResult { public function __construct(public array $rules, public bool $complete = true, public array $warnings = []) {} }
    final class RegisterPermissionProvidersEvent extends \OCP\EventDispatcher\Event { public array $providers = []; public function register(PermissionProvider $provider): void { $this->providers[] = $provider; } }
}

namespace {
    use OCA\BrStunden\Permission\BrStundenPermissionProvider;
    use OCA\BrStunden\Permission\BrStundenPermissionProviderListener;
    use OCA\BrStunden\Permission\BrStundenPermissionSourceInterface;
    use OCA\FlzPermissionMatrix\PublicApi\V1\RegisterPermissionProvidersEvent;
    use function OCA\BrStunden\Tests\assertSameValue;

    $source = new class implements BrStundenPermissionSourceInterface {
        public function memberGroupId(): string { return 'br-members'; }
    };
    $provider = new BrStundenPermissionProvider($source);
    $rules = $provider->collect()->rules;
    $byPermission = [];
    foreach ($rules as $rule) $byPermission[$rule->permission] = $rule;

    assertSameValue('br-members', $byPermission['hours.overview.read']->condition->groupId, 'Die Übersicht muss an die konfigurierte Mitgliedergruppe gebunden sein.');
    assertSameValue('all', $byPermission['hours.entry.manage-own']->condition->operator, 'Eigene Einträge benötigen Mitgliedschaft UND Selbstbezug.');
    assertSameValue(['group', 'self'], array_map(static fn($condition): string => $condition->operator, $byPermission['hours.entry.manage-own']->condition->children), 'Der Selbstbezug darf die Mitgliedschaft nicht ersetzen.');
    assertSameValue('br-members', $byPermission['hours.payroll.export']->condition->groupId, 'Der Abrechnungsexport muss denselben Mitgliedervertrag erzwingen.');
    foreach ($rules as $rule) {
        if ($rule->condition->operator === 'nextcloud-admin') throw new RuntimeException('Der Provider darf keinen im Access-Service nicht vorhandenen Admin-Bypass behaupten.');
    }

    $event = new RegisterPermissionProvidersEvent();
    $listener = new BrStundenPermissionProviderListener($provider);
    if (!$listener instanceof \OCP\EventDispatcher\IEventListener) throw new RuntimeException('Permission-Listener erfüllt den Nextcloud-Eventvertrag nicht.');
    $listener->handle($event);
    assertSameValue($provider, $event->providers[0] ?? null, 'Der Provider muss lazy registriert werden.');

    echo 'BRStunden permission provider tests passed' . PHP_EOL;
}
