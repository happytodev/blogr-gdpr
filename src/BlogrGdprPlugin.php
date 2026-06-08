<?php

namespace Happytodev\BlogrGdpr;

use Filament\Contracts\Plugin as FilamentPlugin;
use Filament\Panel;
use Happytodev\Blogr\Contracts\BlogrExtension;
use Happytodev\BlogrGdpr\Filament\Pages\GdprSettings;

class BlogrGdprPlugin implements BlogrExtension, FilamentPlugin
{
    public function getId(): string
    {
        return 'blogr-gdpr';
    }

    public function getName(): string
    {
        return 'GDPR Compliance';
    }

    public function getDescription(): string
    {
        return 'GDPR compliance: cookie consent, privacy policy, data export & erasure requests.';
    }

    public function getVersion(): string
    {
        return '1.1.2';
    }

    public function getAuthor(): string
    {
        return 'HappyToDev';
    }

    public function getHomepage(): ?string
    {
        return 'https://github.com/happytodev/blogr-gdpr';
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            GdprSettings::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
