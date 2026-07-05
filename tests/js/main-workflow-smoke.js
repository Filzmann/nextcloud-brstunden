const assert = require('assert');

class FakeElement {
    constructor(id = '') {
        this.id = id;
        this.value = '';
        this.innerHTML = '';
        this.textContent = '';
        this.hidden = true;
        this.disabled = false;
        this.className = '';
        this.listeners = {};
        this.focused = false;
    }

    addEventListener(type, listener) {
        this.listeners[type] = listener;
    }

    focus() {
        this.focused = true;
    }
}

class FakeCellButton extends FakeElement {
    constructor(dataset) {
        super();
        this.dataset = dataset;
    }

    closest(selector) {
        return selector === '.brs-cell-button' ? this : null;
    }
}

const elements = new Map([
    ['brs-notice', new FakeElement('brs-notice')],
    ['brs-year', new FakeElement('brs-year')],
    ['brs-load-year', new FakeElement('brs-load-year')],
    ['brs-entry-form', new FakeElement('brs-entry-form')],
    ['brs-entry-month', new FakeElement('brs-entry-month')],
    ['brs-entry-hours', new FakeElement('brs-entry-hours')],
    ['brs-entry-fobi-hours', new FakeElement('brs-entry-fobi-hours')],
    ['brs-entry-note', new FakeElement('brs-entry-note')],
    ['brs-save-entry', new FakeElement('brs-save-entry')],
    ['brs-delete-entry', new FakeElement('brs-delete-entry')],
    ['brs-download-payroll', new FakeElement('brs-download-payroll')],
    ['brs-overview', new FakeElement('brs-overview')],
    ['brs-load-reminders', new FakeElement('brs-load-reminders')],
    ['brs-reminder-preview', new FakeElement('brs-reminder-preview')]
]);

let domReady = null;
const repositoryCalls = [];
let lastRepository = null;

global.window = {
    BRStunden: {
        api: {},
        overview: {
            render(overview) {
                return `<section data-rendered-year="${overview.year}">Rendered ${overview.year}</section>`;
            }
        }
    },
    location: {
        href: ''
    }
};
global.document = {
    getElementById(id) {
        return elements.get(id) || null;
    },
    addEventListener(type, listener) {
        if (type === 'DOMContentLoaded') {
            domReady = listener;
        }
    }
};
global.OC = {
    generateUrl(path) {
        return '/nextcloud' + path;
    }
};

require('../../../localbase/js/ui/ui.js');
require('../../js/modules/format.js');

class FakeHourRepository {
    constructor() {
        lastRepository = this;
    }

    async state() {
        repositoryCalls.push(['state']);

        return {
            currentUser: { uid: 'simon' },
            months: [
                { number: 1, label: 'Januar' },
                { number: 2, label: 'Februar' },
                { number: 3, label: 'Maerz' }
            ],
            defaultYear: 2026,
            defaultMonth: 2,
            currentYear: 2026,
            currentMonth: 2
        };
    }

    async yearOverview(year) {
        repositoryCalls.push(['yearOverview', year]);

        return { year, rows: [] };
    }

    async saveEntry(payload) {
        repositoryCalls.push(['saveEntry', payload]);

        return { year: payload.year, rows: [] };
    }

    async deleteEntry(year, month) {
        repositoryCalls.push(['deleteEntry', year, month]);

        return { year, rows: [] };
    }

    async reminderPreview() {
        repositoryCalls.push(['reminderPreview']);

        return {
            year: 2026,
            untilMonth: 2,
            isLastDayOfMonth: false,
            summary: { willSend: 1, missingMonths: 2 },
            members: [
                {
                    displayName: 'Simon <Test>',
                    missing: [{ label: 'Januar' }, { label: 'Februar' }],
                    hasEmail: true,
                    willSend: true
                },
                {
                    displayName: 'Alex Test',
                    missing: [],
                    hasEmail: false,
                    willSend: false
                }
            ]
        };
    }

