<?php

namespace Happytodev\BlogrGdpr\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Happytodev\Blogr\Services\ExtensionRegistry;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class GdprSettings extends Page
{
    use InteractsWithForms;

    protected static string|\UnitEnum|null $navigationGroup = 'GDPR';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $title = 'GDPR Settings';

    protected string $view = 'blogr-gdpr::filament.pages.gdpr-settings';

    public static function shouldRegisterNavigation(): bool
    {
        if (! app()->has(ExtensionRegistry::class)) {
            return true;
        }

        return app(ExtensionRegistry::class)->isEnabled('blogr-gdpr');
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    // Cookie consent settings
    public bool $cookie_consent_enabled = true;

    public bool $cookie_consent_required = true;

    public string $cookie_consent_position = 'bottom';

    public string $cookie_consent_theme = 'dark';

    public string $cookie_info_url = '';

    public bool $cookie_categories_essential_required = true;

    public bool $cookie_categories_analytics_required = false;

    public bool $cookie_categories_marketing_required = false;

    // Analytics consent settings
    public bool $analytics_consent_enabled = true;

    public bool $analytics_consent_required = true;

    public string $analytics_consent_position = 'body';

    // Contact consent settings
    public bool $contact_consent_enabled = true;

    public bool $contact_consent_required = true;

    // Privacy policy settings
    public bool $privacy_auto_create = true;

    // DPO settings
    public string $dpo_name = '';

    public string $dpo_email = '';

    public string $dpo_address = '';

    // Data export/erasure settings
    public bool $data_export_enabled = true;

    public bool $data_export_show_link = true;

    public bool $data_erasure_enabled = true;

    public bool $data_erasure_show_link = true;

    // Consent log settings
    public bool $consent_log_enabled = true;

    public int $consent_log_retention_days = 365;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-shield-check';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'GDPR';
    }

    public static function getNavigationLabel(): string
    {
        return 'GDPR Settings';
    }

    public function mount(): void
    {
        $this->loadFromConfig();
    }

    protected function loadFromConfig(): void
    {
        $config = config('blogr-gdpr', []);

        $this->cookie_consent_enabled = $config['cookie_consent']['enabled'] ?? true;
        $this->cookie_consent_required = $config['cookie_consent']['required'] ?? true;
        $this->cookie_consent_position = $config['cookie_consent']['position'] ?? 'bottom';
        $this->cookie_consent_theme = $config['cookie_consent']['theme'] ?? 'dark';
        $this->cookie_info_url = $config['cookie_consent']['info_url'] ?? '';
        $this->cookie_categories_essential_required = $config['cookie_consent']['categories']['essential']['required'] ?? true;
        $this->cookie_categories_analytics_required = $config['cookie_consent']['categories']['analytics']['required'] ?? false;
        $this->cookie_categories_marketing_required = $config['cookie_consent']['categories']['marketing']['required'] ?? false;

        $this->analytics_consent_enabled = $config['analytics_consent']['enabled'] ?? true;
        $this->analytics_consent_required = $config['analytics_consent']['required'] ?? true;
        $this->analytics_consent_position = $config['analytics_consent']['position'] ?? 'body';

        $this->contact_consent_enabled = $config['contact_consent']['enabled'] ?? true;
        $this->contact_consent_required = $config['contact_consent']['required'] ?? true;

        $this->privacy_auto_create = $config['privacy_policy']['auto_create'] ?? true;

        $this->dpo_name = $config['dpo']['name'] ?? '';
        $this->dpo_email = $config['dpo']['email'] ?? '';
        $this->dpo_address = $config['dpo']['address'] ?? '';

        $this->data_export_enabled = $config['data_export']['enabled'] ?? true;
        $this->data_export_show_link = $config['data_export']['show_public_link'] ?? true;
        $this->data_erasure_enabled = $config['data_erasure']['enabled'] ?? true;
        $this->data_erasure_show_link = $config['data_erasure']['show_public_link'] ?? true;

        $this->consent_log_enabled = $config['consent_log']['enabled'] ?? true;
        $this->consent_log_retention_days = $config['consent_log']['retention_days'] ?? 365;
    }

