<?php

declare(strict_types=1);

namespace OCA\BrStunden\Service;

use DomainException;
use JsonException;
use OCA\BrStunden\AppInfo\Application;
use OCP\IAppConfig;

class CapabilitySettingsService {
    public const OVERVIEW_READ = 'hours.overview.read';
    public const ENTRY_MANAGE_OWN = 'hours.entry.manage-own';
    public const PAYROLL_EXPORT_OWN = 'hours.payroll.export-own';
    public const PAYROLL_EXPORT_FOREIGN = 'hours.payroll.export-foreign';
    public const REMINDER_DASHBOARD = 'hours.reminders.read';
    public const ENTRY_MANAGE_FOREIGN = 'hours.entry.manage-foreign';

    public const CONFIG_KEYS = [
        self::OVERVIEW_READ => 'permission_roles_overview_read',
        self::ENTRY_MANAGE_OWN => 'permission_roles_entry_manage_own',
        self::PAYROLL_EXPORT_OWN => 'permission_roles_payroll_export_own',
        self::PAYROLL_EXPORT_FOREIGN => 'permission_roles_payroll_export_foreign',
        self::REMINDER_DASHBOARD => 'permission_roles_reminder_dashboard',
        self::ENTRY_MANAGE_FOREIGN => 'permission_roles_entry_manage_foreign',
    ];

    public const DEFAULTS = [
        self::OVERVIEW_READ => [BrGroupsService::ROLE_MEMBER],
        self::ENTRY_MANAGE_OWN => [BrGroupsService::ROLE_MEMBER],
        self::PAYROLL_EXPORT_OWN => [BrGroupsService::ROLE_MEMBER],
        self::PAYROLL_EXPORT_FOREIGN => [BrGroupsService::ROLE_CHAIR, BrGroupsService::ROLE_DEPUTY],
        self::REMINDER_DASHBOARD => [BrGroupsService::ROLE_CHAIR, BrGroupsService::ROLE_DEPUTY],
        self::ENTRY_MANAGE_FOREIGN => [],
    ];

    public function __construct(private IAppConfig $config) {}

    /** @return list<string> */
    public function rolesFor(string $capability): array {
        if (!isset(self::CONFIG_KEYS[$capability])) {
            return [];
        }
        $stored = $this->config->getValueString(
            Application::APP_ID,
            self::CONFIG_KEYS[$capability],
            self::encode(self::DEFAULTS[$capability]),
        );
        return $this->decode($stored);
    }

    /** @return array<string,list<string>> */
    public function current(): array {
        $result = [];
        foreach (array_keys(self::CONFIG_KEYS) as $capability) {
            $result[$capability] = $this->rolesFor($capability);
        }
        return $result;
    }

    /** @param array<string,mixed> $assignments @return array<string,list<string>> */
    public function update(array $assignments): array {
        $validated = [];
        foreach ($assignments as $capability => $roles) {
            if (!isset(self::CONFIG_KEYS[$capability]) || !is_array($roles) || !array_is_list($roles)) {
                throw new DomainException('Die Berechtigungszuordnung enthält einen unbekannten oder ungültigen Eintrag.');
            }
            $validated[$capability] = $this->normalizeRoles($roles);
        }
        foreach ($validated as $capability => $roles) {
            $this->config->setValueString(
                Application::APP_ID,
                self::CONFIG_KEYS[$capability],
                self::encode($roles),
            );
        }
        return $this->current();
    }

    public function isComplete(): bool {
        foreach (self::CONFIG_KEYS as $capability => $configKey) {
            $stored = $this->config->getValueString(Application::APP_ID, $configKey, self::encode(self::DEFAULTS[$capability]));
            if (!$this->isValidStored($stored)) return false;
        }
        return true;
    }

    /** @param list<mixed> $roles @return list<string> */
    private function normalizeRoles(array $roles): array {
        $normalized = [];
        foreach ($roles as $role) {
            if (!is_string($role) || !in_array($role, BrGroupsService::ROLES, true)) {
                throw new DomainException('Berechtigungen dürfen nur den BR-Rollen Mitglied, Vorsitz oder Stellvertretung zugeordnet werden.');
            }
            $normalized[$role] = true;
        }
        $result = array_keys($normalized);
        usort($result, static fn(string $left, string $right): int => array_search($left, BrGroupsService::ROLES, true) <=> array_search($right, BrGroupsService::ROLES, true));
        return $result;
    }

    /** @return list<string> */
    private function decode(string $stored): array {
        try {
            $decoded = json_decode($stored, true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }
        if (!is_array($decoded) || !array_is_list($decoded)) {
            return [];
        }
        try {
            return $this->normalizeRoles($decoded);
        } catch (DomainException) {
            return [];
        }
    }

    private function isValidStored(string $stored): bool {
        try {
            $decoded = json_decode($stored, true, 8, JSON_THROW_ON_ERROR);
            if (!is_array($decoded) || !array_is_list($decoded)) return false;
            $this->normalizeRoles($decoded);
            return true;
        } catch (JsonException|DomainException) {
            return false;
        }
    }

    /** @param list<string> $roles */
    private static function encode(array $roles): string {
        return json_encode(array_values($roles), JSON_THROW_ON_ERROR);
    }
}
