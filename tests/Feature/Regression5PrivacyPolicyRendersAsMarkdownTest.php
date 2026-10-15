<?php

use Happytodev\Blogr\Models\CmsPage;
use Happytodev\BlogrGdpr\BlogrGdprServiceProvider;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #5 — la page de confidentialité générée s'affichait avec ses balises en
 * clair : le plugin fabriquait des blocs HTML alors que le champ `content` d'un
 * bloc Blogr est du **markdown échappé** (`html_input => 'escape'`).
 */
function provider(): BlogrGdprServiceProvider
{
    // Le provider n'est pas un service résolvable par le conteneur : on l'instancie.
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
    // Les tests du plugin ne chargent pas les migrations de Blogr : on pose les
    // deux tables du CMS dont la page de confidentialité a besoin.
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

    // Le gabarit français compte onze sections numérotées, et il commence par son
    // premier titre : aucune section ne doit se retrouver sans son titre.
    expect($blocks)->toHaveCount(11)
        ->and($blocks[0]['data']['content'])->toStartWith('## 1. Introduction')
        ->and($blocks[0]['type'])->toBe('content');
});

it('regression_5_repairs_a_page_generated_by_2_0_0', function () {
    cmsTables();

    // Une page créée par la 2.0.0 : ses blocs portent du HTML, donc la page
    // s'affiche avec ses balises. Le plugin doit la remettre en markdown.
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
