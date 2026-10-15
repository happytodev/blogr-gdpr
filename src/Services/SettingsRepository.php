<?php

namespace Happytodev\BlogrGdpr\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Stores the settings an administrator saves from the GDPR settings page.
 *
 * Why a table instead of the config file: each deployment builds a fresh release
 * directory from the repository, so a value written to `config/blogr-gdpr.php`
 * on the server is replaced by the next deploy — and the file is not even the
 * first target, since the page used to write the package's own copy inside
 * `vendor/`. Values kept here are applied on top of the file when the
 * application boots, which makes a save durable while the committed file keeps
 * playing the role of the defaults.
 *
 * The list is read once per request and never cached: it changes only when an
 * administrator saves, and a stale entry would keep a deleted setting alive.
 *
 * `blogr_gdpr_settings` has existed since the first migration — nothing used it
 * until now (issue #3).
 */
class SettingsRepository
{
    public const TABLE = 'blogr_gdpr_settings';

    /**
     * Memoised on the instance, not statically: the repository is a singleton for
     * one request, and a static would leak the values of one test into the next
     * one in the same process.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $memo = null;

    protected bool $readFailed = false;

    /**
     * Every stored setting, as a flat list of dotted config keys (e.g. `dpo.email`)
     * mapped to their decoded value.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->memo !== null) {
            return $this->memo;
        }

        return $this->memo = $this->readFromDatabase();
    }

    public function count(): int
    {
        return count($this->all());
    }

    public function hasTable(): bool
    {
        try {
            return Schema::hasTable(self::TABLE);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Apply every stored setting on top of the configuration loaded from the file.
     */
    public function applyToConfig(): void
    {
        if ($this->isBuildingCompiledConfig()) {
            return;
        }

        foreach ($this->all() as $key => $value) {
            config()->set("blogr-gdpr.{$key}", $value);
        }
    }

    /**
     * Store the given dotted keys.
     *
     * A null is not a value here: it means "unset", so it is dropped rather than
     * stored — a null would make the file's value unreachable for a field nobody
     * filled, and a page saved without being mounted would blank the settings.
     *
     * @param  array<string, mixed>  $values
     */
    public function put(array $values): void
    {
        $values = array_filter($values, fn ($value) => $value !== null);

        if ($values === [] || ! $this->hasTable()) {
            return;
        }

        $now = now();

        DB::transaction(function () use ($values, $now) {
            foreach ($values as $key => $value) {
                DB::table(self::TABLE)->updateOrInsert(
                    ['key' => $key],
                    [
                        'value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
                        'updated_at' => $now,
                        'created_at' => $now,
                    ],
                );
            }
        });

        $this->flush();
    }

    /**
     * Drop every stored setting, so the values from the config file apply again.
     *
     * @return int number of deleted settings
     */
    public function forgetAll(): int
    {
        if (! $this->hasTable()) {
            return 0;
        }

        $deleted = DB::table(self::TABLE)->delete();

        $this->flush();

        return $deleted;
    }

    /** Forget the memoised list after a write. */
    public function flush(): void
    {
        $this->memo = null;
        $this->readFailed = false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function readFromDatabase(): array
    {
        if ($this->readFailed) {
            return [];
        }

        try {
            return DB::table(self::TABLE)
                ->pluck('value', 'key')
                ->map(function ($value): mixed {
                    return is_string($value) ? json_decode($value, true) : null;
                })
                ->all();
        } catch (Throwable) {
            // The table has not been migrated yet (or the connection is down):
            // the site must keep serving the values from the config file.
            $this->readFailed = true;

            return [];
        }
    }

    /**
     * `php artisan config:cache` boots the application before dumping the
     * configuration, so applying the stored settings there would bake them into
     * `bootstrap/cache/config.php` — the compiled file would then outrank the
     * table, and deleting a setting would have no effect until the next cache
     * rebuild. Stored settings belong to the runtime, never to the build.
     */
    protected function isBuildingCompiledConfig(): bool
    {
        if (! app()->runningInConsole()) {
            return false;
        }

        return in_array($_SERVER['argv'][1] ?? null, ['config:cache', 'optimize'], true);
    }
}
