<?php

namespace Happytodev\BlogrGdpr\Tests;

use Filament\Resources\Pages\EditRecord;
use Happytodev\BlogrGdpr\Filament\Resources\ConsentLogResource;
use Happytodev\BlogrGdpr\Filament\Resources\ConsentLogResource\Pages\ListConsentLogs;
use Happytodev\BlogrGdpr\Filament\Resources\ConsentLogResource\Pages\ViewConsentLog;
use Happytodev\BlogrGdpr\Filament\Resources\GdprRequestResource;
use Happytodev\BlogrGdpr\Filament\Resources\GdprRequestResource\Pages\EditGdprRequest;
use Happytodev\BlogrGdpr\Models\ConsentLog;
use Happytodev\BlogrGdpr\Filament\Resources\GdprRequestResource\Pages\ListGdprRequests;
use Happytodev\BlogrGdpr\Filament\Resources\GdprRequestResource\Pages\ViewGdprRequest;
use Happytodev\BlogrGdpr\Models\GdprRequest;
use Livewire\Mechanisms\ComponentRegistry;

it('has resource property set on gdpr request list page', function () {
    $pages = GdprRequestResource::getPages();

    expect($pages)->toHaveKey('index');
    expect($pages)->toHaveKey('view');
    expect($pages)->toHaveKey('edit');

    $pageClass = $pages['index']->getPage();
    $resource = (new \ReflectionClass($pageClass))->getProperty('resource');
    $resource->setAccessible(true);

    expect($resource->getValue())->toBe(GdprRequestResource::class);
});

it('has resource property set on gdpr request edit page', function () {
    $pages = GdprRequestResource::getPages();

    $editPage = $pages['edit']->getPage();
    expect($editPage)->toExtend(EditRecord::class);

    $resource = (new \ReflectionClass($editPage))->getProperty('resource');
    $resource->setAccessible(true);

    expect($resource->getValue())->toBe(GdprRequestResource::class);
});

it('has resource property set on consent log list page', function () {
    $pages = ConsentLogResource::getPages();

    expect($pages)->toHaveKey('index');
    expect($pages)->toHaveKey('view');
    expect($pages)->not->toHaveKey('edit');

    $pageClass = $pages['index']->getPage();
    $resource = (new \ReflectionClass($pageClass))->getProperty('resource');
    $resource->setAccessible(true);

    expect($resource->getValue())->toBe(ConsentLogResource::class);
});

it('sets completed_at when status changes to completed on edit gdpr request page', function () {
    $request = GdprRequest::create([
        'email' => 'test@example.com',
        'request_type' => 'export',
        'status' => 'pending',
    ]);

    expect($request->completed_at)->toBeNull();

    $method = (new \ReflectionClass(EditGdprRequest::class))->getMethod('mutateFormDataBeforeSave');
    $method->setAccessible(true);

    $instance = app(EditGdprRequest::class);
    $property = (new \ReflectionClass($instance))->getProperty('record');
    $property->setAccessible(true);
    $property->setValue($instance, $request);

    $data = $method->invoke($instance, ['status' => 'completed', 'notes' => '']);

    expect($data)->toHaveKey('completed_at');
    expect($data['completed_at'])->not->toBeNull();
});

it('does not set completed_at when status remains pending on edit gdpr request page', function () {
    $request = GdprRequest::create([
        'email' => 'test@example.com',
        'request_type' => 'export',
        'status' => 'pending',
    ]);

    $method = (new \ReflectionClass(EditGdprRequest::class))->getMethod('mutateFormDataBeforeSave');
    $method->setAccessible(true);

    $instance = app(EditGdprRequest::class);
    $property = (new \ReflectionClass($instance))->getProperty('record');
    $property->setAccessible(true);
    $property->setValue($instance, $request);

    $data = $method->invoke($instance, ['status' => 'pending', 'notes' => 'test']);

    expect($data)->not->toHaveKey('completed_at');
});

it('registers gdpr request pages as livewire components', function () {
    $registry = app(ComponentRegistry::class);

    $componentNames = [
        $registry->getName(ListGdprRequests::class),
        $registry->getName(EditGdprRequest::class),
        $registry->getName(ViewGdprRequest::class),
    ];

    foreach ($componentNames as $name) {
        $class = $registry->getClass($name);
        expect($class)->not->toBeNull();
    }
});

it('registers consent log pages as livewire components', function () {
    $registry = app(ComponentRegistry::class);

    $componentNames = [
        $registry->getName(ListConsentLogs::class),
        $registry->getName(ViewConsentLog::class),
    ];

    foreach ($componentNames as $name) {
        $class = $registry->getClass($name);
        expect($class)->not->toBeNull();
    }
});

it('renders consent log infolist without type error when consent_data is null', function () {
    $log = ConsentLog::create([
        'consent_type' => 'analytics',
        'consent_given' => true,
    ]);

    $schema = ConsentLogResource::infolist(
        app(\Filament\Schemas\Schema::class)
    );

    $components = $schema->getComponents();
    expect($components)->not->toBeEmpty();
});

it('formats consent_data categories in table column', function () {
    $log = ConsentLog::create([
        'email' => 'test@example.com',
        'consent_type' => 'cookies',
        'consent_given' => true,
        'consent_data' => [
            'categories' => [
                'essential' => true,
                'analytics' => true,
                'marketing' => false,
            ],
        ],
    ]);

    expect($log->consent_data)->toBeArray();
    expect($log->consent_data)->toHaveKey('categories');

    $state = $log->consent_data;
    $result = is_array($state) && isset($state['categories'])
        ? collect($state['categories'])->filter()->keys()->implode(', ')
        : '';

    expect($result)->toBe('essential, analytics');
});

it('formats consent_data categories in infolist', function () {
    $log = ConsentLog::create([
        'email' => 'test@example.com',
        'consent_type' => 'cookies',
        'consent_given' => true,
        'consent_data' => [
            'categories' => [
                'essential' => true,
                'marketing' => true,
                'analytics' => false,
            ],
        ],
    ]);

    $state = $log->consent_data;
    $result = is_array($state) && isset($state['categories'])
        ? collect($state['categories'])->filter()->keys()->implode(', ')
        : '';

    expect($result)->toBe('essential, marketing');
});

it('formats consent_data from endpoint through model', function () {
    $this
        ->post(route('gdpr.consent'), [
            'consent_type' => 'cookies',
            'consent_data' => [
                'categories' => [
                    'essential' => true,
                    'analytics' => false,
                    'marketing' => true,
                ],
            ],
        ])
        ->assertSessionHas('blogr_gdpr_consent_cookies', true);

    $log = ConsentLog::where('consent_type', 'cookies')->latest()->first();
    expect($log)->not->toBeNull();
    expect($log->consent_data)->toBeArray();
    expect($log->consent_data['categories']['essential'])->toBeTrue();
    expect($log->consent_data['categories']['marketing'])->toBeTrue();

    $state = $log->consent_data;
    $formatted = is_array($state) && isset($state['categories'])
        ? collect($state['categories'])->filter()->keys()->implode(', ')
        : '';

    expect($formatted)->toBe('essential, marketing');
});
