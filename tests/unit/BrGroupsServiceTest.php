<?php

declare(strict_types=1);

namespace OCP\App { interface IAppManager {} }
namespace OCP {
    final class Server {
        public static mixed $service = null;
        public static function get(string $name): mixed { return self::$service; }
    }
}
namespace Psr\Log {
    interface LoggerInterface {
        public function emergency($message, array $context = []): void;
        public function alert($message, array $context = []): void;
        public function critical($message, array $context = []): void;
        public function error($message, array $context = []): void;
        public function warning($message, array $context = []): void;
        public function notice($message, array $context = []): void;
        public function info($message, array $context = []): void;
        public function debug($message, array $context = []): void;
        public function log($level, $message, array $context = []): void;
    }
}

namespace {
    use OCA\BrStunden\Service\BrGroupsService;
    use OCA\FlzPermissionMatrix\PublicApi\V1\BrRoleSnapshot;
    use OCP\App\IAppManager;
    use Psr\Log\LoggerInterface;
    use function OCA\BrStunden\Tests\assertSameValue;

    final class BrGroupsTestApps implements IAppManager {}
    final class BrGroupsTestLogger implements LoggerInterface {
        public array $warnings = [];
        public function emergency($message, array $context = []): void {}
        public function alert($message, array $context = []): void {}
        public function critical($message, array $context = []): void {}
        public function error($message, array $context = []): void {}
        public function warning($message, array $context = []): void { $this->warnings[] = [$message, $context]; }
        public function notice($message, array $context = []): void {}
        public function info($message, array $context = []): void {}
        public function debug($message, array $context = []): void {}
        public function log($level, $message, array $context = []): void {}
    }
    final class TestBrGroupsService extends BrGroupsService {
        public function __construct(
            private bool $enabled,
            private mixed $providerSnapshot,
            BrGroupsTestLogger $logger,
        ) {
            parent::__construct(new BrGroupsTestApps(), $logger);
        }
        protected function isProviderEnabled(): bool { return $this->enabled; }
        protected function readProviderSnapshot(): object {
            if ($this->providerSnapshot instanceof \Throwable) throw $this->providerSnapshot;
            if ($this->providerSnapshot === null) throw new \UnexpectedValueException('synthetic missing provider');
            return $this->providerSnapshot;
        }
    }

    $logger = new BrGroupsTestLogger();
    $valid = new TestBrGroupsService(
        true,
        new BrRoleSnapshot(true, 1, 5, 'BR-Mitglieder', 'BR-Vorsitz', 'BR-Stellvertretung'),
        $logger,
    );
    assertSameValue('VALID', $valid->snapshot()['status'], 'A compatible flz_permission_matrix V1 snapshot must be accepted.');
    assertSameValue('BR-Mitglieder', $valid->groupIdForRole(BrGroupsService::ROLE_MEMBER), 'Member role must use the public member identity.');
    assertSameValue('BR-Vorsitz', $valid->groupIdForRole(BrGroupsService::ROLE_CHAIR), 'Chair role must use the public chair identity.');
    assertSameValue('BR-Stellvertretung', $valid->groupIdForRole(BrGroupsService::ROLE_DEPUTY), 'Deputy role must use the public deputy identity.');

    $missing = new TestBrGroupsService(false, null, new BrGroupsTestLogger());
    assertSameValue('MISSING', $missing->snapshot()['status'], 'A disabled provider must fail closed.');
    assertSameValue(null, $missing->groupIdForRole(BrGroupsService::ROLE_MEMBER), 'A missing provider must not expose fallback groups.');

    $legacyMemberOnly = new TestBrGroupsService(
        true,
        new BrRoleSnapshot(true, 1, 2, 'BR-Mitglieder'),
        new BrGroupsTestLogger(),
    );
    assertSameValue('INCOMPATIBLE', $legacyMemberOnly->snapshot()['status'], 'The role-aware consumer must reject a member-only snapshot.');

    $failedLogger = new BrGroupsTestLogger();
    $failed = new TestBrGroupsService(true, new \RuntimeException('private detail'), $failedLogger);
    assertSameValue('UNAVAILABLE', $failed->snapshot()['status'], 'Provider failures must produce a stable deny state.');
    assertSameValue(1, count($failedLogger->warnings), 'Unexpected provider failures must be diagnosable.');
    assertSameValue(false, str_contains((string)$failed->snapshot()['warning'], 'private detail'), 'Provider details must not leak to users.');

    echo "BrGroupsService tests passed\n";
}
