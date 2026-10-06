<?php

declare(strict_types=1);

use Marko\Debugbar\Support\Redactor;

it('treats cookie headers as sensitive', function (): void {
    $redactor = new Redactor();

    expect($redactor->isSensitiveKey('Cookie'))->toBeTrue()
        ->and($redactor->isSensitiveKey('Set-Cookie'))->toBeTrue()
        ->and($redactor->isSensitiveKey('Accept'))->toBeFalse();
});

it('masks every cookie value but keeps the cookie names', function (): void {
    expect((new Redactor())->header('Cookie', 'marko_session=abc123; remember_me=xyz; theme=dark'))
        ->toBe('marko_session=[masked]; remember_me=[masked]; theme=[masked]');
});

it('masks the Set-Cookie value but keeps its attributes', function (): void {
    expect((new Redactor())->headerLine('Set-Cookie: marko_session=abc123; Path=/; HttpOnly'))
        ->toBe('Set-Cookie: marko_session=[masked]; Path=/; HttpOnly');
});

it('masks other sensitive header lines entirely and leaves the rest alone', function (): void {
    $redactor = new Redactor();

    expect($redactor->headerLine('X-Api-Key: live-key'))->toBe('X-Api-Key: [masked]')
        ->and($redactor->headerLine('Content-Type: text/html'))->toBe('Content-Type: text/html');
});

it('recursively masks nested sensitive keys', function (): void {
    expect((new Redactor())->redact(['user' => ['id' => 1, 'password' => 'hunter2'], 'api_token' => 'abc']))
        ->toBe(['user' => ['id' => 1, 'password' => '[masked]'], 'api_token' => '[masked]']);
});

it('masks sensitive query-string values inside a uri', function (): void {
    $redactor = new Redactor();

    expect($redactor->uri('/reset?token=abc123&page=2#top'))->toBe('/reset?token=[masked]&page=2#top')
        ->and($redactor->uri('/dashboard?tab=debug'))->toBe('/dashboard?tab=debug')
        ->and($redactor->uri('/plain'))->toBe('/plain');
});
