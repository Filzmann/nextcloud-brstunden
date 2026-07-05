import { dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { runJavaScriptSuite } from '../../localbase/tests/Support/js-runner.mjs';

const root = dirname(dirname(fileURLToPath(import.meta.url)));

runJavaScriptSuite({
    root,
    testFiles: [
        'tests/js/model-smoke.js',
        'tests/js/hour-repository-smoke.js',
        'tests/js/main-workflow-smoke.js',
        'tests/js/overview-smoke.js',
    ],
    successMessage: 'BRStunden JavaScript tests passed',
});
