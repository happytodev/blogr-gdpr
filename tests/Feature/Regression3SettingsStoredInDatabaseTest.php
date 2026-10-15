<?php

use Happytodev\BlogrGdpr\Filament\Pages\GdprSettings;
use Happytodev\BlogrGdpr\Services\SettingsRepository;
use Illuminate\Support\Facades\DB;

/**
 * Issue #3 — the admin settings must live in the database, not in the config
 * file (replaced at every deployment by the repository's copy).
 */
beforeEach(function () {
    app(SettingsRepository::class)->flush();
});

it('regression_3_stores_an_admin_save_in_the_database_instead_of_the_config_file', function () {
    $configFile = config_path('blogr-gdpr.php');
    $before = file_exists($configFile) ? md5_file($configFile) : null;

    $page = new GdprSettings;
    $page->mount();
    $page->dpo_email = 'privacy@example.org';
    $page->cookie_consent_enabled = false;
    $page->save();

    // One row per setting, under its dotted configuration path.
    expect(DB::table(SettingsRepository::TABLE)->where('key', 'dpo.email')->value('value'))
        ->toBe('"privacy@example.org"')
        ->and(DB::table(SettingsRepository::TABLE)->where('key', 'cookie_consent.enabled')->value('value'))
        ->toBe('false');

    // And the values apply at once, so the page reflects the save.
    expect(config('blogr-gdpr.dpo.email'))->toBe('privacy@example.org')
        ->and(config('blogr-gdpr.cookie_consent.enabled'))->toBeFalse();

    // The configuration file is no longer rewritten.
    expect(file_exists($configFile) ? md5_file($configFile) : null)->toBe($before);
});

it('regression_3_applies_the_stored_settings_on_top_of_the_configuration', function () {
    config(['blogr-gdpr.dpo.name' => 'From the file']);
    config(['blogr-gdpr.consent_log.retention_days' => 365]);

    DB::table(SettingsRepository::TABLE)->insert([
        'key' => 'dpo.name',
        'value' => '"From the database"',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $repository = app(SettingsRepository::class);
    $repository->flush();
    $repository->applyToConfig();

    expect(config('blogr-gdpr.dpo.name'))->toBe('From the database')      // la base gagne
        ->and(config('blogr-gdpr.consent_log.retention_days'))->toBe(365); // le fichier reste
});

it('regression_3_never_stores_a_null_value', function () {
    // A page saved without being mounted carries null values.
    (new GdprSettings)->save();

    $rows = DB::table(SettingsRepository::TABLE)->pluck('value', 'key');

    expect($rows)->not->toContain('null');
});

it('regression_3_does_not_bake_the_overrides_into_the_compiled_configuration', function () {
    DB::table(SettingsRepository::TABLE)->insert([
        'key' => 'consent_log.retention_days',
        'value' => '30',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    config(['blogr-gdpr.consent_log.retention_days' => 365]);

    // `php artisan config:cache` boots the application before freezing the
    // configuration: applying the overrides there would bake them into the
    // compiled file, which would then take precedence over the table.
    $_SERVER['argv'] = ['artisan', 'config:cache'];

    try {
        app(SettingsRepository::class)->applyToConfig();
    } finally {
        unset($_SERVER['argv']);
    }

    expect(config('blogr-gdpr.consent_log.retention_days'))->toBe(365);
});

it('regression_3_forgets_the_stored_settings_on_reset', function () {
    $repository = app(SettingsRepository::class);
    $repository->put(['dpo.email' => 'privacy@example.org']);
    $repository->put(['consent_log.retention_days' => 30]);

    expect($repository->count())->toBe(2)
        ->and($repository->forgetAll())->toBe(2)
        ->and(DB::table(SettingsRepository::TABLE)->count())->toBe(0)
        ->and(app(SettingsRepository::class)->all())->toBe([]);
});

it('regression_3_falls_back_to_the_config_file_while_the_table_is_missing', function () {
    app()->instance(SettingsRepository::class, new class extends SettingsRepository
    {
        public function hasTable(): bool
        {
            return false;
        }
    });

    $page = new GdprSettings;
    $page->mount();
    $page->dpo_email = 'privacy@example.org';

    $page->save();

    // In the test environment, the fallback applies the values in memory.
    expect(config('blogr-gdpr.dpo.email'))->toBe('privacy@example.org')
        ->and(DB::table(SettingsRepository::TABLE)->count())->toBe(0);
});