    public function getFormSchema(): array
    {
        return [
            Section::make('Cookie Consent Banner')
                ->schema([
                    Toggle::make('cookie_consent_enabled')
                        ->label('Enable cookie consent banner')
                        ->helperText(__('blogr-gdpr::messages.settings.cookie_consent_enabled'))
                        ->live(),
                    Toggle::make('cookie_consent_required')
                        ->label('Require cookie consent')
                        ->helperText(__('blogr-gdpr::messages.settings.cookie_consent_required'))
                        ->visible(fn () => $this->cookie_consent_enabled)
                        ->live(),
                    Select::make('cookie_consent_position')
                        ->label('Banner position')
                        ->helperText(__('blogr-gdpr::messages.settings.cookie_consent_position'))
                        ->options([
                            'bottom' => 'Bottom',
                            'top' => 'Top',
                        ])
                        ->visible(fn () => $this->cookie_consent_enabled),
                    Select::make('cookie_consent_theme')
                        ->label('Banner theme')
                        ->helperText(__('blogr-gdpr::messages.settings.cookie_consent_theme'))
                        ->options([
                            'dark' => 'Dark',
                            'light' => 'Light',
                        ])
                        ->visible(fn () => $this->cookie_consent_enabled),
                    TextInput::make('cookie_info_url')
                        ->label('Privacy info URL (optional)')
                        ->helperText(__('blogr-gdpr::messages.settings.cookie_info_url'))
                        ->url()
                        ->visible(fn () => $this->cookie_consent_enabled),
                    Section::make('Cookie Categories')
                        ->schema([
                            Toggle::make('cookie_categories_essential_required')
                                ->label('Essential cookies required')
                                ->helperText(__('blogr-gdpr::messages.settings.cookie_categories_required')),
                            Toggle::make('cookie_categories_analytics_required')
                                ->label('Analytics cookies required')
                                ->helperText(__('blogr-gdpr::messages.settings.cookie_categories_required')),
                            Toggle::make('cookie_categories_marketing_required')
                                ->label('Marketing cookies required')
                                ->helperText(__('blogr-gdpr::messages.settings.cookie_categories_required')),
                        ])
                        ->visible(fn () => $this->cookie_consent_enabled),
                ]),

            Section::make('Analytics Consent')
                ->schema([
                    Toggle::make('analytics_consent_enabled')
                        ->label('Enable analytics consent gate')
                        ->helperText(__('blogr-gdpr::messages.settings.analytics_consent_enabled')),
                    Toggle::make('analytics_consent_required')
                        ->label('Require analytics consent')
                        ->helperText(__('blogr-gdpr::messages.settings.analytics_consent_required')),
                    Select::make('analytics_consent_position')
                        ->label('Consent position')
                        ->helperText(__('blogr-gdpr::messages.settings.analytics_consent_position'))
                        ->options([
                            'head' => 'In &lt;head&gt; (before scripts)',
                            'body' => 'In body (after scripts)',
                        ])
                        ->visible(fn () => $this->analytics_consent_enabled),
                ]),

            Section::make('Contact Form Consent')
                ->schema([
                    Toggle::make('contact_consent_enabled')
                        ->label('Enable contact form consent checkbox')
                        ->helperText(__('blogr-gdpr::messages.settings.contact_consent_enabled')),
                    Toggle::make('contact_consent_required')
                        ->label('Require contact form consent')
                        ->helperText(__('blogr-gdpr::messages.settings.contact_consent_required')),
                ]),

            Section::make('Privacy Policy Page')
                ->schema([
                    Toggle::make('privacy_auto_create')
                        ->label('Auto-create privacy policy page via CMS')
                        ->helperText(__('blogr-gdpr::messages.settings.privacy_auto_create')),
                ]),

            Section::make('Data Protection Officer')
                ->schema([
                    TextInput::make('dpo_name')
                        ->label('DPO Name')
                        ->helperText(__('blogr-gdpr::messages.settings.dpo_name')),
                    TextInput::make('dpo_email')
                        ->label('DPO Email')
                        ->helperText(__('blogr-gdpr::messages.settings.dpo_email'))
                        ->email(),
                    TextInput::make('dpo_address')
                        ->label('DPO Address')
                        ->helperText(__('blogr-gdpr::messages.settings.dpo_address')),
                ]),

            Section::make('Data Export & Erasure')
                ->schema([
                    Toggle::make('data_export_enabled')
                        ->label('Enable data export requests')
                        ->helperText(__('blogr-gdpr::messages.settings.data_export_enabled'))
                        ->live(),
                    Toggle::make('data_export_show_link')
                        ->label('Show public link in footer')
                        ->helperText(__('blogr-gdpr::messages.settings.data_export_show_link'))
                        ->visible(fn () => $this->data_export_enabled),
                    TextInput::make('data_export_url')
                        ->label('Data Export URL')
                        ->helperText(__('blogr-gdpr::messages.settings.data_export_url'))
                        ->default(route('gdpr.data-export'))
                        ->dehydrated(false)
                        ->disabled()
                        ->visible(fn () => $this->data_export_enabled)
                        ->prefixIcon('heroicon-o-link')
                        ->extraAttributes(['onclick' => 'navigator.clipboard.writeText(\''.route('gdpr.data-export').'\'); Filament.notify(\'success\', \'URL copied to clipboard\')']),
                    Toggle::make('data_erasure_enabled')
                        ->label('Enable data erasure requests')
                        ->helperText(__('blogr-gdpr::messages.settings.data_erasure_enabled'))
                        ->live(),
                    Toggle::make('data_erasure_show_link')
                        ->label('Show public link in footer')
                        ->helperText(__('blogr-gdpr::messages.settings.data_erasure_show_link'))
                        ->visible(fn () => $this->data_erasure_enabled),
                    TextInput::make('data_erasure_url')
                        ->label('Data Erasure URL')
                        ->helperText(__('blogr-gdpr::messages.settings.data_erasure_url'))
                        ->default(route('gdpr.data-erasure'))
                        ->dehydrated(false)
                        ->disabled()
                        ->visible(fn () => $this->data_erasure_enabled)
                        ->prefixIcon('heroicon-o-link')
                        ->extraAttributes(['onclick' => 'navigator.clipboard.writeText(\''.route('gdpr.data-erasure').'\'); Filament.notify(\'success\', \'URL copied to clipboard\')']),
                ]),

            Section::make('Consent Logging')
                ->schema([
                    Toggle::make('consent_log_enabled')
                        ->label('Enable consent logging')
                        ->helperText(__('blogr-gdpr::messages.settings.consent_log_enabled')),
                    TextInput::make('consent_log_retention_days')
                        ->label('Log retention (days)')
                        ->helperText(__('blogr-gdpr::messages.settings.consent_log_retention'))
                        ->numeric()
                        ->minValue(1),
                ]),
        ];
    }

