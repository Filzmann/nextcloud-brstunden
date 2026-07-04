(function() {
    const format = window.BRStunden.format;
    const { HourEntry } = window.BRStunden.models;

    function entryFor(row, monthNumber) {
        return HourEntry.get(row.months[String(monthNumber)] || row.months[monthNumber] || null);
    }

    function isMissing(overview, monthNumber, entry) {
        return !entry && monthNumber <= Number(overview.editableUntilMonth || 0);
    }

    function hoursContent(entry) {
        const brHours = entry.brHours ?? entry.hours;
        const fobiHours = entry.fobiHours ?? 0;

        return `
            <span class="brs-hour-stack">
                <span>BR ${format.esc(format.hours(brHours))}</span>
                <span class="brs-hours-sub">FoBi ${format.esc(format.hours(fobiHours))}</span>
            </span>
        `;
    }

    function renderEntryCell(overview, row, month) {
        const entry = entryFor(row, month.number);
        const isCurrentUser = row.member.uid === overview.currentUser.uid;
        const editable = isCurrentUser && month.number <= Number(overview.editableUntilMonth || 0);
        const missing = isMissing(overview, month.number, entry);
        const classes = ['brs-month-cell'];
        if (missing) {
            classes.push('is-missing');
        }
        if (missing && isCurrentUser) {
            classes.push('is-own-missing');
        }
        if (editable) {
            classes.push('has-action');
        }

        if (editable) {
            const label = entry ? hoursContent(entry) : 'Fehlt';
            const note = entry ? entry.note : '';
            const noteMark = note ? '<span class="brs-note-mark" aria-label="Notiz vorhanden">*</span>' : '';

            return `
                <td class="${classes.join(' ')}">
                    <button
                        type="button"
                        class="brs-cell-button"
                        data-year="${format.esc(overview.year)}"
                        data-month="${format.esc(month.number)}"
                        data-exists="${entry ? '1' : '0'}"
                        data-br-hours="${entry ? format.esc(entry.brHours ?? entry.hours) : ''}"
                        data-fobi-hours="${entry ? format.esc(entry.fobiHours ?? 0) : '0'}"
                        data-note="${format.esc(note)}"
                        title="${format.esc(note)}"
                    >${entry ? label : format.esc(label)}${noteMark}</button>
                </td>
            `;
        }

        if (!entry) {
            return `<td class="${classes.join(' ')}">-</td>`;
        }

        const noteMark = entry.note ? '<span class="brs-note-mark" aria-label="Notiz vorhanden">*</span>' : '';

        return `<td class="${classes.join(' ')}" title="${format.esc(entry.note)}">${hoursContent(entry)}${noteMark}</td>`;
    }

    function render(overview) {
        const header = [
            '<th>BR-Mitglied</th>',
            ...overview.months.map((month) => `<th title="${format.esc(month.label)}">${format.esc(month.label.substring(0, 3))}</th>`),
            '<th>BR</th>',
            '<th>FoBi</th>',
            '<th>Summe</th>'
        ].join('');

        const rows = (overview.rows || []).map((row) => {
            const monthCells = overview.months.map((month) => renderEntryCell(overview, row, month)).join('');

            return `
                <tr${row.member.uid === overview.currentUser.uid ? ' class="is-current-user"' : ''}>
                    <th scope="row">${format.esc(row.member.displayName)}</th>
                    ${monthCells}
                    <td class="brs-total">${format.esc(format.hours(row.brTotalHours ?? row.totalHours))}</td>
                    <td class="brs-total">${format.esc(format.hours(row.fobiTotalHours ?? 0))}</td>
                    <td class="brs-total">${format.esc(format.hours(row.totalHours))}</td>
                </tr>
            `;
        }).join('');

        return `
            <h2>Jahresuebersicht ${format.esc(overview.year)}</h2>
            <div class="brs-table-wrap">
                <table class="brs-table">
                    <thead><tr>${header}</tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    }

    window.BRStunden = window.BRStunden || {};
    window.BRStunden.overview = { render };
})();
