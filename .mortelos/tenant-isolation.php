<?php

declare(strict_types=1);

return [
    'app/Models/User.php' => [
        'model-declaration' => [
            'count' => 1,
            'reason' => 'Permanent: The public site uses one database and the default users table, has no tenants, and has no stancl/tenancy in its locked dependencies. Revisit when the site adopts tenancy.',
            'issue' => 'https://github.com/mortelos/dev-tools/issues/20',
        ],
    ],
    'database/factories/UserFactory.php' => [
        'static-state' => [
            'count' => 1,
            'reason' => 'Permanent: Laravel standard test factory caches only the hash of a fixed test password; it contains no tenant or user state.',
            'issue' => 'https://github.com/mortelos/dev-tools/issues/20',
        ],
    ],
];
