<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$info = (string)file_get_contents($root . '/appinfo/info.xml');
$template = (string)file_get_contents($root . '/templates/admin.php');
$script = (string)file_get_contents($root . '/js/admin-settings.js');
$routes = (string)file_get_contents($root . '/appinfo/routes.php');
$documentController = (string)file_get_contents($root . '/lib/Controller/DocumentSettingsController.php');

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
if (!str_contains($template, 'type="checkbox"') || !str_contains($template, 'data-capability')) {
    throw new RuntimeException('Capability assignments must use selection controls and never free-text groups.');
}
foreach (['<table', '<caption', 'scope="col"', 'scope="row"', 'Berechtigungsmatrix', "'Berechtigung'"] as $contract) {
    if (!str_contains($template, $contract)) {
        throw new RuntimeException('Capability assignments need the shared matrix contract (Berechtigungen als Zeilen, Gruppen als Spalten): ' . $contract);
    }
}
if (!str_contains($script, 'requesttoken') || !str_contains($script, 'PUT')) {
    throw new RuntimeException('Admin settings writes must use the CSRF-protected update endpoint.');
}
if (!str_contains($info, '<admin>OCA\BrStunden\Settings\Admin</admin>') || !str_contains($info, '<admin-section>OCA\BrStunden\Settings\AdminSection</admin-section>')) {
    throw new RuntimeException('BR-Stunden capability settings must be registered in native Nextcloud administration.');
}
foreach (['organizationName', 'organizationAddress', 'issuePlace', 'introText', 'footerText'] as $field) {
    if (!str_contains($template, 'name="' . $field . '"')) {
        throw new RuntimeException('Document administration is missing field ' . $field);
    }
}
foreach (['brstunden-document-settings', 'brstunden-document-preview', 'brstunden-document-approve'] as $id) {
    if (!str_contains($template, 'id="' . $id . '"')) {
        throw new RuntimeException('Document administration is missing accessible control ' . $id);
    }
}
if (!str_contains($template, 'Ausgabelocale') || !str_contains($template, 'Feste Formularbestandteile')) {
    throw new RuntimeException('Document administration must explain fixed semantics and the retained output locale.');
}
if (!str_contains($script, '/api/document-settings') || !str_contains($script, '/approve')) {
    throw new RuntimeException('Document draft and approval actions must use dedicated CSRF-protected admin endpoints.');
}
if (!str_contains($routes, "documentSettings#saveDraft") || !str_contains($routes, "documentSettings#approve")) {
    throw new RuntimeException('Document administration routes are missing.');
}
if (str_contains($documentController, 'NoAdminRequired') || str_contains($documentController, 'NoCSRFRequired')) {
    throw new RuntimeException('Document configuration must remain in the native admin and CSRF protection boundary.');
}

echo "AdminSettingsSmokeTest: OK\n";
