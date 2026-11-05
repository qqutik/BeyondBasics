<?php

declare(strict_types=1);

use function Pest\Laravel\get;

it('returns a successful response for the home page', function () {
    get('/')->assertOk();
});
