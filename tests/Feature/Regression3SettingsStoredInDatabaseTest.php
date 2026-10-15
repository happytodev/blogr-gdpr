<?php

use Happytodev\BlogrGdpr\Filament\Pages\GdprSettings;
use Happytodev\BlogrGdpr\Services\SettingsRepository;
use Illuminate\Support\Facades\DB;

/**
 * Issue #3 — les réglages de l'admin doivent vivre en base, pas dans le fichier
 * de configuration (remplacé à chaque déploiement).
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

    // Un ligne par réglage, sous son chemin de configuration pointé.
    expect(DB::table(SettingsRepository::TABLE)->where('key', 'dpo.email')->value('value'))
        ->toBe('"privacy@example.org"')
        ->and(DB::table(SettingsRepository::TABLE)->where('key', 'cookie_consent.enabled')->value('value'))
        ->toBe('false');

    // Et les valeurs s'appliquent tout de suite, donc la page reflète l'enregistrement.
    expect(config('blogr-gdpr.dpo.email'))->toBe('privacy@example.org')
        ->and(config('blogr-gdpr.cookie_consent.enabled'))->toBeFalse();

    // Le fichier de configuration n'est plus réécrit.
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
    // Une page enregistrée sans être montée porte des valeurs nulles.
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

    // `php artisan config:cache` démarre l'application avant de figer la
    // configuration : y appliquer les surcharges les graverait dans le fichier
    // compilé, qui passerait alors devant la table.
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

    // En environnement de test, le repli applique les valeurs en mémoire.
    expect(config('blogr-gdpr.dpo.email'))->toBe('privacy@example.org')
        ->and(DB::table(SettingsRepository::TABLE)->count())->toBe(0);
});
