<?php

use FluentForm\App\Modules\Component\Component;

/**
 * Declare common actions/filters/shortcodes
 */


/**
 * @var $app \FluentForm\Framework\Foundation\Application
 */

add_action('save_post', function ($post_id) {
    if (isset($_POST['post_content'])) {
        $post_content = $_POST['post_content'];
    } else {
        $post = get_post($post_id);
        $post_content = $post->post_content;
    }

    $shortcodeIds = \FluentForm\App\Helpers\Helper::getShortCodeIds(
        $post_content, 'fluentform', 'id'
    );

    $shortcodeModalIds = \FluentForm\App\Helpers\Helper::getShortCodeIds(
        $post_content, 'fluentform_modal', 'form_id'
    );

    if($shortcodeModalIds) {
        $shortcodeIds = array_merge($shortcodeIds, $shortcodeModalIds);
    }

    if ($shortcodeIds) {
        update_post_meta($post_id, '_has_fluentform', $shortcodeIds);
    } elseif (get_post_meta($post_id, '_has_fluentform', true)) {
        update_post_meta($post_id, '_has_fluentform', []);
    }
});

$component = new Component($app);
$component->addRendererActions();
$component->addFluentFormShortCode();
$component->addFluentFormDefaultValueParser();

$component = new \FluentForm\App\Modules\Component\Component($app);
$component->addFluentformSubmissionInsertedFilter();
$component->addIsRenderableFilter();

$app->addAction('wp', function () use ($app) {
    if (isset($_GET['fluentform_pages']) && $_GET['fluentform_pages'] == 1) {

        add_action('wp_enqueue_scripts', function () use ($app) {
            wp_enqueue_style('fluentform-styles');
            wp_enqueue_style('fluentform-public-default');
            wp_enqueue_script('fluent-form-submission');
            wp_enqueue_style('fluent-form-preview', $app->publicUrl('css/preview.css'));
        });
        
        (new \FluentForm\App\Modules\ProcessExteriorModule())->handleExteriorPages();
    }
});


$elements = [
    'select',
    'input_checkbox',
    'address',
    'select_country',
    'gdpr_agreement',
    'terms_and_condition',
];

foreach ($elements as $element) {
    $event = 'fluentform_response_render_' . $element;
    $app->addFilter($event, function ($response, $field, $form_id) {
        if ($field['element'] == 'address' && isset($response->country)) {
            $countryList = getFluentFormCountryList();
            if (isset($countryList[$response->country])) {
                $response->country = $countryList[$response->country];
            }
        }

        if ($field['element'] == 'select_country') {
            $countryList = getFluentFormCountryList();
            if (isset($countryList[$response])) {
                $response = $countryList[$response];
            }
        }

        if (in_array($field['element'], array('gdpr_agreement', 'terms_and_condition'))) {
            $response = __('Accepted', 'fluentform');
        }

        return \FluentForm\App\Modules\Form\FormDataParser::formatValue($response);
    }, 10, 3);
}

$app->addFilter('fluentform_response_render_input_file', function ($response, $field, $form_id, $isHtml = false) {
    return \FluentForm\App\Modules\Form\FormDataParser::formatFileValues($response, $isHtml);
}, 10, 4);

$app->addFilter('fluentform_response_render_input_image', function ($response, $field, $form_id, $isHtml = false) {
    return \FluentForm\App\Modules\Form\FormDataParser::formatImageValues($response, $isHtml);
}, 10, 4);

$app->addFilter('fluentform_response_render_input_repeat', function ($response, $field, $form_id) {
    return \FluentForm\App\Modules\Form\FormDataParser::formatRepeatFieldValue($response, $field, $form_id);
}, 10, 3);

$app->addFilter('fluentform_response_render_tabular_grid', function ($response, $field, $form_id) {
    return \FluentForm\App\Modules\Form\FormDataParser::formatTabularGridFieldValue($response, $field, $form_id);
}, 10, 3);

$app->addFilter('fluentform_response_render_input_name', function ($response) {
    return \FluentForm\App\Modules\Form\FormDataParser::formatName($response);
}, 10, 1);


$app->addFilter('fluentform_filter_insert_data', function ($data) {
    $settings = get_option('_fluentform_global_form_settings', false);
    if (is_array($settings) && isset($settings['misc'])) {
        if (isset($settings['misc']['isIpLogingDisabled'])) {
            if ($settings['misc']['isIpLogingDisabled']) {
                unset($data['ip']);
            }
        }
    }
    return $data;
});


// Register api response log hooks
$app->addAction(
    'fluentform_after_submission_api_response_success',
    'fluentform_after_submission_api_response_success', 10, 6
);

$app->addAction(
    'fluentform_after_submission_api_response_failed',
    'fluentform_after_submission_api_response_failed', 10, 6
);