    payrollPdfUrl(year, month) {
        repositoryCalls.push(['payrollPdfUrl', year, month]);

        return `/pdf/${year}/${month}`;
    }
}

window.BRStunden.repositories = { HourRepository: FakeHourRepository };

require('../../js/main.js');

(async () => {
    assert.strictEqual(typeof domReady, 'function');
    await domReady();

    assert(lastRepository instanceof FakeHourRepository);
    assert.deepStrictEqual(repositoryCalls.slice(0, 2), [
        ['state'],
        ['yearOverview', 2026]
    ]);
    assert.strictEqual(elements.get('brs-year').value, 2026);
    assert(elements.get('brs-entry-month').innerHTML.includes('value="1"'));
    assert(elements.get('brs-entry-month').innerHTML.includes('value="3" disabled'));
    assert(elements.get('brs-overview').innerHTML.includes('Rendered 2026'));
    assert.strictEqual(elements.get('brs-delete-entry').disabled, true);
    assert.strictEqual(elements.get('brs-download-payroll').disabled, true);

    const entryButton = new FakeCellButton({
        year: '2026',
        month: '1',
        exists: '1',
        brHours: '2',
        fobiHours: '0.5',
        note: 'Mit Notiz'
    });
    elements.get('brs-overview').listeners.click({ target: entryButton });

    assert.strictEqual(elements.get('brs-entry-month').value, '1');
    assert.strictEqual(elements.get('brs-entry-hours').value, '2');
    assert.strictEqual(elements.get('brs-entry-fobi-hours').value, '0.5');
    assert.strictEqual(elements.get('brs-entry-note').value, 'Mit Notiz');
    assert.strictEqual(elements.get('brs-entry-hours').focused, true);
    assert.strictEqual(elements.get('brs-delete-entry').disabled, false);
    assert.strictEqual(elements.get('brs-download-payroll').disabled, false);

    elements.get('brs-download-payroll').listeners.click();
    assert.strictEqual(window.location.href, '/pdf/2026/1');

    elements.get('brs-entry-hours').value = '3';
    elements.get('brs-entry-fobi-hours').value = '1';
    elements.get('brs-entry-note').value = 'Neu';
    let prevented = false;
    await elements.get('brs-entry-form').listeners.submit({
        preventDefault() {
            prevented = true;
        }
    });
    assert.strictEqual(prevented, true);
    assert.deepStrictEqual(repositoryCalls.at(-1), [
        'saveEntry',
        { year: 2026, month: 1, hours: '3', fobiHours: '1', note: 'Neu' }
    ]);
    assert.strictEqual(elements.get('brs-notice').textContent, 'BR-Stunden gespeichert.');
    assert.strictEqual(elements.get('brs-notice').className, 'brs-notice brs-notice-success');

    await elements.get('brs-delete-entry').listeners.click();
    assert.deepStrictEqual(repositoryCalls.at(-1), ['deleteEntry', 2026, 1]);
    assert.strictEqual(elements.get('brs-entry-hours').value, '');
    assert.strictEqual(elements.get('brs-entry-fobi-hours').value, '0');
    assert.strictEqual(elements.get('brs-entry-note').value, '');
    assert.strictEqual(elements.get('brs-delete-entry').disabled, true);
    assert.strictEqual(elements.get('brs-download-payroll').disabled, true);

    await elements.get('brs-load-reminders').listeners.click();
    assert.deepStrictEqual(repositoryCalls.at(-1), ['reminderPreview']);
    assert(elements.get('brs-reminder-preview').innerHTML.includes('Simon &lt;Test&gt;'));
    assert(!elements.get('brs-reminder-preview').innerHTML.includes('Simon <Test>'));
    assert(elements.get('brs-reminder-preview').innerHTML.includes('1 E-Mail'));
    assert(elements.get('brs-reminder-preview').innerHTML.includes('Vorschau'));

    console.log('BRStunden main workflow smoke test passed.');
})().catch((error) => {
    console.error(error);
    process.exit(1);
});
