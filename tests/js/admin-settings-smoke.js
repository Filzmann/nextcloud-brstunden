const assert = require('assert');

let domReady = null;
let submit = null;
let response = {ok: true, payload: {ok: true}};
const requests = [];

const fieldsets = [
    {
        dataset: {capability: 'hours.overview.read'},
        querySelectorAll() {
            return [{value: 'member'}, {value: 'chair'}];
        },
    },
    {
        dataset: {capability: 'hours.entry.manage-foreign'},
        querySelectorAll() {
            return [];
        },
    },
];
const form = {
    addEventListener(type, listener) {
        if (type === 'submit') submit = listener;
    },
    querySelectorAll(selector) {
        return selector === '[data-capability]' ? fieldsets : [];
    },
};
const status = {textContent: ''};

global.document = {
    addEventListener(type, listener) {
        if (type === 'DOMContentLoaded') domReady = listener;
    },
    getElementById(id) {
        if (id === 'brstunden-capability-settings') return form;
        if (id === 'brstunden-capability-status') return status;
        return null;
    },
};
global.OC = {
    requestToken: 'csrf-token',
    generateUrl(path) {
        return '/nextcloud' + path;
    },
};
global.fetch = async (url, options) => {
    requests.push({url, options});
    return {
        ok: response.ok,
        async json() {
            return response.payload;
        },
    };
};

require('../../js/admin-settings.js');

(async () => {
    assert.strictEqual(typeof domReady, 'function');
    domReady();
    assert.strictEqual(typeof submit, 'function');

    let prevented = false;
    await submit({preventDefault() { prevented = true; }});
    assert.strictEqual(prevented, true);
    assert.strictEqual(requests[0].url, '/nextcloud/apps/brstunden/api/capability-settings');
    assert.strictEqual(requests[0].options.method, 'PUT');
    assert.strictEqual(requests[0].options.headers.requesttoken, 'csrf-token');
    assert.deepStrictEqual(JSON.parse(requests[0].options.body), {
        assignments: {
            'hours.overview.read': ['member', 'chair'],
            'hours.entry.manage-foreign': [],
        },
    });
    assert.strictEqual(status.textContent, 'Die Berechtigungszuordnung wurde gespeichert.');

    response = {ok: false, payload: {message: 'Ungültige Zuordnung'}};
    await submit({preventDefault() {}});
    assert.strictEqual(status.textContent, 'Ungültige Zuordnung');

    console.log('BRStunden admin settings smoke test passed.');
})().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});
