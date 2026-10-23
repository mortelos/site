<?php

declare(strict_types=1);

return [
    'app/Models/User.php' => [
        'model-declaration' => [
            'count' => 1,
            'reason' => 'The public site uses one central database and the default users table, with no tenant switching. Its locked dependencies do not include stancl/tenancy, so CentralConnection cannot be used without adding a dependency.',
            'issue' => 'https://github.com/mortelos/site/issues/11',
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
