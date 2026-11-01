<?php

declare(strict_types=1);

namespace OCA\BrStunden\Permission;

use OCA\BrStunden\Service\CapabilitySettingsService;
use OCA\FlzPermissionMatrix\PublicApi\V1\PermissionCondition;
use OCA\FlzPermissionMatrix\PublicApi\V1\PermissionProvider;
use OCA\FlzPermissionMatrix\PublicApi\V1\PermissionProviderDescriptor;
use OCA\FlzPermissionMatrix\PublicApi\V1\PermissionProviderResult;
use OCA\FlzPermissionMatrix\PublicApi\V1\PermissionRule;

final class BrStundenPermissionProvider implements PermissionProvider {
    public function __construct(private BrStundenPermissionSourceInterface $source) {}

    public function descriptor(): PermissionProviderDescriptor {
        return new PermissionProviderDescriptor('brstunden', 'BR-Stunden', '1.0', ['permissions']);
    }

    public function collect(): PermissionProviderResult {
        $status = $this->source->status();
        if (!$status['complete']) {
            return new PermissionProviderResult([], false, [(string)($status['warning'] ?? 'Der BR-Rollenvertrag ist nicht verfügbar.')]);
        }
        $groups = $this->source->groupIds();
        $assignments = $this->source->assignments();
        $definitions = [
            [CapabilitySettingsService::OVERVIEW_READ, 'Stundenübersicht', 'Jahresübersicht', 'Stunden aller BR-Mitglieder lesen', 'Übersicht lesen', 'all-member-hours', false],
            [CapabilitySettingsService::ENTRY_MANAGE_OWN, 'Stundeneintrag', 'Eigener Monatswert', 'Eigene vergangene Monate anlegen, ändern und löschen', 'Eigenen Eintrag verwalten', 'own-month', true],
            [CapabilitySettingsService::PAYROLL_EXPORT_OWN, 'Abrechnung', 'Eigene PDF-Abrechnung', 'Eigene Monatsabrechnung erzeugen', 'Eigene Abrechnung exportieren', 'own-payroll', true],
            [CapabilitySettingsService::PAYROLL_EXPORT_FOREIGN, 'Abrechnung', 'Fremde PDF-Abrechnung', 'Monatsabrechnung eines anderen BR-Mitglieds erzeugen', 'Fremde Abrechnung exportieren', 'foreign-payroll', false],
            [CapabilitySettingsService::REMINDER_DASHBOARD, 'Erinnerungen', 'Reminder-Vorschau', 'Fehlende Monatseinträge und Mailstatus lesen', 'Reminder-Vorschau lesen', 'all-member-reminders', false],
            [CapabilitySettingsService::ENTRY_MANAGE_FOREIGN, 'Stundeneintrag', 'Fremder Monatswert', 'Einträge eines anderen BR-Mitglieds korrigieren oder löschen', 'Fremden Eintrag korrigieren', 'foreign-month', false],
        ];
        $rules = [];
        foreach ($definitions as [$permission, $type, $name, $detail, $label, $scope, $selfScoped]) {
            $roles = $assignments[$permission] ?? [];
            $effect = $roles === [] ? 'deny' : 'allow';
            $condition = $roles === []
                ? PermissionCondition::authenticated()
                : $this->condition($roles, $groups, $selfScoped);
            $rules[] = new PermissionRule($type, $name, $detail, $permission, $label, $effect, $scope, $condition, 'brstunden:AuthorizationService', 'high');
        }
        return new PermissionProviderResult($rules);
    }

    /** @param list<string> $roles @param array<string,string> $groups */
    private function condition(array $roles, array $groups, bool $selfScoped): PermissionCondition {
        $conditions = [];
        foreach ($roles as $role) {
            if (isset($groups[$role])) $conditions[] = PermissionCondition::group($groups[$role]);
        }
        $roleCondition = count($conditions) === 1 ? $conditions[0] : PermissionCondition::any($conditions);
        return $selfScoped ? PermissionCondition::all([$roleCondition, PermissionCondition::self()]) : $roleCondition;
    }
}
