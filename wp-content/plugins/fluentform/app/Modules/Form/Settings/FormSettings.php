<?php

namespace FluentForm\App\Modules\Form\Settings;

use FluentForm\App\Modules\Acl\Acl;
use FluentForm\Framework\Helpers\ArrayHelper;
use FluentForm\Framework\Foundation\Application;
use FluentForm\App\Modules\Form\Settings\Validator\Validator;

class FormSettings
{
    /**
     * @var \FluentForm\Framework\Request\Request
     */
    private $request;

    /**
     * @var int form ID.
     */
    private $formId;

    /**
     * The settings (fluentform_form_meta) query builder.
     *
     * @var \WpFluent\QueryBuilder\QueryBuilderHandler
     */
    private $settingsQuery;

    /**
     * Construct the object
     * @throws \Exception
     */
    public function __construct(Application $application)
    {
        $this->request = $application->request;

        $this->formId = intval($this->request->get('form_id'));

        $this->settingsQuery = wpFluent()->table('fluentform_form_meta')->where('form_id', $this->formId);
    }

    /**
     * Get settings for a particular form by id
     * @return void
     */
    public function index()
    {
        $metaKey = sanitize_text_field($this->request->get('meta_key'));

        // We'll always try to get a collection for a given meta key.
        // Acknowledging that a certain meta key can have multiple
        // results. The developer using the api knows beforehand
        // that whether the expected result contains multiple
        // or one value. The developer will access that way.
        $query = $this->settingsQuery->where('meta_key', $metaKey);
        
        $result = $query->get();

        foreach ($result as $item) {
            $item->value = json_decode($item->value, true);
        }

        $result = apply_filters('fluentform_settings_formSettings', $result);

        wp_send_json_success(['result' => $result], 200);
    }

    /**
     * Save settings/meta for a form in database
     */
    public function store()
    {
        $value = $this->request->get('value', '');

        $valueArray = $value ? json_decode($value, true) : [];
        
        $key = sanitize_text_field($this->request->get('meta_key'));

        if ($key == 'formSettings') {
            Validator::validate(
                'confirmations', ArrayHelper::get(
                    $valueArray, 'confirmation', []
                )
            );
        } else {
            Validator::validate($key, $valueArray);
        }

        $data = [
            'meta_key' => $key,
            'value'    => $value,
            'form_id'  => $this->formId
        ];

        // If the request has an valid id field it's safe to assume
        // that the user wants to update an existing settings.
        // So, we'll proceed to do so by finding it first.
        $id = intval($this->request->get('id'));

        if ($id) {
            $settings = $this->settingsQuery->find($id);
        }

        if (isset($settings)) {
            $this->settingsQuery->where('id', $settings->id)->update($data);
            $insertId = $settings->id;
        } else {
            $insertId = $this->settingsQuery->insert($data);
        }

        wp_send_json_success([
            'message'  => __('Settings has been saved.', 'fluentform'),
            'settings' => json_decode($value, true),
            'id'       => $insertId
        ], 200);
    }

    /**
     * Delete settings/meta from database for a given form
     * @return void
     */
    public function remove()
    {
        $id = intval($this->request->get('id'));

        $this->settingsQuery->where('id', $id)->delete();

        wp_send_json([], 200);
    }
}
