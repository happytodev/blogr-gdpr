<?php

use Happytodev\BlogrGdpr\Tests\TestCase;

/**
 * Issue #7 — les formulaires d'accès et de suppression étaient toujours rendus
 * dans la langue par défaut, leurs adresses ne portant pas la langue. Ils sont
 * maintenant servis aussi sous /{locale}/gdpr/…, et les adresses sans préfixe
 * restent en place pour ne casser aucun lien déjà publié.
 */
class Regression7LocalizedGdprPagesTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('blogr.locales', [
            'enabled' => true,
            'default' => 'en',
            'available' => ['en', 'fr'],
        ]);

        // La route « attrape-tout » des pages CMS doit exister pendant ce test :
        // c'est elle qui avalait /fr/gdpr/… avant que le plugin ne réserve le
        // préfixe. Sans elle, le test ne prouverait rien.
        $app['config']->set('blogr.cms.enabled', true);

        // Les vues du plugin étendent la mise en page de Blogr, qui référence les
        // routes du blog (les tests du plugin ne les activent pas par défaut).
        $app['config']->set('blogr.route.frontend.enabled', true);
    }

    public function test_localized_pages_render_in_the_site_language(): void
    {
        $this->get('/fr/gdpr/data-export')
            ->assertOk()
            ->assertSee('Demander vos données');

        $this->get('/en/gdpr/data-export')
            ->assertOk()
            ->assertSee('Request Your Data');

        $this->get('/fr/gdpr/data-erasure')
            ->assertOk()
            // assertSee échappe l'apostrophe, comme le fait le rendu de la vue.
            ->assertSee("Demander l'effacement des données");
    }

    public function test_prefixless_pages_keep_working(): void
    {
        $this->get('/gdpr/data-export')
            ->assertOk()
            ->assertSee('Request Your Data');

        $this->get('/gdpr/data-erasure')->assertOk();
    }

    public function test_the_footer_links_to_the_localized_page(): void
    {
        $html = view('blogr-gdpr::footer-data-export')->render();

        expect($html)->toContain('/en/gdpr/data-export');

        app()->setLocale('fr');

        expect(view('blogr-gdpr::footer-data-export')->render())->toContain('/fr/gdpr/data-export');
    }
}
