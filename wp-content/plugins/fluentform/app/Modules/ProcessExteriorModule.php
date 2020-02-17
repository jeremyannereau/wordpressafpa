<?php

namespace FluentForm\App\Modules;

use FluentForm\App\Modules\Acl\Acl;

class ProcessExteriorModule
{
    public function handleExteriorPages()
    {
        if (isset($_GET['fluentform_pages']) && $_GET['fluentform_pages'] == 1) {
            if (isset($_GET['preview_id']) && $_GET['preview_id']) {
                $this->renderFormPreview(intval($_GET['preview_id']));
            }
        }
    }

    public function renderFormPreview($form_id)
    {
        if (Acl::hasAnyFormPermission($form_id)) {
            add_filter('fluentform_is_form_renderable', function ($renderable) {
                $renderable['status'] = true;
                return $renderable;
            });

            $form = wpFluent()->table('fluentform_forms')->find($form_id);
            if ($form) {
                echo \FluentForm\View::make('frameless.show_review', [
                    'form_id' => $form_id,
                    'form' => $form
                ]);
                exit();
            }
        }
    }

    private function loadDefaultPageTemplate()
    {
        add_filter('template_include', function ($original) {
            return locate_template(['page.php', 'single.php', 'index.php']);
        });
    }

    /**
     * Set the posts to one
     *
     * @param  WP_Query $query
     *
     * @return void
     */
    public function pre_get_posts($query)
    {
        if ($query->is_main_query()) {
            $query->set('posts_per_page', 1);
        }
    }
}
