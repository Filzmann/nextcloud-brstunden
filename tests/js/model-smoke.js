const assert = require('assert');

global.window = {};

require('../../../localbase/js/models/model.js');
require('../../js/models/hour-entry.js');

const { HourEntry } = window.BRStunden.models;

const entry = HourEntry.get({
    id: 1,
    user_id: 'simon',
    entry_year: 2026,
    entry_month: 7,
    brMinutes: 90,
    fobi_minutes: 30,
    note: 'Monatsabschluss'
});

assert(entry instanceof HourEntry);
assert.strictEqual(entry.userId, 'simon');
assert.strictEqual(entry.year, 2026);
assert.strictEqual(entry.month, 7);
assert.strictEqual(entry.brHours, 1.5);
assert.strictEqual(entry.fobiHours, 0.5);
assert.strictEqual(entry.toArray().note, 'Monatsabschluss');

console.log('BRStunden model smoke test passed.');
