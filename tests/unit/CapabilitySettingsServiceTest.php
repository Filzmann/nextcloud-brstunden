<?php

declare(strict_types=1);

namespace OCA\BrStunden\AppInfo { final class Application { public const APP_ID = 'brstunden'; } }
namespace OCP {
    interface IAppConfig {
        public function getValueString(string $appId, string $key, string $default = ''): string;
        public function setValueString(string $appId, string $key, string $value): void;
    }
}

namespace {
    use OCA\BrStunden\Service\BrGroupsService;
    use OCA\BrStunden\Service\CapabilitySettingsService;
    use OCP\IAppConfig;
    use function OCA\BrStunden\Tests\assertSameValue;

    $config = new class implements IAppConfig {
        public array $values = [];
        public function getValueString(string $appId, string $key, string $default = ''): string { return $this->values[$appId][$key] ?? $default; }
        public function setValueString(string $appId, string $key, string $value): void { $this->values[$appId][$key] = $value; }
    };

    $settings = new CapabilitySettingsService($config);
    assertSameValue([BrGroupsService::ROLE_MEMBER], $settings->rolesFor(CapabilitySettingsService::OVERVIEW_READ), 'BS1 must default to every BR member.');
    assertSameValue([BrGroupsService::ROLE_MEMBER], $settings->rolesFor(CapabilitySettingsService::ENTRY_MANAGE_OWN), 'BS2 must default to every BR member.');
    assertSameValue([BrGroupsService::ROLE_MEMBER], $settings->rolesFor(CapabilitySettingsService::PAYROLL_EXPORT_OWN), 'BS3 own export must default to every BR member.');
    assertSameValue([BrGroupsService::ROLE_CHAIR, BrGroupsService::ROLE_DEPUTY], $settings->rolesFor(CapabilitySettingsService::PAYROLL_EXPORT_FOREIGN), 'BS3 foreign export must default to chair and deputy.');
    assertSameValue([BrGroupsService::ROLE_CHAIR, BrGroupsService::ROLE_DEPUTY], $settings->rolesFor(CapabilitySettingsService::REMINDER_DASHBOARD), 'BS4 must default to chair and deputy.');
    assertSameValue([], $settings->rolesFor(CapabilitySettingsService::ENTRY_MANAGE_FOREIGN), 'BS5 must be disabled by default.');

    $updated = $settings->update([
        CapabilitySettingsService::ENTRY_MANAGE_FOREIGN => [BrGroupsService::ROLE_DEPUTY, BrGroupsService::ROLE_DEPUTY],
    ]);
    assertSameValue([BrGroupsService::ROLE_DEPUTY], $updated[CapabilitySettingsService::ENTRY_MANAGE_FOREIGN], 'Assignments must be normalized and persisted by semantic role.');

    $thrown = false;
    try {
        $settings->update([CapabilitySettingsService::ENTRY_MANAGE_FOREIGN => ['arbitrary-native-group']]);
    } catch (\DomainException) {
        $thrown = true;
    }
    assertSameValue(true, $thrown, 'Arbitrary native groups must be rejected.');

    $beforeRejectedBatch = $config->values;
    $thrown = false;
    try {
        $settings->update([
            CapabilitySettingsService::OVERVIEW_READ => [BrGroupsService::ROLE_CHAIR],
            'unknown.permission' => [BrGroupsService::ROLE_MEMBER],
        ]);
    } catch (\DomainException) {
        $thrown = true;
    }
    assertSameValue(true, $thrown, 'A batch containing an unknown capability must be rejected.');
    assertSameValue($beforeRejectedBatch, $config->values, 'A rejected settings batch must not persist partial permission changes.');

    $config->values['brstunden'][CapabilitySettingsService::CONFIG_KEYS[CapabilitySettingsService::OVERVIEW_READ]] = '{broken';
    assertSameValue([], $settings->rolesFor(CapabilitySettingsService::OVERVIEW_READ), 'Corrupt stored assignments must fail closed instead of restoring a permissive default.');
    assertSameValue(false, $settings->isComplete(), 'Corrupt stored assignments must make the permission inventory incomplete.');

    echo "CapabilitySettingsService tests passed\n";
}
