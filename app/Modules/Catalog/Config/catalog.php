<?php

declare(strict_types=1);

return [

    'pagination' => [
        // a module config is not in config/, so env() here would return null once config is cached
        'per_page' => 20,
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
