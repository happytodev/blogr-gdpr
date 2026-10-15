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

        // Réglages enregistrés depuis l'admin (appliqués au démarrage, plus bas)
        $this->app->singleton(SettingsRepository::class);

        // Les pages d'accès et de suppression sont servies dans la langue du site,
        // sous /{locale}/gdpr/… (issue #7). Encore faut-il que la route « attrape-
        // tout » des pages CMS de Blogr — {locale}/{slug} — laisse passer ce
        // préfixe : Blogr construit son motif à partir de `blogr.cms.reserved_slugs`
        // pendant son boot(), qui suit ce register().
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
        // Les réglages de l'admin (table blogr_gdpr_settings) passent avant tout
        // ce qui lit la configuration : compositeurs de vues, extensions, pages.
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
            // `blogr-gdpr.enabled` éteint tout le plugin ; `cookie_consent.enabled`
            // n'éteint que le bandeau — un site dont la mesure d'audience ne pose
            // aucun cookie n'a pas à en afficher un (issue #4).
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

            // Tant que le visiteur n'a pas accepté, Blogr ne sert pas la balise du
            // fournisseur : le blocage se fait donc ici, à la source, et pas par du
            // JavaScript qui arriverait trop tard (issue #6). Le choix vit dans un
            // cookie, pas seulement dans le `localStorage` du navigateur.
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

            // Le lien « gérer mes préférences » n'a de sens que s'il existe une
            // interface de consentement pour l'accueillir : sans bandeau ni encart
            // de mesure, il n'ouvrait rien et levait une erreur dans la console
            // (issue #4).
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
     * Les blocs d'une traduction portent-ils le HTML que la 2.0.0 y écrivait ?
     *
     * Sert à réparer les pages déjà créées : sans cela, corriger le gabarit ne
     * changeait rien pour les sites installés (issue #5).
     *
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

                    // Une page écrite par la 2.0.0 (ou une version antérieure) porte des
                    // balises HTML dans ses blocs : elle s'affiche avec ses balises en
                    // clair (issue #5). On la remet en markdown.
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
     * Découpe le gabarit en un bloc « content » par section.
     *
     * Le gabarit est du **markdown**, et pas du HTML : le bloc `content` de Blogr
     * passe son champ par `MarkdownHelper`, dont l'environnement CommonMark est
     * configuré avec `html_input => 'escape'` (contre le XSS). Du HTML s'y affiche
     * donc en clair — c'était le défaut de la 2.0.0 (issue #5).
     */
    protected function buildPrivacyPolicyBlocks(string $markdown): array
    {
        $blocks = [];

        $sections = preg_split('/^##\s+/m', $markdown);
        // Un gabarit peut commencer par du texte avant son premier titre : ce
        // premier morceau ne doit pas être transformé en titre.
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
