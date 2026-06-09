<?php

namespace Happytodev\BlogrGdpr\Tests\Feature;

use Happytodev\BlogrGdpr\Models\ConsentLog;
use Happytodev\BlogrGdpr\Models\GdprRequest;

it('uses correct table name from migration', function () {
    expect((new ConsentLog)->getTable())->toBe('blogr_gdpr_consent_logs');
    expect((new GdprRequest)->getTable())->toBe('blogr_gdpr_requests');
});

it('can create a consent log entry', function () {
    $log = ConsentLog::create([
        'email' => 'test@example.com',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test/1.0',
        'consent_type' => 'cookies',
        'consent_given' => true,
        'consent_data' => ['key' => 'value'],
    ]);

    expect($log->exists)->toBeTrue();
    expect($log->email)->toBe('test@example.com');
    expect($log->consent_type)->toBe('cookies');
    expect($log->consent_given)->toBeTrue();
    expect($log->consent_data)->toBe(['key' => 'value']);
});

it('can create a consent log entry without optional fields', function () {
    $log = ConsentLog::create([
        'consent_type' => 'analytics',
        'consent_given' => false,
    ]);

    expect($log->exists)->toBeTrue();
    expect($log->email)->toBeNull();
    expect($log->ip_address)->toBeNull();
});

it('can create a gdpr request', function () {
    $request = GdprRequest::create([
        'email' => 'user@example.com',
        'request_type' => 'export',
    ]);

    expect($request->exists)->toBeTrue();
    expect($request->email)->toBe('user@example.com');
    expect($request->request_type)->toBe('export');
    expect($request->status)->toBe('pending');
});

it('can mark a gdpr request as completed', function () {
    $request = GdprRequest::create([
        'email' => 'user@example.com',
        'request_type' => 'erasure',
    ]);

    $request->update([
        'status' => 'completed',
        'completed_at' => now(),
    ]);

    expect($request->status)->toBe('completed');
    expect($request->completed_at)->not->toBeNull();
});
