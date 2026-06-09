<?php

namespace Happytodev\BlogrGdpr\Tests;

use Happytodev\BlogrGdpr\Filament\Resources\ConsentLogResource;
use Happytodev\BlogrGdpr\Filament\Resources\GdprRequestResource;

it('has resource property set on gdpr request list page', function () {
    $pages = GdprRequestResource::getPages();

    expect($pages)->toHaveKey('index');
    expect($pages)->toHaveKey('view');

    $pageClass = $pages['index']->getPage();
    $resource = (new \ReflectionClass($pageClass))->getProperty('resource');
    $resource->setAccessible(true);

    expect($resource->getValue())->toBe(GdprRequestResource::class);
});

it('has resource property set on consent log list page', function () {
    $pages = ConsentLogResource::getPages();

    expect($pages)->toHaveKey('index');
    expect($pages)->toHaveKey('view');

    $pageClass = $pages['index']->getPage();
    $resource = (new \ReflectionClass($pageClass))->getProperty('resource');
    $resource->setAccessible(true);

    expect($resource->getValue())->toBe(ConsentLogResource::class);
});
