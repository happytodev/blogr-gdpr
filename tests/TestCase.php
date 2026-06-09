<?php

namespace Happytodev\BlogrGdpr\Tests;

use Filament\FilamentServiceProvider;
use Happytodev\Blogr\BlogrServiceProvider;
use Happytodev\BlogrGdpr\BlogrGdprServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Permission\PermissionServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createViteManifest();
        $this->runGdprMigrations();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Happytodev\\BlogrGdpr\\Tests\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function runGdprMigrations(): void
    {
        Schema::create('blogr_gdpr_consent_logs', function ($table) {
            $table->id();
            $table->string('email')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('consent_type');
            $table->boolean('consent_given');
            $table->json('consent_data')->nullable();
            $table->timestamps();
        });

        Schema::create('blogr_gdpr_requests', function ($table) {
            $table->id();
            $table->string('email');
            $table->string('request_type');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    protected function createViteManifest(): void
    {
        $manifestPath = __DIR__.'/../vendor/orchestra/testbench-core/laravel/public/build/manifest.json';

        if (! is_dir(dirname($manifestPath))) {
            mkdir(dirname($manifestPath), 0755, true);
        }

        if (! file_exists($manifestPath)) {
            file_put_contents($manifestPath, json_encode([
                'resources/css/app.css' => [
                    'file' => 'assets/app.css',
                    'src' => 'resources/css/app.css',
                ],
                'resources/js/app.js' => [
                    'file' => 'assets/app.js',
                    'src' => 'resources/js/app.js',
                    'isEntry' => true,
                ],
            ]));
        }
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            FilamentServiceProvider::class,
            PermissionServiceProvider::class,
            BlogrServiceProvider::class,
            BlogrGdprServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['config']->set('blogr.locales', [
            'enabled' => false,
            'default' => 'en',
            'available' => ['en'],
        ]);

        $app['config']->set('blogr-gdpr.enabled', true);
        $app['config']->set('blogr-gdpr.privacy_policy.auto_create', false);
        $app['config']->set('blogr-gdpr.dpo.email', 'dpo@example.com');
        $app['config']->set('mail.default', 'array');
        $app['config']->set('blogr.cms.enabled', false);
        $app['config']->set('blogr.route.frontend.enabled', false);
    }
}
