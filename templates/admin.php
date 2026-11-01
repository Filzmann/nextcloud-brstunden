<?php

declare(strict_types=1);

use OCA\BrStunden\AppInfo\Application;

script(Application::APP_ID, 'admin-settings');
style(Application::APP_ID, 'style');
$assignments = is_array($_['assignments'] ?? null) ? $_['assignments'] : [];
$source = is_array($_['roleSource'] ?? null) ? $_['roleSource'] : [];
$roles = [
    'member' => 'BR-Mitglied',
    'chair' => 'BR-Vorsitz',
    'deputy' => 'Stellvertretender BR-Vorsitz',
];
$capabilities = [
    'hours.overview.read' => ['Jahresübersicht lesen', 'Zeigt die Stundenübersicht aller BR-Mitglieder.'],
    'hours.entry.manage-own' => ['Eigene Stunden verwalten', 'Erlaubt das Anlegen, Ändern und Löschen eigener Monatswerte.'],
    'hours.payroll.export-own' => ['Eigene Abrechnung exportieren', 'Erzeugt die eigene monatliche PDF-Abrechnung.'],
    'hours.payroll.export-foreign' => ['Fremde Abrechnung exportieren', 'Erzeugt eine PDF-Abrechnung für ein anderes BR-Mitglied.'],
    'hours.reminders.read' => ['Reminder-Vorschau lesen', 'Zeigt fehlende Monate und den aktuellen Mailstatus.'],
    'hours.entry.manage-foreign' => ['Fremde Stunden korrigieren', 'Erlaubt Änderungen und Löschungen bei anderen BR-Mitgliedern; standardmäßig deaktiviert.'],
];
?>
<section id="brstunden-admin" class="section brs-admin" aria-labelledby="brstunden-admin-heading">
    <h2 id="brstunden-admin-heading">BR-Stunden: Berechtigungen</h2>
    <p>Ordne die sechs Fachberechtigungen ausschließlich den kanonischen BR-Rollen zu. Die aktuellen Gruppen-IDs stammen zur Laufzeit aus LocalBase und werden hier nicht als Freitext gespeichert.</p>
    <?php if (($source['status'] ?? '') !== 'VALID'): ?>
        <p role="alert"><?php p((string)($source['warning'] ?? 'Der BR-Rollenvertrag ist nicht verfügbar.')); ?> Bis zur Behebung bleiben Fachzugriffe gesperrt.</p>
    <?php endif; ?>
    <form id="brstunden-capability-settings">
        <?php foreach ($capabilities as $capability => [$label, $description]): ?>
            <?php $selected = is_array($assignments[$capability] ?? null) ? $assignments[$capability] : []; ?>
            <fieldset class="brs-permission-fieldset" data-capability="<?php p($capability); ?>">
                <legend><?php p($label); ?></legend>
                <p><?php p($description); ?></p>
                <?php foreach ($roles as $role => $roleLabel): ?>
                    <label>
                        <input type="checkbox" name="<?php p($capability); ?>" value="<?php p($role); ?>" <?php if (in_array($role, $selected, true)): ?>checked<?php endif; ?>>
                        <?php p($roleLabel); ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>
        <?php endforeach; ?>
        <button type="submit" class="primary">Berechtigungen speichern</button>
        <p id="brstunden-capability-status" role="status" aria-live="polite"></p>
    </form>
</section>
