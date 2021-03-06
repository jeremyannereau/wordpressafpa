<?php

namespace FluentForm\App\Modules\Renderer\GlobalSettings;

use FluentForm\Framework\Foundation\Application;

class Settings
{
    /**
     * @var \FluentForm\Framework\Foundation\Application
     */
    protected $app;

    /**
     * GlobalSettings constructor.
     *
     * @param \FluentForm\Framework\Foundation\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Render the page for native global settings components
     *
     * @throws \Exception
     */
    public function render()
    {
        $this->enqueue();

        $this->app->view->render('admin.settings.settings');
    }

    /**
     * Enqueue necessary resources.
     *
     * @throws \Exception
     */
    public function enqueue()
    {
        wp_enqueue_script('fluentform-global-settings-js');

        wp_localize_script('fluentform-global-settings-js', 'FluentFormApp', [
            'plugin' => $this->app->getSlug(),
        ]);
    }
}
