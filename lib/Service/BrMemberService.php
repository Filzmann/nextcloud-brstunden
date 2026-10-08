<?php

declare(strict_types=1);

namespace OCA\BrStunden\Service;

use OCP\Accounts\IAccountManager;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;

class BrMemberService {
    public function __construct(
        private IGroupManager $groupManager,
        private IUserSession $userSession,
        private IAccountManager $accountManager,
        private BrGroupsService $groups,
    ) {
    }

    public function currentUserId(): string {
        $user = $this->userSession->getUser();
        if (!$user instanceof IUser) {
            throw new \DomainException('Bitte anmelden.');
        }

        return $user->getUID();
    }

    public function currentUserAddress(): string {
        $user = $this->userSession->getUser();
        if (!$user instanceof IUser) {
            return '';
        }

        try {
            return trim($this->accountManager->getAccount($user)->getProperty(IAccountManager::PROPERTY_ADDRESS)->getValue());
        } catch (\Throwable) {
            return '';
        }
    }

    public function assertCurrentUserIsMember(): void {
        $uid = $this->currentUserId();
        if (!$this->groupManager->isInGroup($uid, $this->groups->memberGroupName())) {
            throw new \DomainException('Nur Mitglieder der konfigurierten Betriebsratsgruppe dürfen BR-Stunden verwalten.');
        }
    }

    public function memberGroupName(): string {
        return $this->groups->memberGroupName();
    }

    public function members(): array {
        $group = $this->groupManager->get($this->groups->memberGroupName());
        if ($group === null) {
            return [];
        }

        $members = array_map(
            static fn(IUser $user): array => [
                'uid' => $user->getUID(),
                'displayName' => $user->getDisplayName() ?: $user->getUID(),
                'email' => $user->getEMailAddress() ?: '',
            ],
            $group->getUsers()
        );

        usort($members, static fn(array $a, array $b): int => strcasecmp($a['displayName'], $b['displayName']));

        return $members;
    }
}
