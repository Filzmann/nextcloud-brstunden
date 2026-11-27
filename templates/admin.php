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
$documentState = is_array($_['documentState'] ?? null) ? $_['documentState'] : [];
$approvedDocument = is_array($documentState['approved'] ?? null) ? $documentState['approved'] : [];
$draftDocument = is_array($documentState['draft'] ?? null) ? $documentState['draft'] : null;
$editedDocument = $draftDocument ?? $approvedDocument;
$allowedPlaceholders = is_array($documentState['allowedPlaceholders'] ?? null) ? $documentState['allowedPlaceholders'] : [];
?>
<section id="brstunden-admin" class="section brs-admin" aria-labelledby="brstunden-admin-heading">
    <h2 id="brstunden-admin-heading"><?php p($l->t('BR-Stunden: Berechtigungen')); ?></h2>
    <p><?php p($l->t('Ordne die sechs Fachberechtigungen ausschließlich den kanonischen BR-Rollen zu. Die aktuellen Gruppen-IDs stammen zur Laufzeit aus LocalBase und werden hier nicht als Freitext gespeichert.')); ?></p>
    <?php if (($source['status'] ?? '') !== 'VALID'): ?>
        <p role="alert"><?php p((string)($source['warning'] ?? 'Der BR-Rollenvertrag ist nicht verfügbar.')); ?> <?php p($l->t('Bis zur Behebung bleiben Fachzugriffe gesperrt.')); ?></p>
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
        <button type="submit" class="primary"><?php p($l->t('Berechtigungen speichern')); ?></button>
        <p id="brstunden-capability-status" role="status" aria-live="polite"></p>
    </form>
</section>

<section class="section brs-admin" aria-labelledby="brstunden-document-heading">
    <h2 id="brstunden-document-heading">Abrechnungsdokument</h2>
    <p><?php p($l->t('Pflege hier ausschließlich die organisationsbezogenen Stammdaten und optionalen Texte. Feste Formularbestandteile wie Mitglied, Monat, BR-/Fortbildungsstunden, Gesamtsumme und Unterschrift sowie alle Berechnungen bleiben unveränderlich.')); ?></p>
    <p><?php p($l->t('Freigegeben ist Vorlage v')); ?><?php p((string)($approvedDocument['version'] ?? 1)); ?>. Ausgabelocale: <strong><?php p((string)($approvedDocument['outputLocale'] ?? 'de-DE')); ?></strong>. <?php p($l->t('Ein Entwurf verändert laufende PDF-Exporte erst nach ausdrücklicher Freigabe.')); ?></p>
    <?php if ($draftDocument !== null): ?>
        <p role="status"><?php p($l->t('Entwurf für Vorlage v')); ?><?php p((string)$draftDocument['version']); ?> vorhanden.</p>
    <?php endif; ?>

    <form id="brstunden-document-settings">
        <div class="brs-document-grid">
            <label>Organisationsname
                <input name="organizationName" type="text" maxlength="160" required value="<?php p((string)($editedDocument['organizationName'] ?? '')); ?>">
            </label>
            <label>Ausstellungsort
                <input name="issuePlace" type="text" maxlength="120" required value="<?php p((string)($editedDocument['issuePlace'] ?? '')); ?>">
            </label>
            <label class="brs-document-wide">Organisationsanschrift
                <textarea name="organizationAddress" maxlength="500" rows="3" required><?php p((string)($editedDocument['organizationAddress'] ?? '')); ?></textarea>
            </label>
            <label class="brs-document-wide">Optionaler Einleitungstext
                <textarea name="introText" maxlength="800" rows="3"><?php p((string)($editedDocument['introText'] ?? '')); ?></textarea>
            </label>
            <label class="brs-document-wide"><?php p($l->t('Optionaler Fußtext')); ?>
                <textarea name="footerText" maxlength="800" rows="3"><?php p((string)($editedDocument['footerText'] ?? '')); ?></textarea>
            </label>
        </div>
        <p><?php p($l->t('Zulässige, nicht ausführbare Platzhalter:')); ?> <?php p(implode(', ', array_map(static fn(string $name): string => '{{' . $name . '}}', $allowedPlaceholders))); ?></p>
        <div class="brs-document-actions">
            <button type="button" id="brstunden-document-preview"><?php p($l->t('Vorschau aktualisieren')); ?></button>
            <button type="submit" class="primary"><?php p($l->t('Entwurf speichern')); ?></button>
            <button type="button" id="brstunden-document-approve" <?php if ($draftDocument === null): ?>disabled<?php endif; ?>><?php p($l->t('Entwurf freigeben')); ?></button>
        </div>
        <p id="brstunden-document-status" role="status" aria-live="polite"></p>
    </form>

    <section class="brs-document-preview" aria-labelledby="brstunden-document-preview-heading">
        <h3 id="brstunden-document-preview-heading"><?php p($l->t('Vorschau mit synthetischen Beispieldaten')); ?></h3>
        <p><strong id="brstunden-preview-organization"></strong></p>
        <p id="brstunden-preview-address"></p>
        <p id="brstunden-preview-intro"></p>
        <p><?php p($l->t('Abrechnung für Beispielmitglied')); ?> · Juli 2026 · <?php p($l->t('BR 1,5 Stunden')); ?> · <?php p($l->t('Fortbildung 0,5 Stunden')); ?> · <?php p($l->t('Gesamt 2 Stunden')); ?></p>
        <p id="brstunden-preview-issue"></p>
        <p id="brstunden-preview-footer"></p>
    </section>
</section>
