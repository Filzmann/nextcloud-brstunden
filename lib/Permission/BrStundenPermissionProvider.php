<?php

declare(strict_types=1);

namespace OCA\BrStunden\Permission;

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
        $groupId = $this->source->memberGroupId();

        return new PermissionProviderResult([
            $this->rule('Stundenübersicht', 'Jahresübersicht', 'Stunden aller Mitglieder lesen', 'hours.overview.read', 'Übersicht lesen', 'all-member-hours', PermissionCondition::group($groupId)),
            $this->rule('Stundeneintrag', 'Eigener Monatswert', 'Eigene vergangene Monate anlegen, ändern und löschen', 'hours.entry.manage-own', 'Eigenen Eintrag verwalten', 'own-month', PermissionCondition::all([PermissionCondition::group($groupId), PermissionCondition::self()])),
            $this->rule('Abrechnung', 'Monatlicher PDF-Export', 'Abrechnungsübersicht für die Mitgliedergruppe erzeugen', 'hours.payroll.export', 'Abrechnung exportieren', 'member-payroll', PermissionCondition::group($groupId)),
        ]);
    }

    private function rule(string $type, string $name, string $detail, string $permission, string $label, string $scope, PermissionCondition $condition): PermissionRule {
        return new PermissionRule($type, $name, $detail, $permission, $label, 'allow', $scope, $condition, 'brstunden:BrMemberService', 'high');
    }
}
