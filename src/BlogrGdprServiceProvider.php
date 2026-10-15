<?php

namespace Happytodev\BlogrGdpr;

use Filament\Facades\Filament;
use Filament\PanelRegistry;
use Happytodev\Blogr\Models\CmsPage;
use Happytodev\Blogr\Services\ExtensionRegistry;
use Happytodev\BlogrGdpr\Filament\Pages\GdprSettings;
use Happytodev\BlogrGdpr\Services\AnalyticsConsentCookie;
use Happytodev\BlogrGdpr\Services\SettingsRepository;
use Happytodev\BlogrGdpr\Filament\Resources\ConsentLogResource\Pages\ListConsentLogs;
use Happytodev\BlogrGdpr\Filament\Resources\ConsentLogResource\Pages\ViewConsentLog;
use Happytodev\BlogrGdpr\Filament\Resources\GdprRequestResource\Pages\EditGdprRequest;
use Happytodev\BlogrGdpr\Filament\Resources\GdprRequestResource\Pages\ListGdprRequests;
use Happytodev\BlogrGdpr\Filament\Resources\GdprRequestResource\Pages\ViewGdprRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Illuminate\Support\Str;

class BlogrGdprServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/blogr-gdpr.php', 'blogr-gdpr');

        $this->app->singleton(BlogrGdprPlugin::class, fn () => new BlogrGdprPlugin);

        // Settings saved from the admin (applied at boot, further down)
        $this->app->singleton(SettingsRepository::class);

        // The access and erasure forms are served in the site's language, under
        // /{locale}/gdpr/… (issue #7). Blogr's CMS catch-all route — {locale}/{slug}
        // — must let that prefix through: Blogr builds its pattern from
        // `blogr.cms.reserved_slugs` during its boot(), which follows this register().
        $reservedSlugs = config('blogr.cms.reserved_slugs');

        if (is_array($reservedSlugs) && ! in_array('gdpr', $reservedSlugs, true)) {
            config(['blogr.cms.reserved_slugs' => [...$reservedSlugs, 'gdpr']]);
        }

        $this->app->afterResolving(PanelRegistry::class, function (PanelRegistry $registry): void {
            $panel = $registry->get('admin');

            if (! $panel) {
                return;
            }

            $panel->plugin($this->app->make(BlogrGdprPlugin::class));
        });
    }

    public function boot(): void
    {
        // The admin settings (blogr_gdpr_settings table) come before everything
        // that reads the configuration: view composers, extensions, pages.
        $this->app->make(SettingsRepository::class)->applyToConfig();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'blogr-gdpr');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'blogr-gdpr');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        $this->publishes([
            __DIR__.'/../config/blogr-gdpr.php' => config_path('blogr-gdpr.php'),
        ], 'blogr-gdpr-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/blogr-gdpr'),
        ], 'blogr-gdpr-views');

        $this->publishes([
            __DIR__.'/../resources/lang' => $this->app->langPath('vendor/blogr-gdpr'),
        ], 'blogr-gdpr-lang');

        $this->registerExtensions();
        $this->registerBladeStacks();
        $this->registerFilamentPages();
        $this->registerFilamentResourceComponents();
        $this->autoCreatePrivacyPolicy();
        $this->registerCommands();
    }

    protected function registerExtensions(): void
    {
        if ($this->app->has(ExtensionRegistry::class)) {
            $registry = $this->app->make(ExtensionRegistry::class);
            $registry->register($this->app->make(BlogrGdprPlugin::class));
        }
    }

    protected function registerFilamentPages(): void
    {
        if (! $this->isExtensionEnabled()) {
            return;
        }

        if (! class_exists(Filament::class)) {
            return;
        }

        try {
            $panel = Filament::getPanel('admin');
        } catch (\Exception $e) {
            return;
        }

        if (! $panel) {
            return;
        }

        $panel->pages([GdprSettings::class]);

        Livewire::component(
            Str::kebab(class_basename(GdprSettings::class)),
            GdprSettings::class,
        );

        $slug = GdprSettings::getSlug($panel);
        $path = trim($panel->getPath(), '/').'/'.ltrim($slug, '/');
        $middleware = array_merge($panel->getMiddleware(), $panel->getAuthMiddleware());

        Route::get($path, GdprSettings::class)
            ->middleware($middleware)
            ->name('filament.'.$panel->getId().'.pages.'.$slug);
    }

    protected function registerFilamentResourceComponents(): void
    {
        if (! $this->isExtensionEnabled()) {
            return;
        }

        if (! class_exists(Filament::class)) {
            return;
        }

        $pages = [
            ListGdprRequests::class,
            ViewGdprRequest::class,
            EditGdprRequest::class,
            ListConsentLogs::class,
            ViewConsentLog::class,
        ];

        foreach ($pages as $page) {
            Livewire::component(
                Str::kebab(class_basename($page)),
                $page,
            );
        }
    }

    protected function registerBladeStacks(): void
    {
        $this->registerDpoComposer();

        View::composer('blogr::layouts.blog', function ($view) {
            // `blogr-gdpr.enabled` turns the whole plugin off; `cookie_consent.enabled`
            // only turns the banner off — a site whose measurement sets no cookie has no
            // reason to display one (issue #4).
            if (! config('blogr-gdpr.enabled') || ! config('blogr-gdpr.cookie_consent.enabled', true) || ! $this->isExtensionEnabled()) {
                return;
            }
            $view->getFactory()->startPush(
                'cookie-consent',
                view('blogr-gdpr::cookie-consent')->render(),
            );
        });

        View::composer('blogr::components.analytics-tracker', function ($view) {
            if (! config('blogr-gdpr.analytics_consent.enabled') || ! $this->isExtensionEnabled()) {
                return;
            }
            $trackerProvider = config('blogr.analytics.provider');
            if (blank($trackerProvider)) {
                return;
            }

            // Until the visitor accepts, Blogr does not serve the provider's tag: the
            // blocking happens here, at the source, and not through JavaScript that
            // would arrive too late (issue #6). The choice lives in a cookie, not only
            // in the browser's `localStorage`.
            $accepted = AnalyticsConsentCookie::hasAccepted(request());

            if (! $accepted) {
                config(['blogr.analytics.enabled' => false]);
            }

            $position = config('blogr-gdpr.analytics_consent.position', 'body');
            if ($position === 'body') {
                $view->getFactory()->startPush(
                    'analytics-after',
                    view('blogr-gdpr::analytics-consent', ['accepted' => $accepted])->render(),
                );
            } else {
                $view->getFactory()->startPush(
                    'analytics-consent',
                    view('blogr-gdpr::analytics-consent', ['accepted' => $accepted])->render(),
                );
            }
        });

        View::composer('blogr::components.blocks.contact_form', function ($view) {
            if (! config('blogr-gdpr.contact_consent.enabled') || ! $this->isExtensionEnabled()) {
                return;
            }
            $view->getFactory()->startPush(
                'contact-form-consent',
                view('blogr-gdpr::contact-consent')->render(),
            );
        });

        View::composer('blogr::components.footer', function ($view) {
            if (! config('blogr-gdpr.enabled') || ! $this->isExtensionEnabled()) {
                return;
            }

            if (config('blogr-gdpr.privacy_policy.auto_create')) {
                $view->getFactory()->startPush(
                    'footer-links',
                    view('blogr-gdpr::footer-privacy-link')->render(),
                );
            }

            // The "manage my preferences" link only makes sense when there is a consent
            // interface to welcome it: with no banner and no measurement panel, it opened
            // nothing and raised a console error (issue #4).
            if (config('blogr-gdpr.cookie_consent.enabled') || config('blogr-gdpr.analytics_consent.enabled')) {
                $view->getFactory()->startPush(
                    'footer-links',
                    view('blogr-gdpr::footer-manage-preferences')->render(),
                );
            }

            if (config('blogr-gdpr.data_export.show_public_link')) {
                $view->getFactory()->startPush(
                    'footer-links',
                    view('blogr-gdpr::footer-data-export')->render(),
                );
            }

            if (config('blogr-gdpr.data_erasure.show_public_link')) {
                $view->getFactory()->startPush(
                    'footer-links',
                    view('blogr-gdpr::footer-data-erasure')->render(),
                );
            }
        });
    }

    protected function registerDpoComposer(): void
    {
        View::composer('blogr::cms.pages.default', function ($view) {
            if (! $this->isExtensionEnabled()) {
                return;
            }

            $page = $view->page ?? null;
            if (! $page || $page->slug !== 'privacy-policy') {
                return;
            }

            $dpoName = config('blogr-gdpr.dpo.name', '');
            if (empty($dpoName)) {
                return;
            }

            $blocks = $view->blocks ?? [];
            if (empty($blocks)) {
                return;
            }

            $dpoEmail = config('blogr-gdpr.dpo.email', '');
            $dpoAddress = config('blogr-gdpr.dpo.address', '');

            $dpoHtml = '<p><strong>'.e($dpoName).'</strong>';
            if (! empty($dpoEmail)) {
                $dpoHtml .= '<br><a href="mailto:'.e($dpoEmail).'">'.e($dpoEmail).'</a>';
            }
            if (! empty($dpoAddress)) {
                $dpoHtml .= '<br>'.nl2br(e($dpoAddress));
            }
            $dpoHtml .= '</p>';

            $contactKey = null;
            foreach ($blocks as $key => $block) {
                $content = $block['data']['content'] ?? '';
                if (str_contains($content, 'Contact Us') || preg_match('/<h2[^>]*>\s*10\./i', $content)) {
                    $contactKey = $key;
                    break;
                }
            }

            if ($contactKey !== null) {
                $blocks[$contactKey]['data']['content'] .= $dpoHtml;
            }

            $view->with('blocks', $blocks);
        });
    }

    protected function isExtensionEnabled(): bool
    {
        if (! $this->app->has(ExtensionRegistry::class)) {
            return true;
        }

        return $this->app->make(ExtensionRegistry::class)->isEnabled('blogr-gdpr');
    }

    /**
     * Do a translation's blocks carry the HTML that 2.0.0 wrote into them?
     *
     * It serves to repair pages already created: without it, fixing the template
     * changed nothing for installed sites (issue #5).
     * @param  array<int, array<string, mixed>>|null  $blocks
     */
    protected function blocksWereGeneratedAsHtml(?array $blocks): bool
    {
        foreach ($blocks ?? [] as $block) {
            $content = $block['data']['content'] ?? null;

            if (is_string($content) && preg_match('/<(h2|p|ul|li)\b/i', $content)) {
                return true;
            }
        }

        return false;
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\PruneConsentLogs::class,
            ]);
        }
    }

    protected function autoCreatePrivacyPolicy(): void
    {
        if (! config('blogr-gdpr.privacy_policy.auto_create')) {
            return;
        }

        try {
            $page = CmsPage::firstOrCreate(
                ['slug' => 'privacy-policy'],
                ['is_published' => true, 'is_draft' => false, 'template' => 'default']
            );

            $dataDir = __DIR__.'/../data/privacy-policy';
            $configuredLocales = config('blogr.locales.available', ['en']);
            $fileLocales = [];

            foreach (glob($dataDir.'/*.json') as $filePath) {
                $locale = basename($filePath, '.json');
                $fileLocales[] = $locale;
            }

            $locales = array_unique(array_merge($configuredLocales, $fileLocales));

            foreach ($locales as $locale) {
                $dataPath = $dataDir."/{$locale}.json";
                if (! file_exists($dataPath)) {
                    continue;
                }

                $data = json_decode(file_get_contents($dataPath), true);
                if (! $data) {
                    continue;
                }

                $blocks = $this->buildPrivacyPolicyBlocks($data['content'] ?? '');

                $translation = $page->translations()->where('locale', $locale)->first();

                if ($translation) {
                    $hasOldContent = ! empty($translation->content) && empty($translation->blocks);

                    // A page written by 2.0.0 (or an earlier version) carries HTML tags in its
                    // blocks: it displays its tags as plain text (issue #5). We put it back to
                    // markdown.
                    if ($hasOldContent || $this->blocksWereGeneratedAsHtml($translation->blocks)) {
                        $translation->update([
                            'content' => null,
                            'blocks' => $blocks,
                        ]);
                    }
                } else {
                    $page->translations()->create([
                        'locale' => $locale,
                        'title' => $data['title'] ?? 'Privacy Policy',
                        'slug' => 'privacy-policy',
                        'content' => null,
                        'blocks' => $blocks,
                        'meta_description' => $data['meta_description'] ?? '',
                    ]);
                }
            }
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * Split the template into one "content" block per section.
     *
     * The template is **markdown**, not HTML: a Blogr content block passes its
     * field through `MarkdownHelper`, whose CommonMark environment is configured
     * with `html_input => 'escape'` (against XSS). HTML therefore shows as plain
     * text there — that was the 2.0.0 defect (issue #5).
     */
    protected function buildPrivacyPolicyBlocks(string $markdown): array
    {
        $blocks = [];

        $sections = preg_split('/^##\s+/m', $markdown);
        // A template may start with text before its first heading: that first chunk
        // must not be turned into a heading.
        $firstChunkIsAHeading = str_starts_with(ltrim($markdown), '## ');

        foreach ($sections as $index => $section) {
            $section = trim($section);
            if (empty($section)) {
                continue;
            }

            $blocks[] = [
                'type' => 'content',
                'data' => [
                    'content' => ($index === 0 && ! $firstChunkIsAHeading) ? $section : '## '.$section,
                    'max_width' => 'prose',
                    'background_type' => 'none',
                    'background_type_dark' => 'none',
                ],
            ];
        }

        if (empty($blocks)) {
            $blocks[] = [
                'type' => 'content',
                'data' => [
                    'content' => $htmlContent,
                    'max_width' => 'prose',
                    'background_type' => 'none',
                    'background_type_dark' => 'none',
                ],
            ];
        }

        return $blocks;
    }
}
