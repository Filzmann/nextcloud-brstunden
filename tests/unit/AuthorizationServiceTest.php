<?php

declare(strict_types=1);

namespace OCP {
    interface IGroupManager { public function isInGroup($uid, $gid); }
    interface IUser { public function getUID(); }
    interface IUserSession { public function getUser(); }
}

namespace {
    use OCA\BrStunden\Service\AuthorizationService;
    use OCA\BrStunden\Service\BrGroupsService;
    use OCA\BrStunden\Service\CapabilitySettingsService;
    use OCP\IGroupManager;
    use OCP\IUser;
    use OCP\IUserSession;
    use function OCA\BrStunden\Tests\assertSameValue;

    $user = new class implements IUser { public function getUID(): string { return 'actor'; } };
    $session = new class($user) implements IUserSession {
        public function __construct(public ?IUser $user) {}
        public function getUser(): ?IUser { return $this->user; }
    };
    $groups = new class implements IGroupManager {
        public array $memberships = [
            'actor' => ['BR-Mitglieder' => true, 'BR-Vorsitz' => true],
            'target' => ['BR-Mitglieder' => true],
        ];
        public function isInGroup($uid, $gid): bool { return $this->memberships[(string)$uid][(string)$gid] ?? false; }
    };
    $roleGroups = new class extends BrGroupsService {
        public function __construct() {}
        public function groupIdForRole(string $role): ?string {
            return [self::ROLE_MEMBER => 'BR-Mitglieder', self::ROLE_CHAIR => 'BR-Vorsitz', self::ROLE_DEPUTY => 'BR-Stellvertretung'][$role] ?? null;
        }
    };
    $settings = new class extends CapabilitySettingsService {
        public array $assignments = [
            self::OVERVIEW_READ => [BrGroupsService::ROLE_MEMBER],
            self::ENTRY_MANAGE_OWN => [BrGroupsService::ROLE_MEMBER],
            self::PAYROLL_EXPORT_FOREIGN => [BrGroupsService::ROLE_CHAIR, BrGroupsService::ROLE_DEPUTY],
            self::REMINDER_DASHBOARD => [BrGroupsService::ROLE_CHAIR, BrGroupsService::ROLE_DEPUTY],
            self::ENTRY_MANAGE_FOREIGN => [],
        ];
        public function __construct() {}
        public function rolesFor(string $capability): array { return $this->assignments[$capability] ?? []; }
    };

    $authorization = new AuthorizationService($session, $groups, $roleGroups, $settings);
    assertSameValue(true, $authorization->can(CapabilitySettingsService::OVERVIEW_READ), 'A member assignment must authorize a member.');
    assertSameValue(true, $authorization->can(CapabilitySettingsService::REMINDER_DASHBOARD), 'A chair assignment must authorize a chair.');
    assertSameValue(false, $authorization->can(CapabilitySettingsService::ENTRY_MANAGE_FOREIGN), 'An empty BS5 assignment must deny even a chair.');

    $settings->assignments[CapabilitySettingsService::ENTRY_MANAGE_FOREIGN] = [BrGroupsService::ROLE_CHAIR];
    $authorization->assertCanManageEntryFor('target');

    $before = $groups->memberships;
    $denied = false;
    try {
        $authorization->assertCanManageEntryFor('outsider');
    } catch (\DomainException) {
        $denied = true;
    }
    assertSameValue(true, $denied, 'A manipulated foreign target outside the canonical member group must be rejected.');
    assertSameValue($before, $groups->memberships, 'A rejected authorization check must have no side effects.');

    $groups->memberships['actor'] = [];
    assertSameValue(false, $authorization->can(CapabilitySettingsService::OVERVIEW_READ), 'Removing canonical membership must revoke access immediately.');

    echo "AuthorizationService tests passed\n";
}
