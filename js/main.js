(function() {
    const format = window.BRStunden.format;
    const overviewRenderer = window.BRStunden.overview;
    const { HourRepository } = window.BRStunden.repositories;
    const { Notice, byId } = window.LocalBase.ui;
    const repository = new HourRepository(window.BRStunden.api);
    const noticeBox = new Notice('brs-notice', {
        baseClass: 'brs-notice',
        typeClassPrefix: 'brs-notice-'
    });
    const state = {
        currentUser: null,
        months: [],
        year: new Date().getFullYear(),
        currentYear: new Date().getFullYear(),
        currentMonth: new Date().getMonth() + 1,
        overview: null,
        selectedEntry: null,
        permissions: {}
    };

    function notice(message, type = 'info') {
        noticeBox.show(message, type);
    }

    function errorNotice(error, fallback) {
        noticeBox.error(error, fallback);
    }

    async function init() {
        try {
            window.BRStunden.persistentHorizontalScroll.bind(byId('brstunden-app'));
            const data = await repository.state();
            state.currentUser = data.currentUser;
            state.months = data.months || [];
            state.year = data.defaultYear;
            state.currentYear = data.currentYear || data.defaultYear;
            state.currentMonth = data.currentMonth || data.defaultMonth;
            state.permissions = data.permissions || {};
            const reminders = byId('brs-reminders');
            if (reminders) reminders.hidden = !state.permissions['hours.reminders.read'];
            byId('brs-year').value = state.year;
            renderMonthOptions(data.defaultMonth);
            bindEvents();
            await loadYear();
        } catch (e) {
            errorNotice(e, 'BR-Stunden konnten nicht geladen werden.');
        }
    }

    function bindEvents() {
        byId('brs-load-year').addEventListener('click', loadYear);
        byId('brs-year').addEventListener('input', () => renderMonthOptions());
        byId('brs-entry-form').addEventListener('submit', saveEntry);
        byId('brs-delete-entry').addEventListener('click', deleteEntry);
        byId('brs-download-payroll').addEventListener('click', downloadPayrollPdf);
        byId('brs-overview').addEventListener('click', fillFormFromTable);
        byId('brs-load-reminders').addEventListener('click', loadReminderPreview);
    }

    function selectedYear() {
        return Number(byId('brs-year').value || state.year);
    }

    function canEditMonth(year, month) {
        if (year < state.currentYear) {
            return true;
        }

        if (year > state.currentYear) {
            return false;
        }

        return month <= state.currentMonth;
    }

    function renderMonthOptions(preferredMonth) {
        const year = selectedYear();
        const select = byId('brs-entry-month');
        const requestedMonth = Number(preferredMonth || select.value || state.currentMonth);
        let fallbackMonth = null;

        for (const month of state.months) {
            if (canEditMonth(year, month.number)) {
                fallbackMonth = month.number;
            }
        }

        const selectedMonth = canEditMonth(year, requestedMonth) ? requestedMonth : fallbackMonth;
        select.innerHTML = state.months.map((month) => {
            const disabled = !canEditMonth(year, month.number);
            const selected = month.number === selectedMonth;

            return `<option value="${format.esc(month.number)}"${selected ? ' selected' : ''}${disabled ? ' disabled' : ''}>${format.esc(month.label)}</option>`;
        }).join('');
        select.disabled = selectedMonth === null;
        byId('brs-save-entry').disabled = selectedMonth === null;
        if (!state.selectedEntry || state.selectedEntry.year !== year || state.selectedEntry.month !== selectedMonth) {
            setSelectedEntry(null);
        }
    }

    async function loadYear() {
        const year = Number(byId('brs-year').value || state.year);
        state.year = year;
        setSelectedEntry(null);
        renderMonthOptions();
        notice('');
        try {
            state.overview = await repository.yearOverview(year);
            renderOverview();
        } catch (e) {
            errorNotice(e, 'Jahresübersicht konnte nicht geladen werden.');
        }
    }

    async function saveEntry(event) {
        event.preventDefault();
        const month = Number(byId('brs-entry-month').value);
        if (!month) {
            notice('Für dieses Jahr ist noch kein Monat speicherbar.', 'error');
            return;
        }

        const payload = {
            year: Number(byId('brs-year').value || state.year),
            month,
            hours: byId('brs-entry-hours').value,
            fobiHours: byId('brs-entry-fobi-hours').value,
            note: byId('brs-entry-note').value,
            userId: state.selectedEntry?.userId || state.currentUser.uid
        };

        try {
            state.overview = await repository.saveEntry(payload);
            notice('BR-Stunden gespeichert.', 'success');
            setSelectedEntry({
                year: payload.year,
                month: payload.month,
                userId: payload.userId,
                exists: true,
                canEdit: true,
                canExport: payload.userId === state.currentUser.uid
                    ? Boolean(state.permissions['hours.payroll.export-own'])
                    : Boolean(state.permissions['hours.payroll.export-foreign'])
            });
            renderOverview();
        } catch (e) {
            errorNotice(e, 'Eintrag konnte nicht gespeichert werden.');
        }
    }

    async function deleteEntry() {
        if (!state.selectedEntry || !state.selectedEntry.exists) {
            return;
        }

        const year = Number(byId('brs-year').value || state.year);
        const month = Number(byId('brs-entry-month').value);

        try {
            state.overview = await repository.deleteEntry(year, month, state.selectedEntry.userId);
            notice('Eintrag gelöscht.', 'success');
            byId('brs-entry-hours').value = '';
            byId('brs-entry-fobi-hours').value = '0';
            byId('brs-entry-note').value = '';
            setSelectedEntry({year, month, userId: state.selectedEntry.userId, exists: false, canEdit: true, canExport: false});
            renderOverview();
        } catch (e) {
            errorNotice(e, 'Eintrag konnte nicht geloescht werden.');
        }
    }

    function downloadPayrollPdf() {
        if (!state.selectedEntry || !state.selectedEntry.exists) {
            notice('Bitte zuerst einen gespeicherten Eintrag auswählen.', 'error');
            return;
        }

        const year = Number(byId('brs-year').value || state.year);
        const month = Number(byId('brs-entry-month').value);
        window.location.href = repository.payrollPdfUrl(year, month, state.selectedEntry.userId);
    }

    function fillFormFromTable(event) {
        const button = event.target.closest('.brs-cell-button');
        if (!button) {
            return;
        }

        const year = Number(button.dataset.year || state.year);
        const month = Number(button.dataset.month);
        byId('brs-year').value = year;
        state.year = year;
        renderMonthOptions(month);
        byId('brs-entry-month').value = String(month);
        byId('brs-entry-hours').value = button.dataset.brHours || '';
        byId('brs-entry-fobi-hours').value = button.dataset.fobiHours || '0';
        byId('brs-entry-note').value = button.dataset.note || '';
        setSelectedEntry({
            year,
            month,
            userId: button.dataset.userId || state.currentUser.uid,
            exists: button.dataset.exists === '1',
            canEdit: button.dataset.canEdit === '1',
            canExport: button.dataset.canExport === '1'
        });
        if (button.dataset.canEdit === '1') byId('brs-entry-hours').focus();
    }

    function setSelectedEntry(entry) {
        state.selectedEntry = entry;
        const canEdit = Boolean(entry && entry.canEdit !== false);
        byId('brs-entry-hours').disabled = !canEdit;
        byId('brs-entry-fobi-hours').disabled = !canEdit;
        byId('brs-entry-note').disabled = !canEdit;
        byId('brs-save-entry').disabled = !canEdit;
        byId('brs-delete-entry').disabled = !entry || !entry.exists || !canEdit;
        byId('brs-download-payroll').disabled = !entry || !entry.exists || entry.canExport === false;
    }

    function renderOverview() {
        const overview = state.overview;
        if (!overview) {
            return;
        }

        byId('brs-overview').innerHTML = overviewRenderer.render(overview);
    }

    async function loadReminderPreview() {
        try {
            const preview = await repository.reminderPreview();
            renderReminderPreview(preview);
        } catch (e) {
            errorNotice(e, 'Reminder-Vorschau konnte nicht geladen werden.');
        }
    }

    function renderReminderPreview(preview) {
        const target = byId('brs-reminder-preview');
        const rows = (preview.members || []).filter((member) => member.missing.length || !member.hasEmail);

        if (rows.length === 0) {
            target.innerHTML = `<p class="brs-empty">Alle Einträge bis ${format.esc(monthName(preview.untilMonth))} ${format.esc(preview.year)} sind vollständig.</p>`;
            return;
        }

        const body = rows.map((member) => {
            const missing = member.missing.map((month) => month.label).join(', ');
            const status = member.willSend ? 'E-Mail' : 'Keine E-Mail';

            return `
                <tr>
                    <th scope="row">${format.esc(member.displayName)}</th>
                    <td>${format.esc(missing || '-')}</td>
                    <td>${format.esc(status)}</td>
                </tr>
            `;
        }).join('');

        target.innerHTML = `
            <div class="brs-reminder-summary">
                <span>${format.esc(preview.summary.willSend)} E-Mail</span>
                <span>${format.esc(preview.summary.missingMonths)} fehlende Monate</span>
                <span>${preview.isLastDayOfMonth ? 'Monatsende' : 'Vorschau'}</span>
            </div>
            <div class="brs-table-wrap" data-persistent-horizontal-scroll>
                <table class="brs-table brs-reminder-table">
                    <thead><tr><th>BR-Mitglied</th><th>Fehlende Monate</th><th>Status</th></tr></thead>
                    <tbody>${body}</tbody>
                </table>
            </div>
        `;
    }

    function monthName(monthNumber) {
        const month = state.months.find((entry) => entry.number === Number(monthNumber));

        return month ? month.label : '';
    }

    document.addEventListener('DOMContentLoaded', init);
})();
