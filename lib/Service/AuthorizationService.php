<?php

declare(strict_types=1);

namespace OCA\BrStunden\Service;

use DomainException;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;

class AuthorizationService {
    public function __construct(
        private IUserSession $userSession,
        private IGroupManager $groupManager,
        private BrGroupsService $groups,
        private CapabilitySettingsService $settings,
    ) {}
    public function currentUserId(): string {
        $user = $this->userSession->getUser();
        if (!$user instanceof IUser) throw new DomainException('Bitte anmelden.');
        return $user->getUID();
    }
    public function can(string $capability): bool {
        $uid = $this->currentUserId();
        foreach ($this->settings->rolesFor($capability) as $role) {
            $groupId = $this->groups->groupIdForRole($role);
            if ($groupId !== null && $this->groupManager->isInGroup($uid, $groupId)) return true;
        }
        return false;
    }
    public function assertCan(string $capability): void {
        if (!$this->can($capability)) throw new DomainException('Für diese BR-Stunden-Funktion fehlt die erforderliche Berechtigung.');
    }
    public function isMember(string $uid): bool {
        $memberGroup = $this->groups->groupIdForRole(BrGroupsService::ROLE_MEMBER);
        return $memberGroup !== null && $this->groupManager->isInGroup($uid, $memberGroup);
    }
    public function assertCanManageEntryFor(string $targetUid): void {
        $actorUid = $this->currentUserId();
        $this->assertTargetIsMember($targetUid);
        $this->assertCan($actorUid === $targetUid ? CapabilitySettingsService::ENTRY_MANAGE_OWN : CapabilitySettingsService::ENTRY_MANAGE_FOREIGN);
    }
    public function assertCanExportPayrollFor(string $targetUid): void {
        $actorUid = $this->currentUserId();
        $this->assertTargetIsMember($targetUid);
        $this->assertCan($actorUid === $targetUid ? CapabilitySettingsService::PAYROLL_EXPORT_OWN : CapabilitySettingsService::PAYROLL_EXPORT_FOREIGN);
    }
    /** @return array<string,bool> */
    public function currentPermissions(): array {
        $result = [];
        foreach (array_keys(CapabilitySettingsService::CONFIG_KEYS) as $capability) $result[$capability] = $this->can($capability);
        return $result;
    }
    private function assertTargetIsMember(string $targetUid): void {
        if ($targetUid === '' || !$this->isMember($targetUid)) throw new DomainException('Der gewählte Eintrag gehört nicht zu einem aktuellen BR-Mitglied.');
    }
}
