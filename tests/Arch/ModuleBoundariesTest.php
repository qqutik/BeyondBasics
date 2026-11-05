<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Modular monolith boundaries
|--------------------------------------------------------------------------
|
| Every module lives in app/Modules/<Name>. Other modules may only use its
| public surface: Contracts (interfaces), DTO and Events. Everything else
| (Models, Services, Repositories, Http, Listeners, Database, Providers...)
| is internal to the module and must not be referenced from outside.
|
*/

const PUBLIC_MODULE_LAYERS = ['Contracts', 'DTO', 'Events'];

/**
 * @return list<string>
 */
function moduleNames(): array
{
    $dirs = glob(dirname(__DIR__, 2).'/app/Modules/*', GLOB_ONLYDIR);

    return array_map('basename', $dirs === false ? [] : $dirs);
}

/**
 * @return list<string>
 */
function internalNamespacesOf(string $module): array
{
    $dirs = glob(dirname(__DIR__, 2)."/app/Modules/{$module}/*", GLOB_ONLYDIR);
    $layers = array_map('basename', $dirs === false ? [] : $dirs);

    return array_values(array_map(
        static fn (string $layer): string => "App\\Modules\\{$module}\\{$layer}",
        array_diff($layers, PUBLIC_MODULE_LAYERS),
    ));
}

foreach (moduleNames() as $module) {
    foreach (moduleNames() as $other) {
        if ($module === $other || internalNamespacesOf($other) === []) {
            continue;
        }

        arch("{$module} module does not use internals of {$other} module")
            ->expect("App\\Modules\\{$module}")
            ->not->toUse(internalNamespacesOf($other));
    }

    arch("{$module} module contracts are interfaces")
        ->expect("App\\Modules\\{$module}\\Contracts")
        ->toBeInterfaces();
}

arch('code outside modules does not use module internals')
    ->expect(['App\\Http', 'App\\Providers', 'App\\Console'])
    ->not->toUse(array_merge(...array_map(internalNamespacesOf(...), moduleNames())));

arch('application code uses strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch()->preset()->php();
arch()->preset()->security();
