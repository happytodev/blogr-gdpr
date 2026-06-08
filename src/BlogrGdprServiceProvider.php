<?php

namespace Happytodev\BlogrGdpr;

use Filament\Facades\Filament;
use Happytodev\Blogr\Models\CmsPage;
use Happytodev\Blogr\Services\ExtensionRegistry;
use Happytodev\BlogrGdpr\Filament\Pages\GdprSettings;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Livewire\Mechanisms\ComponentRegistry;

class BlogrGdprServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/blogr-gdpr.php', 'blogr-gdpr');

        $this->app->singleton(BlogrGdprPlugin::class, fn () => new BlogrGdprPlugin);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'blogr-gdpr');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'blogr-gdpr');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        $this->publishes([
            __DIR__ . '/../config/blogr-gdpr.php' => config_path('blogr-gdpr.php'),
        ], 'blogr-gdpr-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/blogr-gdpr'),
        ], 'blogr-gdpr-views');

        $this->publishes([
            __DIR__ . '/../resources/lang' => $this->app->langPath('vendor/blogr-gdpr'),
        ], 'blogr-gdpr-lang');

        $this->registerExtensions();
        $this->registerBladeStacks();
        $this->registerFilamentPages();
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
        if (!class_exists(Filament::class)) {
            return;
        }

        try {
            $panel = Filament::getPanel('admin');
        } catch (\Exception $e) {
            return;
        }

        if (!$panel) {
            return;
        }

        $panel->pages([GdprSettings::class]);

        Livewire::component(
            app(ComponentRegistry::class)->getName(GdprSettings::class),
            GdprSettings::class,
        );

        $slug = GdprSettings::getSlug($panel);
        $path = trim($panel->getPath(), '/') . '/' . ltrim($slug, '/');
        $middleware = array_merge($panel->getMiddleware(), $panel->getAuthMiddleware());

        Route::get($path, GdprSettings::class)
            ->middleware($middleware)
            ->name('filament.' . $panel->getId() . '.pages.' . $slug);
    }

    protected function registerBladeStacks(): void
    {
        $this->registerDpoComposer();

        View::composer('blogr::layouts.blog', function ($view) {
            if (!config('blogr-gdpr.enabled')) {
                return;
            }
            $view->getFactory()->startPush(
                'cookie-consent',
                view('blogr-gdpr::cookie-consent')->render(),
            );
        });

        View::composer('blogr::components.analytics-tracker', function ($view) {
            if (!config('blogr-gdpr.analytics_consent.enabled')) {
                return;
            }
            $providers = config('blogr-gdpr.analytics_consent.providers', []);
            $trackerProvider = config('blogr.analytics.provider');
            if (empty($providers) || !in_array($trackerProvider, $providers, true)) {
                return;
            }
            $position = config('blogr-gdpr.analytics_consent.position', 'body');
            if ($position === 'body') {
                $view->getFactory()->startPush(
                    'analytics-after',
                    view('blogr-gdpr::analytics-consent')->render(),
                );
            } else {
                $view->getFactory()->startPush(
                    'analytics-consent',
                    view('blogr-gdpr::analytics-consent')->render(),
                );
            }
        });

        View::composer('blogr::components.blocks.contact_form', function ($view) {
            if (!config('blogr-gdpr.contact_consent.enabled')) {
                return;
            }
            $view->getFactory()->startPush(
                'contact-form-consent',
                view('blogr-gdpr::contact-consent')->render(),
            );
        });

        View::composer('blogr::components.footer', function ($view) {
            if (!config('blogr-gdpr.enabled') || !config('blogr-gdpr.privacy_policy.auto_create')) {
                return;
            }
            $view->getFactory()->startPush(
                'footer-links',
                view('blogr-gdpr::footer-privacy-link')->render(),
            );
        });
    }

    protected function registerDpoComposer(): void
    {
        View::composer('blogr::cms.pages.default', function ($view) {
            $page = $view->page ?? null;
            if (!$page || $page->slug !== 'privacy-policy') {
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

            $dpoHtml = '<p><strong>' . e($dpoName) . '</strong>';
            if (!empty($dpoEmail)) {
                $dpoHtml .= '<br><a href="mailto:' . e($dpoEmail) . '">' . e($dpoEmail) . '</a>';
            }
            if (!empty($dpoAddress)) {
                $dpoHtml .= '<br>' . nl2br(e($dpoAddress));
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

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\PruneConsentLogs::class,
                Console\Commands\ProcessDataRequests::class,
            ]);
        }
    }

    protected function autoCreatePrivacyPolicy(): void
    {
        if (!config('blogr-gdpr.privacy_policy.auto_create')) {
            return;
        }

        try {
            $page = CmsPage::firstOrCreate(
                ['slug' => 'privacy-policy'],
                ['is_published' => true, 'is_draft' => false, 'template' => 'default']
            );

            $dataDir = __DIR__ . '/../data/privacy-policy';
            $configuredLocales = config('blogr.locales.available', ['en']);
            $fileLocales = [];

            foreach (glob($dataDir . '/*.json') as $filePath) {
                $locale = basename($filePath, '.json');
                $fileLocales[] = $locale;
            }

            $locales = array_unique(array_merge($configuredLocales, $fileLocales));

            foreach ($locales as $locale) {
                $dataPath = $dataDir . "/{$locale}.json";
                if (!file_exists($dataPath)) {
                    continue;
                }

                $data = json_decode(file_get_contents($dataPath), true);
                if (!$data) {
                    continue;
                }

                $blocks = $this->buildPrivacyPolicyBlocks($data['content'] ?? '');

                $translation = $page->translations()->where('locale', $locale)->first();

                if ($translation) {
                    $hasOldContent = !empty($translation->content) && empty($translation->blocks);
                    if ($hasOldContent) {
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

    protected function buildPrivacyPolicyBlocks(string $htmlContent): array
    {
        $blocks = [];

        $sections = preg_split('/<h2\b[^>]*>/i', $htmlContent);

        foreach ($sections as $section) {
            $section = trim($section);
            if (empty($section)) {
                continue;
            }

            $sectionContent = '<h2>' . $section;

            $blocks[] = [
                'type' => 'content',
                'data' => [
                    'content' => $sectionContent,
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