    public function save(): void
    {
        $data = [
            'enabled' => true,
            'cookie_consent' => [
                'enabled' => $this->cookie_consent_enabled,
                'required' => $this->cookie_consent_required,
                'position' => $this->cookie_consent_position,
                'theme' => $this->cookie_consent_theme,
                'info_url' => $this->cookie_info_url,
                'categories' => [
                    'essential' => [
                        'label' => 'Essential',
                        'description' => 'Required for the site to function properly.',
                        'required' => $this->cookie_categories_essential_required,
                        'default' => true,
                    ],
                    'analytics' => [
                        'label' => 'Analytics',
                        'description' => 'Help us understand how visitors interact with our site.',
                        'required' => $this->cookie_categories_analytics_required,
                        'default' => false,
                    ],
                    'marketing' => [
                        'label' => 'Marketing',
                        'description' => 'Used to deliver relevant advertisements.',
                        'required' => $this->cookie_categories_marketing_required,
                        'default' => false,
                    ],
                ],
            ],
            'analytics_consent' => [
                'enabled' => $this->analytics_consent_enabled,
                'required' => $this->analytics_consent_required,
                'position' => $this->analytics_consent_position,
                'providers' => [],
            ],
            'contact_consent' => [
                'enabled' => $this->contact_consent_enabled,
                'required' => $this->contact_consent_required,
            ],
            'privacy_policy' => [
                'auto_create' => $this->privacy_auto_create,
                'version' => 1,
                'updated_at' => null,
            ],
            'dpo' => [
                'name' => $this->dpo_name,
                'email' => $this->dpo_email,
                'address' => $this->dpo_address,
            ],
            'data_export' => [
                'enabled' => $this->data_export_enabled,
                'show_public_link' => $this->data_export_show_link,
            ],
            'data_erasure' => [
                'enabled' => $this->data_erasure_enabled,
                'show_public_link' => $this->data_erasure_show_link,
            ],
            'consent_log' => [
                'enabled' => $this->consent_log_enabled,
                'retention_days' => $this->consent_log_retention_days,
            ],
        ];

        $this->updateConfigFile($data);

        Artisan::call('config:clear');

        Notification::make()
            ->title('GDPR settings saved successfully!')
            ->success()
            ->send();
    }

    private function updateConfigFile(array $data): void
    {
        if (app()->environment('testing')) {
            foreach (Arr::dot($data) as $key => $value) {
                config()->set("blogr-gdpr.{$key}", $value);
            }

            return;
        }

        $configPath = config_path('blogr-gdpr.php');
        $currentConfig = config('blogr-gdpr', []);
        $updatedConfig = array_merge($currentConfig, $data);
        $content = $this->generateConfigContent($updatedConfig);

        if (! file_exists(dirname($configPath))) {
            mkdir(dirname($configPath), 0755, true);
        }

        File::put($configPath, $content);
    }

    private function generateConfigContent(array $config): string
    {
        $export = var_export($config, true);
        $export = preg_replace('/^\\s+/m', '        ', $export);
        $export = preg_replace('/array \\(/', '[', $export);
        $export = preg_replace('/\\)/', ']', $export);
        $export = preg_replace('/=>\\s*\\n\\s*\\[/', '=> [', $export);

        return "<?php\n\nreturn {$export};\n";
    }
}
