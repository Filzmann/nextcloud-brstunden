import { existsSync, readFileSync, readdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';
import assert from 'node:assert/strict';

const appRoot = resolve(dirname(fileURLToPath(import.meta.url)), '../..');
const bundleRoot = resolve(appRoot, 'js/shared');
const requiredAssets = [
    'api-client.js',
    'model.js',
    'repository.js',
    'ui.js',
    'LICENSE',
    'flz-shared.lock.json',
];

for (const asset of requiredAssets) {
    if (!existsSync(resolve(bundleRoot, asset))) {
        throw new Error(`Das app-lokale Kategorie-A-Artefakt fehlt: js/shared/${asset}`);
    }
}

const lock = JSON.parse(readFileSync(resolve(bundleRoot, 'flz-shared.lock.json'), 'utf8'));
if (lock.schema !== 1
    || lock.component !== 'browser'
    || lock.app_id !== 'brstunden'
    || lock.app_namespace !== 'BrStunden'
    || lock.browser_global !== 'window.OCA.BrStunden.Shared'
    || lock.packages?.browser?.name !== '@filzmann/shared-browser'
    || lock.packages?.browser?.version !== '0.1.0'
    || !/^[a-f0-9]{64}$/.test(lock.packages?.browser?.sha256 || '')
    || Object.keys(lock.packages || {}).join(',') !== 'browser') {
    throw new Error('Der Browser-Lock beschreibt nicht ausschließlich das isolierte brstunden-Bundle.');
}

const license = readFileSync(resolve(bundleRoot, 'LICENSE'), 'utf8');
if (!license.includes('SPDX-License-Identifier: AGPL-3.0-or-later')
    || !license.includes('GNU Affero General Public License')) {
    throw new Error('Die gebündelten Lizenzmetadaten fehlen.');
}

const indexTemplate = readFileSync(resolve(appRoot, 'templates/index.php'), 'utf8');
for (const asset of ['api-client', 'model', 'repository', 'ui']) {
    if (!indexTemplate.includes(`script('brstunden', 'shared/${asset}')`)) {
        throw new Error(`Das Template lädt shared/${asset} nicht app-lokal.`);
    }
}

const loadOrder = new Map([
    ['shared/api-client', 'modules/api'],
    ['shared/model', 'models/hour-entry'],
    ['shared/repository', 'repositories/hour-repository'],
    ['shared/ui', 'modules/format'],
]);
for (const [sharedAsset, consumerAsset] of loadOrder) {
    const sharedPosition = indexTemplate.indexOf(sharedAsset);
    const consumerPosition = indexTemplate.indexOf(consumerAsset);
    if (sharedPosition < 0 || consumerPosition < 0 || sharedPosition > consumerPosition) {
        throw new Error(`${sharedAsset} wird nicht vor ${consumerAsset} geladen.`);
    }
}

const forbiddenReferences = [
    'window.LocalBase.api',
    'window.LocalBase.models',
    'window.LocalBase.repositories',
    'window.LocalBase.ui',
    "script('localbase', 'api/",
    "script('localbase', 'models/",
    "script('localbase', 'repositories/",
    "script('localbase', 'ui/",
    '/../flz_shared/',
    '/../../flz_shared/',
];
for (const relativeRoot of ['js', 'templates']) {
    for (const file of sourceFiles(resolve(appRoot, relativeRoot))) {
        const source = readFileSync(file, 'utf8');
        for (const forbidden of forbiddenReferences) {
            if (source.includes(forbidden)) {
                throw new Error(`${file.slice(appRoot.length + 1)} enthält weiterhin ${forbidden}.`);
            }
        }
    }
}

const requests = [];
const noticeElement = { className: '', hidden: true, textContent: '' };
let response = {
    ok: true,
    status: 200,
    body: JSON.stringify({ year: 2026, rows: [] }),
};
const window = {
    fetch: async (url, options) => {
        requests.push({ url, options });
        return {
            ok: response.ok,
            status: response.status,
            text: async () => response.body,
        };
    },
};
window.window = window;
window.OC = {
    generateUrl: path => `/index.php${path}`,
    requestToken: 'brstunden-token',
};

const context = vm.createContext({
    document: { getElementById: id => id === 'brs-notice' ? noticeElement : null },
    encodeURIComponent,
    Error,
    JSON,
    Number,
    Object,
    OC: window.OC,
    String,
    window,
});

for (const relative of [
    'js/shared/api-client.js',
    'js/shared/model.js',
    'js/shared/repository.js',
    'js/shared/ui.js',
    'js/modules/api.js',
    'js/modules/format.js',
    'js/models/hour-entry.js',
    'js/repositories/hour-repository.js',
]) {
    const filename = resolve(appRoot, relative);
    vm.runInContext(readFileSync(filename, 'utf8'), context, { filename });
}

const shared = window.OCA?.BrStunden?.Shared;
if (!shared?.api?.ApiClient
    || !shared?.models?.Model
    || !shared?.repositories?.Repository
    || !shared?.ui?.Notice) {
    throw new Error('Das app-lokale Browser-Bundle stellt seine Primitives nicht vollständig isoliert bereit.');
}
if ('LocalBase' in window) {
    throw new Error('Das app-lokale Browser-Bundle erzeugt weiterhin window.LocalBase.');
}

const repository = new window.BRStunden.repositories.HourRepository(window.BRStunden.api);
await repository.yearOverview(2026);
await repository.saveEntry({ year: 2026, month: 11, hours: '1.5' });
if (requests.length !== 2
    || requests[0].url !== '/index.php/apps/brstunden/api/years/2026'
    || requests[0].options.headers.requesttoken !== 'brstunden-token'
    || requests[1].options.method !== 'POST'
    || requests[1].options.body !== '{"year":2026,"month":11,"hours":"1.5"}') {
    throw new Error('ApiClient und HourRepository bewahren URL-, CSRF- und Schreibvertrag nicht gemeinsam.');
}

const entry = window.BRStunden.models.HourEntry.get({
    id: 7,
    user_id: 'person-a',
    entry_year: 2026,
    entry_month: 11,
    brMinutes: 90,
});
if (!(entry instanceof window.BRStunden.models.HourEntry)
    || entry.toArray().userId !== 'person-a'
    || entry.to_array().brHours !== 1.5) {
    throw new Error('HourEntry bewahrt Hydration und Serialisierung mit dem gebündelten Model nicht.');
}

if (window.BRStunden.format.esc('<Stunden & mehr>') !== '&lt;Stunden &amp; mehr&gt;') {
    throw new Error('Das app-lokale UI-Primitive bewahrt den Escaping-Vertrag nicht.');
}

const notice = new shared.ui.Notice('brs-notice', {
    baseClass: 'brs-notice',
    typeClassPrefix: 'brs-notice-',
});
notice.success('Bereit');
if (noticeElement.textContent !== 'Bereit'
    || noticeElement.hidden
    || noticeElement.className !== 'brs-notice brs-notice-success') {
    throw new Error('Die gebündelte Notice bewahrt ihren sichtbaren Erfolgszustand nicht.');
}

response = { ok: false, status: 403, body: '{"message":" Fremde Stunden sind gesperrt. "}' };
await assert.rejects(repository.saveEntry({ year: 2026, month: 11, hours: '2' }), error => {
    assert.equal(error.status, 403);
    assert.equal(error.data.message, ' Fremde Stunden sind gesperrt. ');
    notice.error(error);
    assert.equal(noticeElement.textContent, 'Fremde Stunden sind gesperrt.');
    assert.equal(noticeElement.hidden, false);
    assert.equal(noticeElement.className, 'brs-notice brs-notice-error');
    return true;
});

// A non-JSON server error must remain diagnosable without rendering its HTML.
response = { ok: false, status: 502, body: '<strong>Gateway unavailable</strong>' };
await assert.rejects(repository.yearOverview(2026), error => {
    assert.equal(error.message, 'HTTP 502');
    assert.equal(error.data.raw, response.body);
    notice.error(error);
    assert.equal(noticeElement.textContent, 'HTTP 502');
    return true;
});
notice.clear();
assert.equal(noticeElement.textContent, '');
assert.equal(noticeElement.hidden, true);
assert.equal(noticeElement.className, 'brs-notice');

console.log('BRStunden isolated Category-A browser consumer smoke passed.');

function sourceFiles(root) {
    const files = [];
    for (const entry of readdirSync(root, { withFileTypes: true })) {
        const path = resolve(root, entry.name);
        if (entry.isDirectory()) {
            files.push(...sourceFiles(path));
        } else if (entry.isFile()) {
            files.push(path);
        }
    }
    return files;
}
