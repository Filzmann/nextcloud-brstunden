<?php

declare(strict_types=1);

namespace OCA\BrStunden\Service;

use InvalidArgumentException;
use OCA\FlzPermissionMatrix\PublicApi\V1\BrRoleSnapshotService;
use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Throwable;
use UnexpectedValueException;

class BrGroupsService {
    public const ROLE_MEMBER = 'member';
    public const ROLE_CHAIR = 'chair';
    public const ROLE_DEPUTY = 'deputy';
    public const ROLES = [self::ROLE_MEMBER, self::ROLE_CHAIR, self::ROLE_DEPUTY];
    private const PROVIDER_APP_ID = 'flz_permission_matrix';
    private const SUPPORTED_CONTRACT_VERSION = '1.0';
    private const SUPPORTED_DEFINITION_VERSION = 1;
    /** @var array<string,mixed>|null */
    private ?array $resolved = null;

    public function __construct(private IAppManager $apps, private LoggerInterface $logger, private ?IL10N $l10n = null) {}

    /** Lokalisierung fachlicher Texte; im Test ohne IL10N identity. */
    private function t(string $text): string
    {
        return $this->l10n?->t($text) ?? $text;
    }

    /** @return array{status:string,contract_version:string,definition_version:int,definition_revision:int,checksum:string,groups:array<string,string>,warning:?string} */
    public function snapshot(): array {
        if ($this->resolved !== null) return $this->resolved;
        if (!$this->isProviderEnabled()) {
            return $this->resolved = $this->unavailable('MISSING', $this->t('Der BR-Rollenvertrag (flz_permission_matrix V1) ist nicht verfügbar.'));
        }
        try {
            $payload = $this->readProviderSnapshot();
        } catch (InvalidArgumentException|UnexpectedValueException) {
            return $this->resolved = $this->unavailable('INCOMPATIBLE', $this->t('Der öffentliche BR-Rollenvertrag (flz_permission_matrix V1) ist inkompatibel.'));
        } catch (Throwable $error) {
            $this->logger->warning('BR-Stunden-Rollen-Snapshot ist nicht verfügbar.', [
                'app' => 'brstunden',
                'provider' => self::PROVIDER_APP_ID,
                'exceptionClass' => $error::class,
            ]);
            return $this->resolved = $this->unavailable('UNAVAILABLE', $this->t('Der BR-Rollenvertrag (flz_permission_matrix V1) ist vorübergehend nicht verfügbar.'));
        }
        try {
            foreach (['contractVersion', 'definitionVersion', 'definitionRevision', 'checksum', 'isValid', 'memberGroupId', 'chairGroupId', 'deputyGroupId'] as $method) {
                if (!method_exists($payload, $method)) throw new UnexpectedValueException('Unvollständiger BR-Rollenvertrag.');
            }
            $contractVersion = $payload->contractVersion();
            $definitionVersion = $payload->definitionVersion();
            $definitionRevision = $payload->definitionRevision();
            $checksum = $payload->checksum();
            $valid = $payload->isValid();
            $groups = [
                self::ROLE_MEMBER => $payload->memberGroupId(),
                self::ROLE_CHAIR => $payload->chairGroupId(),
                self::ROLE_DEPUTY => $payload->deputyGroupId(),
            ];
        } catch (Throwable) {
            return $this->resolved = $this->unavailable('INCOMPATIBLE', $this->t('Der öffentliche BR-Rollenvertrag (flz_permission_matrix V1) ist inkompatibel.'));
        }
        if ($contractVersion !== self::SUPPORTED_CONTRACT_VERSION || $definitionVersion !== self::SUPPORTED_DEFINITION_VERSION) {
            return $this->resolved = $this->unavailable('INCOMPATIBLE', $this->t('Der BR-Rollenvertrag (flz_permission_matrix V1) besitzt eine nicht unterstützte Version.'));
        }
        if ($valid !== true || !is_int($definitionRevision) || $definitionRevision < 1 || !is_string($checksum) || strlen($checksum) !== 64) {
            return $this->resolved = $this->unavailable('INVALID', $this->t('Der BR-Rollenvertrag (flz_permission_matrix V1) ist ungültig.'));
        }
        foreach ($groups as $groupId) {
            if (!$this->isValidGroupId($groupId)) {
                return $this->resolved = $this->unavailable('INCOMPATIBLE', $this->t('Der BR-Rollenvertrag (flz_permission_matrix V1) enthält keine vollständigen Rollenidentitäten.'));
            }
        }
        if (count(array_unique($groups)) !== count($groups)) {
            return $this->resolved = $this->unavailable('INVALID', $this->t('Der BR-Rollenvertrag (flz_permission_matrix V1) enthält widersprüchliche Rollenidentitäten.'));
        }
        return $this->resolved = [
            'status' => 'VALID',
            'contract_version' => $contractVersion,
            'definition_version' => $definitionVersion,
            'definition_revision' => $definitionRevision,
            'checksum' => $checksum,
            'groups' => array_map('trim', $groups),
            'warning' => null,
        ];
    }

    public function groupIdForRole(string $role): ?string {
        $snapshot = $this->snapshot();
        return $snapshot['status'] === 'VALID' ? ($snapshot['groups'][$role] ?? null) : null;
    }
    /** @return list<string> */
    public function requiredGroups(): array {
        $snapshot = $this->snapshot();
        return $snapshot['status'] === 'VALID' ? array_values($snapshot['groups']) : [];
    }
    /** @return list<string> */
    public function ensureRequiredGroups(): array { $this->snapshot(); return []; }
    public function memberGroupName(): string {
        $groupId = $this->groupIdForRole(self::ROLE_MEMBER);
        if ($groupId === null) throw new \DomainException('Der kanonische BR-Mitgliedervertrag ist nicht verfügbar.');
        return $groupId;
    }
    protected function isProviderEnabled(): bool { return in_array(self::PROVIDER_APP_ID, $this->apps->getEnabledApps(), true); }
    protected function readProviderSnapshot(): object {
        if (!class_exists(BrRoleSnapshotService::class)) throw new UnexpectedValueException('Der BR-Rollendienst (flz_permission_matrix V1) fehlt.');
        $provider = Server::get(BrRoleSnapshotService::class);
        if (!$provider instanceof BrRoleSnapshotService) throw new UnexpectedValueException('Der BR-Rollendienst (flz_permission_matrix V1) ist nicht auflösbar.');
        return $provider->snapshot();
    }
    private function isValidGroupId(mixed $groupId): bool {
        if (!is_string($groupId)) return false;
        $groupId = trim($groupId);
        $characters = preg_match_all('/./us', $groupId, $matches);
        return $groupId !== '' && $characters !== false && $characters <= 64 && preg_match('/[\x00-\x1F\x7F]/u', $groupId) !== 1;
    }
    /** @return array{status:string,contract_version:string,definition_version:int,definition_revision:int,checksum:string,groups:array<string,string>,warning:string} */
    private function unavailable(string $status, string $warning): array {
        return ['status' => $status, 'contract_version' => '', 'definition_version' => 0, 'definition_revision' => 0, 'checksum' => '', 'groups' => [], 'warning' => $warning];
    }
}