function fluentform_after_submission_api_response_success($form, $entryId, $data, $feed, $res, $msg = '')
{
    try {

        $isDev = wpFluentForm()->getEnv() != 'production';
        if (!apply_filters('fluentform_api_success_log', $isDev, $form, $feed)) return;

        wpFluent()->table('fluentform_submission_meta')->insert([
            'response_id' => $entryId,
            'form_id'     => $form->id,
            'meta_key'    => 'api_log',
            'value'       => $msg,
            'name'        => $feed->formattedValue['name'],
            'status'      => 'success',
            'created_at'  => current_time('mysql'),
            'updated_at'  => current_time('mysql')
        ]);
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}

function fluentform_after_submission_api_response_failed($form, $entryId, $data, $feed, $res, $msg = '')
{
    try {

        $isDev = wpFluentForm()->getEnv() != 'production';
        if (!apply_filters('fluentform_api_failed_log', $isDev, $form, $feed)) return;

        wpFluent()->table('fluentform_submission_meta')->insert([
            'response_id' => $entryId,
            'form_id'     => $form->id,
            'meta_key'    => 'api_log',
            'value'       => json_encode($res),
            'name'        => $feed->formattedValue['name'],
            'status'      => 'failed',
            'created_at'  => current_time('mysql'),
            'updated_at'  => current_time('mysql'),
        ]);
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}


$app->bindInstance(
    'fluentFormAsyncRequest',
    new \FluentForm\App\Services\WPAsync\FluentFormAsyncRequest($app),
    'FluentFormAsyncRequest',
    'FluentForm\App\Services\WPAsync\FluentFormAsyncRequest'
);


$app->addAction('shutdown', function () use ($app) {
    $fluentFormAsyncRequest = $app['fluentFormAsyncRequest'];
    if ($fluentFormAsyncRequest->hasActions()) {
        $fluentFormAsyncRequest->dispatch();
    }
});

$app->addFilter('fluentform-disabled_analytics', function ($status) {
    $settings = get_option('_fluentform_global_form_settings');
    if (isset($settings['misc']['isAnalyticsDisabled']) && $settings['misc']['isAnalyticsDisabled']) {
        return true;
    }
    return $status;
});

$app->addAction('fluentform_before_form_render', function ($form) {
    do_action('fluentform_load_form_assest', $form->id);
});

$app->addAction('fluentform_load_form_assest', function ($formId) {
    // check if alreaded loaded
    if(!in_array($formId, \FluentForm\App\Helpers\Helper::$loadedForms)) {
        (new \FluentForm\App\Modules\Form\Settings\FormCssJs())->addCssJs($formId);
        \FluentForm\App\Helpers\Helper::$loadedForms[] = $formId;
        $selectedStyle = \FluentForm\App\Helpers\Helper::getFormMeta($formId, '_ff_selected_style');

        if($selectedStyle) {
            do_action('fluentform_init_custom_stylesheet', $selectedStyle, $formId);
        }
    }
});

$app->addAction('fluentform_submission_inserted', function ($insertId, $formData, $form) use ($app) {
    $notificationManager = new \FluentForm\App\Services\Integrations\GlobalNotificationManager($app);
    $notificationManager->globalNotify($insertId, $formData, $form);
}, 10, 3);

$app->addAction('fluentform_async_global_notify', function ($insertId, $feedIds, $form) use ($app) {
    $notificationManager = new \FluentForm\App\Services\Integrations\GlobalNotificationManager($app);
    $notificationManager->handleGlobalAsyncNotifications($insertId, $feedIds, $form);
}, 10, 3);

$app->addAction('init', function () use ($app) {
    new \FluentForm\App\Services\Integrations\MailChimp\MailChimpIntegration($app);
});

$app->addAction('fluentform_form_element_start', function ($form) use ($app) {
    $honeyPot = new \FluentForm\App\Modules\Form\HonetPot($app);
    $honeyPot->renderHoneyPot($form);
});

$app->addAction('fluentform_before_insert_submission', function ($insertData, $requestData, $formId) use ($app) {
    $honeyPot = new \FluentForm\App\Modules\Form\HonetPot($app);
    $honeyPot->verify($insertData, $requestData, $formId);
}, 9, 3);

add_action('ff_log_data', function ($data) use ($app) {
    $dataLogger = new \FluentForm\App\Modules\Logger\DataLogger($app);
    $dataLogger->log($data);
});

// permision based filters
add_filter('fluentform_permission_callback', function ($status, $permission) {
    return (new \FluentForm\App\Modules\Acl\RoleManager())->currentUserFormFormCapability();
}, 10, 2);

// widgets
add_action('widgets_init', function () {
    register_widget('FluentForm\App\Modules\Widgets\SidebarWidgets');
});

add_action('wp', function () {
    global $post;

    if(!is_a($post, 'WP_Post')) {
        return;
    }

    $fluentFormIds = get_post_meta($post->ID, '_has_fluentform', true);

    if ($fluentFormIds && is_array($fluentFormIds)) {
        foreach ($fluentFormIds as $formId) {
            do_action('fluentform_load_form_assest', $formId);
        }
    }
});

add_filter('fluentform_validate_input_item_input_email', function ($validation, $field, $formData, $fields, $form) {
    if(\FluentForm\Framework\Helpers\ArrayHelper::get($field, 'raw.settings.is_unique') == 'yes') {
        $fieldName = \FluentForm\Framework\Helpers\ArrayHelper::get($field, 'name');

        if($inputValue = \FluentForm\Framework\Helpers\ArrayHelper::get($formData, $fieldName)) {
            $exist = wpFluent()->table('fluentform_entry_details')
                ->where('form_id', $form->id)
                ->where('field_name', $fieldName)
                ->where('field_value', $inputValue)
                ->first();
            if($exist) {
                return [
                    'unique' => \FluentForm\Framework\Helpers\ArrayHelper::get($field, 'raw.settings.unique_validation_message')
                ];
            }
        }
    }

    return $validation;
}, 10, 5);