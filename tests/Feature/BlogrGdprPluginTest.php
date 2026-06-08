<?php

namespace Happytodev\BlogrGdpr\Tests;

use Filament\Contracts\Plugin;
use Happytodev\Blogr\Contracts\BlogrExtension;
use Happytodev\BlogrGdpr\BlogrGdprPlugin;

it('can instantiate the plugin', function () {
    $plugin = app(BlogrGdprPlugin::class);
    expect($plugin)->toBeInstanceOf(BlogrGdprPlugin::class);
});

it('returns the correct plugin id', function () {
    $plugin = app(BlogrGdprPlugin::class);
    expect($plugin->getId())->toBe('blogr-gdpr');
});

it('returns the correct plugin name', function () {
    $plugin = app(BlogrGdprPlugin::class);
    expect($plugin->getName())->toBe('GDPR Compliance');
});

it('returns a description', function () {
    $plugin = app(BlogrGdprPlugin::class);
    expect($plugin->getDescription())->toBeString()->not->toBeEmpty();
});

it('returns a version', function () {
    $plugin = app(BlogrGdprPlugin::class);
    expect($plugin->getVersion())->toMatch('/^\d+\.\d+\.\d+$/');
});

it('returns an author', function () {
    $plugin = app(BlogrGdprPlugin::class);
    expect($plugin->getAuthor())->toBe('HappyToDev');
});

it('returns a homepage url', function () {
    $plugin = app(BlogrGdprPlugin::class);
    expect($plugin->getHomepage())->toBeString()->toStartWith('https://');
});

it('has no dependencies', function () {
    $plugin = app(BlogrGdprPlugin::class);
    expect($plugin->getDependencies())->toBeEmpty();
});

it('is a filament plugin', function () {
    $plugin = app(BlogrGdprPlugin::class);
    expect($plugin)->toBeInstanceOf(Plugin::class);
});

it('is a blogr extension', function () {
    $plugin = app(BlogrGdprPlugin::class);
    expect($plugin)->toBeInstanceOf(BlogrExtension::class);
});
