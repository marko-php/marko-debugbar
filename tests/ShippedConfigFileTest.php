<?php

declare(strict_types=1);

use Marko\Config\Exceptions\ConfigException;

const DEBUGBAR_CONFIG_FILE = __DIR__ . '/../config/debugbar.php';
const DEBUGBAR_CONFIG_VARIABLES = [
    'DEBUGBAR_ENABLED',
    'APP_DEBUG',
    'DEBUGBAR_STORAGE_MAX_FILES',
    'DEBUGBAR_ALLOW_PRODUCTION',
];

beforeEach(function (): void {
    $this->originalEnv = [];

    foreach (DEBUGBAR_CONFIG_VARIABLES as $variable) {
        $this->originalEnv[$variable] = $_ENV[$variable] ?? null;
        unset($_ENV[$variable]);
    }
});

afterEach(function (): void {
    foreach ($this->originalEnv as $variable => $value) {
        if ($value === null) {
            unset($_ENV[$variable]);
        } else {
            $_ENV[$variable] = $value;
        }
    }
});

it('keeps the debugbar disabled by default', function (): void {
    $config = require DEBUGBAR_CONFIG_FILE;

    expect($config['enabled'])->toBeFalse()
        ->and($config['inject'])->toBeTrue()
        ->and($config['storage']['max_files'])->toBe(100)
        ->and($config['options']['database']['slow_threshold_ms'])->toBe(100);
});

it('reads DEBUGBAR_ENABLED=off as false', function (): void {
    $_ENV['DEBUGBAR_ENABLED'] = 'off';
    $_ENV['APP_DEBUG'] = 'true';

    expect((require DEBUGBAR_CONFIG_FILE)['enabled'])->toBeFalse();
});

it('falls back to APP_DEBUG for debugbar.enabled', function (): void {
    $_ENV['APP_DEBUG'] = 'yes';

    expect((require DEBUGBAR_CONFIG_FILE)['enabled'])->toBeTrue();
});

it('rejects an unrecognised DEBUGBAR_ENABLED value', function (): void {
    $_ENV['DEBUGBAR_ENABLED'] = 'maybe';

    expect(fn (): array => require DEBUGBAR_CONFIG_FILE)
        ->toThrow(ConfigException::class, 'Environment variable "DEBUGBAR_ENABLED" must be a boolean');
});

it('rejects a DEBUGBAR_STORAGE_MAX_FILES that is not an integer', function (): void {
    $_ENV['DEBUGBAR_STORAGE_MAX_FILES'] = 'lots';

    expect(fn (): array => require DEBUGBAR_CONFIG_FILE)
        ->toThrow(ConfigException::class, 'Environment variable "DEBUGBAR_STORAGE_MAX_FILES" must be an integer');
});

it('keeps production access off by default', function (): void {
    $config = require DEBUGBAR_CONFIG_FILE;

    expect($config['allow_production'])->toBeFalse()
        ->and($config['route']['trusted_proxies'])->toBe([]);
});

it('reads DEBUGBAR_ALLOW_PRODUCTION for debugbar.allow_production', function (): void {
    $_ENV['DEBUGBAR_ALLOW_PRODUCTION'] = 'true';

    expect((require DEBUGBAR_CONFIG_FILE)['allow_production'])->toBeTrue();
});
