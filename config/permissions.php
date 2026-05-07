<?php

return [
    'tree' => [
        'users' => ['create', 'update', 'delete'],
        'tenants' => [
            'settings' => ['view', 'update', 'delete'],
            'users' => ['view', 'attach', 'detach', 'create'],
        ],
    ],
];
