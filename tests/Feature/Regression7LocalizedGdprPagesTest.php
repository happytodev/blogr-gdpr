<?php

use Happytodev\BlogrGdpr\Tests\TestCase;

/**
 * Issue #7 — the access and erasure forms were always rendered in the default
 * language, their addresses carrying none. They are now also served under
 * /{locale}/gdpr/…, and the prefixless addresses stay in place so that no
 * published link breaks.
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

        // Blogr's CMS catch-all route must exist during this test: it is the one that
        // swallowed /fr/gdpr/… before the plugin reserved the prefix. Without it, the
        // test would prove nothing.
        $app['config']->set('blogr.cms.enabled', true);

        // The plugin's views extend Blogr's layout, which references the blog routes
        // (the plugin's tests do not enable them by default).
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
            // assertSee escapes the apostrophe, as the view rendering does.
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
