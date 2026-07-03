<?php
script('brstunden', 'modules/api');
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
                Stunden
                <input id="brs-entry-hours" type="number" min="0" max="744" step="0.25" inputmode="decimal">
            </label>
            <label>
                Notiz
                <input id="brs-entry-note" type="text" maxlength="1000">
            </label>
            <button type="submit">Speichern</button>
        </form>
    </section>

    <div id="brs-notice" class="brs-notice" hidden></div>
    <section id="brs-overview" class="brs-overview"></section>
</div>
