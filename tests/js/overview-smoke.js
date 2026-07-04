const assert = require('assert');

global.window = {
    BRStunden: {}
};

require('../../../localbase/js/ui/ui.js');
require('../../../localbase/js/models/model.js');
require('../../js/models/hour-entry.js');
require('../../js/modules/format.js');
require('../../js/modules/overview.js');

const html = window.BRStunden.overview.render({
    year: 2026,
    currentUser: { uid: 'simon' },
    editableUntilMonth: 2,
    months: [
        { number: 1, label: 'Januar' },
        { number: 2, label: 'Februar' }
    ],
    rows: [
        {
            member: { uid: 'simon', displayName: 'Simon <Test>' },
            months: {
                1: {
                    id: 1,
                    userId: 'simon',
                    year: 2026,
                    month: 1,
                    brHours: 1.5,
                    fobiHours: 0.5,
                    totalHours: 2,
                    note: '<Notiz & "wichtig">'
                },
                2: null
            },
            brTotalHours: 1.5,
            fobiTotalHours: 0.5,
            totalHours: 2
        },
        {
            member: { uid: 'alex', displayName: 'Alex Test' },
            months: {
                1: null,
                2: {
                    id: 2,
                    userId: 'alex',
                    year: 2026,
                    month: 2,
                    brHours: 1,
                    fobiHours: 0,
                    totalHours: 1,
                    note: ''
                }
            },
            brTotalHours: 1,
            fobiTotalHours: 0,
            totalHours: 1
        }
    ]
});

assert(html.includes('Jahresuebersicht 2026'));
assert(html.includes('Simon &lt;Test&gt;'));
assert(!html.includes('Simon <Test>'));
assert(html.includes('class="brs-cell-button"'));
assert(html.includes('data-year="2026"'));
assert(html.includes('data-month="1"'));
assert(html.includes('data-exists="1"'));
assert(html.includes('data-note="&lt;Notiz &amp; &quot;wichtig&quot;&gt;"'));
assert(!html.includes('<Notiz & "wichtig">'));
assert(html.includes('BR 1,5'));
assert(html.includes('FoBi 0,5'));
assert(html.includes('is-current-user'));
assert(html.includes('is-own-missing'));
assert(html.includes('>Fehlt</button>'));
assert(html.includes('<td class="brs-month-cell is-missing">-</td>'));

console.log('BRStunden overview smoke test passed.');
