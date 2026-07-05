<?php

declare(strict_types=1);

namespace {
    if (!interface_exists(\OCP\IUser::class)) {
        eval('namespace OCP; interface IUser { public function getUID(); public function getDisplayName(); public function getEMailAddress(); }');
    }
    if (!interface_exists(\OCP\IUserSession::class)) {
        eval('namespace OCP; interface IUserSession { public function getUser(); }');
    }
    if (!interface_exists(\OCP\IGroupManager::class)) {
        eval('namespace OCP; interface IGroupManager { public function isInGroup($uid, $gid); public function get($gid); }');
    }
    if (!interface_exists(\OCP\Accounts\IAccountManager::class)) {
        eval('namespace OCP\Accounts; interface IAccountManager { public const PROPERTY_ADDRESS = "address"; public function getAccount($user); }');
    }

    require_once __DIR__ . '/helpers.php';
    require_once __DIR__ . '/../../lib/Service/BrMemberService.php';

    use OCA\BrStunden\Service\BrMemberService;
    use OCP\Accounts\IAccountManager;
    use OCP\IGroupManager;
    use OCP\IUser;
    use OCP\IUserSession;
    use function OCA\BrStunden\Tests\assertSameValue;

    $alice = new class('alice', 'Alice Betriebsrat', 'alice@example.invalid') implements IUser {
        public function __construct(
            private string $uid,
            private string $displayName,
            private string $email
        ) {
        }

        public function getUID(): string {
            return $this->uid;
        }

        public function getDisplayName(): string {
            return $this->displayName;
        }

        public function getEMailAddress(): string {
            return $this->email;
        }
    };
    $bob = new class('bob', '', '') implements IUser {
        public function __construct(
            private string $uid,
            private string $displayName,
            private string $email
        ) {
        }

        public function getUID(): string {
            return $this->uid;
        }

        public function getDisplayName(): string {
            return $this->displayName;
        }

        public function getEMailAddress(): string {
            return $this->email;
        }
    };

    $group = new class([$bob, $alice]) {
        public function __construct(private array $users) {
        }

        public function getUsers(): array {
            return $this->users;
        }
    };

    $groupManager = new class($group) implements IGroupManager {
        public array $memberships = ['alice' => true];

        public function __construct(private object $group) {
        }

        public function isInGroup($uid, $gid): bool {
            return $gid === BrMemberService::GROUP_NAME && isset($this->memberships[(string)$uid]);
        }

        public function get($gid): ?object {
            return $gid === BrMemberService::GROUP_NAME ? $this->group : null;
        }
    };

    $session = new class($alice) implements IUserSession {
        public function __construct(private ?object $user) {
        }

        public function getUser(): ?object {
            return $this->user;
        }

        public function setUser(?object $user): void {
            $this->user = $user;
        }
    };

    $accountManager = new class implements IAccountManager {
        public bool $fail = false;

        public function getAccount($user): object {
            if ($this->fail) {
                throw new RuntimeException('Account unavailable.');
            }

            return new class {
                public function getProperty(string $property): object {
                    return new class {
                        public function getValue(): string {
                            return ' Teststr. 1, 12345 Berlin ';
                        }
                    };
                }
            };
        }
    };

    $service = new BrMemberService($groupManager, $session, $accountManager);

    assertSameValue('alice', $service->currentUserId(), 'Current user id should come from the user session.');
    assertSameValue('Teststr. 1, 12345 Berlin', $service->currentUserAddress(), 'Current user address should be trimmed.');
    $service->assertCurrentUserIsMember();
    assertSameValue(
        [
            ['uid' => 'alice', 'displayName' => 'Alice Betriebsrat', 'email' => 'alice@example.invalid'],
            ['uid' => 'bob', 'displayName' => 'bob', 'email' => ''],
        ],
        $service->members(),
        'BR members should be sorted by display name and fall back to uid/email defaults.'
    );

    $accountManager->fail = true;
    assertSameValue('', $service->currentUserAddress(), 'Account lookup failures should produce an empty address.');

    $groupManager->memberships = [];
    try {
        $service->assertCurrentUserIsMember();
        throw new RuntimeException('Non-members should be rejected.');
    } catch (DomainException) {
    }

    $session->setUser(null);
    assertSameValue('', $service->currentUserAddress(), 'Anonymous sessions should not expose an address.');
    try {
        $service->currentUserId();
        throw new RuntimeException('Anonymous sessions should be rejected.');
    } catch (DomainException) {
    }

    echo 'BrMemberService tests passed' . PHP_EOL;
}
