<?php

declare(strict_types=1);

return [

    'pagination' => [
        'per_page' => (int) env('CATALOG_PER_PAGE', 20),
        'max_per_page' => 100,
    ],

    // public catalog list (GET /api/courses), seconds; flushed on approve / archive / edit / delete anyway
    'cache' => [
        'ttl' => 300,
    ],

    'categories' => [
        'max_depth' => 3,
    ],

];
