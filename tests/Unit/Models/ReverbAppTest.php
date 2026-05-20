<?php

use App\Models\ReverbApp;

it('normalizes origins from various inputs', function (?string $input, ?string $expected) {
    expect(ReverbApp::normalizeOrigin($input))->toBe($expected);
})->with([
    'bare domain' => ['example.com', 'example.com'],
    'https URL' => ['https://example.com', 'example.com'],
    'http URL with path' => ['http://example.com/foo/bar', 'example.com'],
    'URL with port' => ['https://example.com:8080/path', 'example.com'],
    'URL with query' => ['https://example.com?foo=bar', 'example.com'],
    'uppercase scheme + host' => ['HTTPS://Example.COM', 'example.com'],
    'wss URL' => ['wss://example.com/socket', 'example.com'],
    'subdomain' => ['app.example.com', 'app.example.com'],
    'domain with trailing path' => ['example.com/foo', 'example.com'],
    'wildcard' => ['*', '*'],
    'whitespace' => ['  example.com  ', 'example.com'],
    'empty string' => ['', null],
    'null' => [null, null],
]);
