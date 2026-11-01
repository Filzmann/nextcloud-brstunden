<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$info = (string)file_get_contents($root . '/appinfo/info.xml');
$template = (string)file_get_contents($root . '/templates/admin.php');
$script = (string)file_get_contents($root . '/js/admin-settings.js');

foreach ([
    'hours.overview.read',
    'hours.entry.manage-own',
    'hours.payroll.export-own',
    'hours.payroll.export-foreign',
    'hours.reminders.read',
    'hours.entry.manage-foreign',
] as $capability) {
    if (!str_contains($template, $capability)) {
        throw new RuntimeException('Admin settings are missing capability ' . $capability);
    }
}
foreach (['member', 'chair', 'deputy'] as $role) {
    if (!str_contains($template, $role)) throw new RuntimeException('Admin settings are missing semantic role ' . $role);
}
if (!str_contains($template, 'type="checkbox"') || str_contains($template, 'type="text"')) {
    throw new RuntimeException('Capability assignments must use selection controls and never free-text groups.');
}
if (!str_contains($template, '<fieldset') || !str_contains($template, '<legend')) {
    throw new RuntimeException('Capability assignments need semantic fieldsets and legends.');
}
if (!str_contains($script, 'requesttoken') || !str_contains($script, 'PUT')) {
    throw new RuntimeException('Admin settings writes must use the CSRF-protected update endpoint.');
}
if (!str_contains($info, '<admin>OCA\BrStunden\Settings\Admin</admin>') || !str_contains($info, '<admin-section>OCA\BrStunden\Settings\AdminSection</admin-section>')) {
    throw new RuntimeException('BR-Stunden capability settings must be registered in native Nextcloud administration.');
}

echo "AdminSettingsSmokeTest: OK\n";
