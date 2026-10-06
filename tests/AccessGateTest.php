<?php

declare(strict_types=1);

use Marko\Config\ConfigRepository;
use Marko\Core\Environment\AppEnvironment;
use Marko\Debugbar\Access\AccessGate;

function makeAccessGate(
    array $debugbar = [],
    string $environment = 'local',
): AccessGate {
    $config = new ConfigRepository([
        'debugbar' => array_replace_recursive([
            'enabled' => true,
            'allow_production' => false,
            'route' => [
                'open' => false,
                'allowed_ips' => ['127.0.0.1', '::1'],
                'trusted_proxies' => [],
            ],
        ], $debugbar),
    ]);

    return new AccessGate($config, new AppEnvironment(['APP_ENV' => $environment]));
}

describe('production hard-stop', function (): void {
    it('refuses to enable in production even when debugbar.enabled is true', function (): void {
        expect(makeAccessGate(environment: 'production')->enabled())->toBeFalse()
            ->and(makeAccessGate(environment: 'prod')->enabled())->toBeFalse();
    });

    it('treats an unset environment as production', function (): void {
        $config = new ConfigRepository(['debugbar' => ['enabled' => true]]);

        expect((new AccessGate($config, new AppEnvironment([])))->enabled())->toBeFalse();
    });

    it('enables in production only when allow_production is explicitly true', function (): void {
        expect(makeAccessGate(['allow_production' => true], 'production')->enabled())->toBeTrue();
    });

    it('enables outside production when debugbar.enabled is true', function (): void {
        expect(makeAccessGate(environment: 'local')->enabled())->toBeTrue()
            ->and(makeAccessGate(environment: 'testing')->enabled())->toBeTrue();
    });

    it('stays disabled when debugbar.enabled is false, even with allow_production', function (): void {
        expect(makeAccessGate(['enabled' => false, 'allow_production' => true], 'local')->enabled())->toBeFalse();
    });
});

describe('client allowlist', function (): void {
    it('allows a direct loopback client', function (): void {
        expect(makeAccessGate()->clientAllowed(['REMOTE_ADDR' => '127.0.0.1']))->toBeTrue()
            ->and(makeAccessGate()->clientAllowed(['REMOTE_ADDR' => '::1']))->toBeTrue();
    });

    it('denies when REMOTE_ADDR is missing', function (): void {
        expect(makeAccessGate()->clientAllowed([]))->toBeFalse();
    });

    it('denies when REMOTE_ADDR is not a valid IP address', function (): void {
        expect(makeAccessGate()->clientAllowed(['REMOTE_ADDR' => 'localhost']))->toBeFalse();
    });

    it('denies a client outside the allowlist', function (): void {
        expect(makeAccessGate()->clientAllowed(['REMOTE_ADDR' => '203.0.113.9']))->toBeFalse();
    });

    it('matches IPv6 addresses regardless of notation', function (): void {
        expect(makeAccessGate()->clientAllowed(['REMOTE_ADDR' => '0:0:0:0:0:0:0:1']))->toBeTrue();
    });

    it('allows any client when route.open is true', function (): void {
        expect(makeAccessGate(['route' => ['open' => true]])->clientAllowed([]))->toBeTrue();
    });
});

describe('reverse proxies', function (): void {
    it('denies a loopback peer that forwards X-Forwarded-For when no proxy is trusted', function (): void {
        $server = ['REMOTE_ADDR' => '127.0.0.1', 'HTTP_X_FORWARDED_FOR' => '203.0.113.9'];

        expect(makeAccessGate()->clientAllowed($server))->toBeFalse();
    });

    it('denies a loopback peer that forwards a Forwarded header when no proxy is trusted', function (): void {
        $server = ['REMOTE_ADDR' => '127.0.0.1', 'HTTP_FORWARDED' => 'for=203.0.113.9'];

        expect(makeAccessGate()->clientAllowed($server))->toBeFalse();
    });

    it('denies even a forwarded loopback client when the peer is not a trusted proxy', function (): void {
        $server = ['REMOTE_ADDR' => '127.0.0.1', 'HTTP_X_FORWARDED_FOR' => '127.0.0.1'];

        expect(makeAccessGate()->clientAllowed($server))->toBeFalse();
    });

    it('checks the forwarded client against the allowlist behind a trusted proxy', function (): void {
        $gate = makeAccessGate([
            'route' => [
                'allowed_ips' => ['192.0.2.10'],
                'trusted_proxies' => ['127.0.0.1'],
            ],
        ]);

        expect($gate->clientAllowed(['REMOTE_ADDR' => '127.0.0.1', 'HTTP_X_FORWARDED_FOR' => '192.0.2.10']))
            ->toBeTrue()
            ->and($gate->clientAllowed(['REMOTE_ADDR' => '127.0.0.1', 'HTTP_X_FORWARDED_FOR' => '203.0.113.9']))
            ->toBeFalse();
    });

    it('uses the right-most untrusted hop so a spoofed left-most entry cannot pass', function (): void {
        $gate = makeAccessGate(['route' => ['trusted_proxies' => ['10.0.0.2']]]);

        $server = ['REMOTE_ADDR' => '10.0.0.2', 'HTTP_X_FORWARDED_FOR' => '127.0.0.1, 203.0.113.9'];

        expect($gate->clientAllowed($server))->toBeFalse();
    });

    it('reads the client from an RFC 7239 Forwarded header behind a trusted proxy', function (): void {
        $gate = makeAccessGate(['route' => ['trusted_proxies' => ['10.0.0.2']]]);

        expect($gate->clientAllowed(['REMOTE_ADDR' => '10.0.0.2', 'HTTP_FORWARDED' => 'for="[::1]:4711";proto=http']))
            ->toBeTrue()
            ->and($gate->clientAllowed(['REMOTE_ADDR' => '10.0.0.2', 'HTTP_FORWARDED' => 'for=127.0.0.1:8080']))
            ->toBeTrue()
            ->and($gate->clientAllowed(['REMOTE_ADDR' => '10.0.0.2', 'HTTP_FORWARDED' => 'for=203.0.113.9']))
            ->toBeFalse();
    });

    it('denies when a forwarded hop is not a valid IP address', function (): void {
        $gate = makeAccessGate(['route' => ['trusted_proxies' => ['10.0.0.2']]]);

        expect($gate->clientAllowed(['REMOTE_ADDR' => '10.0.0.2', 'HTTP_FORWARDED' => 'for=unknown']))
            ->toBeFalse()
            ->and($gate->clientAllowed(['REMOTE_ADDR' => '10.0.0.2', 'HTTP_X_FORWARDED_FOR' => '']))
            ->toBeFalse();
    });
});
