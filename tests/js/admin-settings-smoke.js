const assert = require('assert');

let domReady = null;
let submit = null;
let documentSubmit = null;
let previewClick = null;
let approveClick = null;
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
const documentStatus = {textContent: ''};
const approveButton = {
    disabled: true,
    addEventListener(type, listener) {
        if (type === 'click') approveClick = listener;
    },
};
const previewButton = {
    addEventListener(type, listener) {
        if (type === 'click') previewClick = listener;
    },
};
const documentElements = {
    organizationName: {value: 'Beispiel <Organisation>'},
    organizationAddress: {value: 'Musterweg 4\n12345 Beispielstadt'},
    issuePlace: {value: 'Beispielstadt'},
    introText: {value: 'Für {{member_name}}'},
    footerText: {value: 'Vorlage {{template_version}}'},
};
const documentForm = {
    elements: documentElements,
    addEventListener(type, listener) {
        if (type === 'submit') documentSubmit = listener;
    },
};
const previewElements = new Map([
    ['brstunden-preview-organization', {textContent: ''}],
    ['brstunden-preview-address', {textContent: ''}],
    ['brstunden-preview-intro', {textContent: ''}],
    ['brstunden-preview-issue', {textContent: ''}],
    ['brstunden-preview-footer', {textContent: ''}],
]);

global.document = {
    addEventListener(type, listener) {
        if (type === 'DOMContentLoaded') domReady = listener;
    },
    getElementById(id) {
        if (id === 'brstunden-capability-settings') return form;
        if (id === 'brstunden-capability-status') return status;
        if (id === 'brstunden-document-settings') return documentForm;
        if (id === 'brstunden-document-status') return documentStatus;
        if (id === 'brstunden-document-preview') return previewButton;
        if (id === 'brstunden-document-approve') return approveButton;
        if (previewElements.has(id)) return previewElements.get(id);
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
    assert.strictEqual(typeof documentSubmit, 'function');
    assert.strictEqual(typeof previewClick, 'function');
    assert.strictEqual(typeof approveClick, 'function');
    assert.strictEqual(previewElements.get('brstunden-preview-organization').textContent, 'Beispiel <Organisation>');
    assert.strictEqual(previewElements.get('brstunden-preview-intro').textContent, 'Für Beispielmitglied');

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

    response = {ok: true, payload: {ok: true, state: {draft: {version: 2}}}};
    await documentSubmit({preventDefault() {}});
    assert.strictEqual(requests[2].url, '/nextcloud/apps/brstunden/api/document-settings');
    assert.strictEqual(requests[2].options.method, 'PUT');
    assert.strictEqual(requests[2].options.headers.requesttoken, 'csrf-token');
    assert.deepStrictEqual(JSON.parse(requests[2].options.body), {document: {
        organizationName: 'Beispiel <Organisation>',
        organizationAddress: 'Musterweg 4\n12345 Beispielstadt',
        issuePlace: 'Beispielstadt',
        introText: 'Für {{member_name}}',
        footerText: 'Vorlage {{template_version}}',
    }});
    assert.strictEqual(approveButton.disabled, false);

    response = {ok: true, payload: {ok: true, state: {approved: {version: 2}}}};
    await approveClick();
    assert.strictEqual(requests[3].url, '/nextcloud/apps/brstunden/api/document-settings/approve');
    assert.strictEqual(requests[3].options.method, 'POST');
    assert.strictEqual(approveButton.disabled, true);
    assert.strictEqual(documentStatus.textContent, 'Vorlage v2 wurde freigegeben.');

    console.log('BRStunden admin settings smoke test passed.');
})().catch((error) => {
    console.error(error);
    process.exitCode = 1;
});
