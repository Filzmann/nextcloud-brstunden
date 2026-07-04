<?php
script('localbase', 'api/api-client');
script('brstunden', 'modules/api');
script('localbase', 'models/model');
script('brstunden', 'models/hour-entry');
script('brstunden', 'repositories/hour-repository');
script('localbase', 'ui/ui');
script('brstunden', 'modules/format');
script('brstunden', 'modules/overview');
script('brstunden', 'main');
style('brstunden', 'style');
?>

<div id="brstunden-app">
    <header class="brs-head">
        <h1>BR-Stunden</h1>
        <div class="brs-controls">
            <label>
                Jahr
                <input id="brs-year" type="number" min="2000" max="2100" step="1">
            </label>
            <button type="button" id="brs-load-year">Aktualisieren</button>
        </div>
    </header>

    <section class="brs-entry">
        <h2>Eigene Stunden eintragen</h2>
        <form id="brs-entry-form">
            <label>
                Monat
                <select id="brs-entry-month"></select>
            </label>
            <label>
                BR-Stunden
                <input id="brs-entry-hours" type="number" min="0" max="744" step="0.25" inputmode="decimal">
            </label>
            <label>
                FoBi-Stunden
                <input id="brs-entry-fobi-hours" type="number" min="0" max="744" step="0.25" inputmode="decimal" value="0">
            </label>
            <label>
                Notiz
                <input id="brs-entry-note" type="text" maxlength="1000">
            </label>
            <button type="submit" id="brs-save-entry">Speichern</button>
            <button type="button" id="brs-delete-entry" disabled>Loeschen</button>
            <button type="button" id="brs-download-payroll" disabled>PDF</button>
        </form>
    </section>

    <div id="brs-notice" class="brs-notice" hidden></div>
    <section id="brs-overview" class="brs-overview"></section>

    <section class="brs-reminders">
        <div class="brs-section-head">
            <h2>Erinnerungen</h2>
            <button type="button" id="brs-load-reminders">Pruefen</button>
        </div>
        <div id="brs-reminder-preview" class="brs-reminder-preview"></div>
    </section>
</div>
