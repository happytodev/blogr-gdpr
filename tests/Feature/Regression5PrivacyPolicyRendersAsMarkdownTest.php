<?php

use Happytodev\Blogr\Models\CmsPage;
use Happytodev\BlogrGdpr\BlogrGdprServiceProvider;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #5 — the generated privacy policy displayed its own markup as text:
 * the plugin built HTML blocks while a Blogr content block holds **escaped**
 * **markdown** (`html_input => 'escape'`).
 */
function provider(): BlogrGdprServiceProvider
{
    // The provider is not a resolvable service: we instantiate it.
    return new BlogrGdprServiceProvider(app());
}

function providerMethod(string $name): ReflectionMethod
{
    $method = (new ReflectionClass(provider()))->getMethod($name);
    $method->setAccessible(true);

    return $method;
}

function cmsTables(): void
{
    // The plugin's tests do not load Blogr's migrations: we create the two CMS
    // tables the privacy policy needs.
    Schema::create('cms_pages', function ($table) {
        $table->id();
        $table->string('slug')->unique();
        $table->string('template')->default('default');
        $table->longText('blocks')->nullable();
        $table->boolean('is_published')->default(false);
        $table->boolean('is_homepage')->default(false);
        $table->timestamp('published_at')->nullable();
        $table->string('default_locale', 2)->default('en');
        $table->timestamps();
    });

    Schema::create('cms_page_translations', function ($table) {
        $table->id();
        $table->foreignId('cms_page_id');
        $table->string('locale', 2);
        $table->string('slug');
        $table->string('title');
        $table->longText('content')->nullable();
        $table->longText('blocks')->nullable();
        $table->string('meta_title')->nullable();
        $table->text('meta_description')->nullable();
        $table->text('meta_keywords')->nullable();
        $table->boolean('is_complete')->default(false);
        $table->timestamps();
    });
}

function templateContent(string $locale = 'fr'): string
{
    $path = dirname(__DIR__, 2)."/data/privacy-policy/{$locale}.json";

    return json_decode(file_get_contents($path), true)['content'];
}

it('regression_5_generates_sections_that_render_as_html', function () {
    $blocks = providerMethod('buildPrivacyPolicyBlocks')->invoke(provider(), templateContent());

    expect($blocks)->not->toBeEmpty();

    $html = view('blogr::components.blocks.content', ['data' => $blocks[0]['data']])->render();

    expect($html)->toContain('<h2')
        ->and($html)->not->toContain('&lt;h2&gt;')
        ->and($html)->not->toContain('&lt;p&gt;');
});

it('regression_5_splits_every_section_of_the_template', function () {
    $blocks = providerMethod('buildPrivacyPolicyBlocks')->invoke(provider(), templateContent());

    // The French template has eleven numbered sections, and it starts with its
    // first heading: no section must end up without one.
    expect($blocks)->toHaveCount(11)
        ->and($blocks[0]['data']['content'])->toStartWith('## 1. Introduction')
        ->and($blocks[0]['type'])->toBe('content');
});

it('regression_5_repairs_a_page_generated_by_2_0_0', function () {
    cmsTables();

    // A page created by 2.0.0: its blocks carry HTML, so the page shows its tags.
    // The plugin must put it back to markdown.
    $page = CmsPage::firstOrCreate(
        ['slug' => 'privacy-policy'],
        ['template' => 'default', 'is_published' => true, 'is_homepage' => false, 'default_locale' => 'en'],
    );

    $page->translations()->updateOrCreate(
        ['locale' => 'fr'],
        [
            'slug' => 'privacy-policy',
            'title' => 'Politique de confidentialité',
            'meta_description' => 'ancienne page',
            'content' => null,
            'blocks' => [
                [
                    'type' => 'content',
                    'data' => ['content' => '<h2>1. Introduction</h2><p>Texte généré par la 2.0.0.</p>'],
                ],
            ],
        ],
    );

    config(['blogr-gdpr.privacy_policy.auto_create' => true]);
    providerMethod('autoCreatePrivacyPolicy')->invoke(provider());

    $blocks = CmsPage::where('slug', 'privacy-policy')->firstOrFail()
        ->translations()->where('locale', 'fr')->firstOrFail()->blocks;

    $content = $blocks[0]['data']['content'] ?? '';

    expect($content)->toContain('## ')
        ->and($content)->not->toContain('<h2>');
});
