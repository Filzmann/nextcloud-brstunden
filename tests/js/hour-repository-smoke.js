const assert = require('assert');

const calls = [];

global.window = {
    BRStunden: {
        api: {
            request(path, options = {}) {
                calls.push({ path, options });

                return Promise.resolve({ path, options });
            }
        }
    }
};

global.OC = {
    generateUrl(path) {
        return '/nextcloud' + path;
    }
};

require('../../../localbase/js/repositories/repository.js');
require('../../js/repositories/hour-repository.js');

(async () => {
    const { HourRepository } = window.BRStunden.repositories;
    const repository = new HourRepository(window.BRStunden.api);

    await repository.state();
    await repository.yearOverview(2026);
    await repository.saveEntry({ year: 2026, month: 7, hours: '1.5' });
    await repository.deleteEntry(2026, 7);
    await repository.reminderPreview();

    assert.deepStrictEqual(calls.map(call => call.path), [
        '/api/state',
        '/api/years/2026',
        '/api/entries',
        '/api/entries/2026/7',
        '/api/reminders/preview'
    ]);
    assert.strictEqual(calls[2].options.method, 'POST');
    assert.strictEqual(calls[2].options.body, '{"year":2026,"month":7,"hours":"1.5"}');
    assert.strictEqual(calls[3].options.method, 'DELETE');
    assert.strictEqual(
        repository.payrollPdfUrl(2026, 7),
        '/nextcloud/apps/brstunden/api/entries/2026/7/payroll.pdf'
    );

    console.log('BRStunden hour repository smoke test passed.');
})();
