(function() {
    const api = window.BRStunden.api;
    const state = {
        currentUser: null,
        months: [],
        year: new Date().getFullYear(),
        overview: null
    };

    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function byId(id) {
        return document.getElementById(id);
    }

    function notice(message, type = 'info') {
        const box = byId('brs-notice');
        box.textContent = message;
        box.className = 'brs-notice brs-notice-' + type;
        box.hidden = !message;
    }

    async function init() {
        try {
            const data = await api.request('/api/state');
            state.currentUser = data.currentUser;
            state.months = data.months || [];
            state.year = data.defaultYear;
            byId('brs-year').value = state.year;
            renderMonthOptions(data.defaultMonth);
            bindEvents();
            await loadYear();
        } catch (e) {
            notice(e.message || 'BR-Stunden konnten nicht geladen werden.', 'error');
        }
    }

    function bindEvents() {
        byId('brs-load-year').addEventListener('click', loadYear);
        byId('brs-entry-form').addEventListener('submit', saveEntry);
    }

    function renderMonthOptions(defaultMonth) {
        byId('brs-entry-month').innerHTML = state.months.map((month) => (
            `<option value="${esc(month.number)}"${month.number === defaultMonth ? ' selected' : ''}>${esc(month.label)}</option>`
        )).join('');
    }

    async function loadYear() {
        const year = Number(byId('brs-year').value || state.year);
        state.year = year;
        notice('');
        state.overview = await api.request('/api/years/' + encodeURIComponent(String(year)));
        renderOverview();
    }

    async function saveEntry(event) {
        event.preventDefault();
        const payload = {
            year: Number(byId('brs-year').value || state.year),
            month: Number(byId('brs-entry-month').value),
            hours: byId('brs-entry-hours').value,
            note: byId('brs-entry-note').value
        };

        try {
            state.overview = await api.request('/api/entries', {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            notice('BR-Stunden gespeichert.', 'success');
            renderOverview();
        } catch (e) {
            notice(e.message || 'Eintrag konnte nicht gespeichert werden.', 'error');
        }
    }

    function renderOverview() {
        const overview = state.overview;
        if (!overview) {
            return;
        }

        const header = [
            '<th>BR-Mitglied</th>',
            ...overview.months.map((month) => `<th>${esc(month.label.substring(0, 3))}</th>`),
            '<th>Summe</th>'
        ].join('');

        const rows = (overview.rows || []).map((row) => {
            const monthCells = overview.months.map((month) => {
                const entry = row.months[String(month.number)] || row.months[month.number];
                if (!entry) {
                    return '<td class="is-missing">-</td>';
                }

                return `<td title="${esc(entry.note)}">${esc(entry.hours)}</td>`;
            }).join('');

            return `
                <tr${row.member.uid === overview.currentUser.uid ? ' class="is-current-user"' : ''}>
                    <th scope="row">${esc(row.member.displayName)}</th>
                    ${monthCells}
                    <td class="brs-total">${esc(row.totalHours)}</td>
                </tr>
            `;
        }).join('');

        byId('brs-overview').innerHTML = `
            <h2>Jahresuebersicht ${esc(overview.year)}</h2>
            <div class="brs-table-wrap">
                <table class="brs-table">
                    <thead><tr>${header}</tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    }

    document.addEventListener('DOMContentLoaded', init);
})();
