<?php

namespace Happytodev\BlogrGdpr\Tests\Feature;

use Happytodev\Blogr\Services\ExtensionRegistry;
use Happytodev\BlogrGdpr\BlogrGdprPlugin;

it('registers the extension with the ExtensionRegistry', function () {
    $registry = app(ExtensionRegistry::class);
    $extensions = $registry->getAll();

    $found = collect($extensions)->first(fn ($ext) => $ext instanceof BlogrGdprPlugin);

    expect($found)->not->toBeNull();
});

it('can publish config', function () {
    $this->artisan('vendor:publish', ['--tag' => 'blogr-gdpr-config'])
        ->assertExitCode(0);
});

it('can publish views', function () {
    $this->artisan('vendor:publish', ['--tag' => 'blogr-gdpr-views'])
        ->assertExitCode(0);
});

it('can publish translations', function () {
    $this->artisan('vendor:publish', ['--tag' => 'blogr-gdpr-lang'])
        ->assertExitCode(0);
});
