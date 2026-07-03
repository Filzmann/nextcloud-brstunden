<?php

return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'api#state', 'url' => '/api/state', 'verb' => 'GET'],
        ['name' => 'api#year', 'url' => '/api/years/{year}', 'verb' => 'GET'],
        ['name' => 'api#saveEntry', 'url' => '/api/entries', 'verb' => 'POST'],
    ],
];
