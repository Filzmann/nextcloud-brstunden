<?php

return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'api#state', 'url' => '/api/state', 'verb' => 'GET'],
        ['name' => 'api#year', 'url' => '/api/years/{year}', 'verb' => 'GET'],
        ['name' => 'api#saveEntry', 'url' => '/api/entries', 'verb' => 'POST'],
        ['name' => 'api#deleteEntry', 'url' => '/api/entries/{year}/{month}', 'verb' => 'DELETE'],
        ['name' => 'api#payrollPdf', 'url' => '/api/entries/{year}/{month}/payroll.pdf', 'verb' => 'GET'],
        ['name' => 'api#reminderPreview', 'url' => '/api/reminders/preview', 'verb' => 'GET'],
        ['name' => 'capabilitySettings#update', 'url' => '/api/capability-settings', 'verb' => 'PUT'],
        ['name' => 'documentSettings#saveDraft', 'url' => '/api/document-settings', 'verb' => 'PUT'],
        ['name' => 'documentSettings#approve', 'url' => '/api/document-settings/approve', 'verb' => 'POST'],
    ],
];
