<?php

namespace FluentForm\App\Modules\Form;

use FluentForm\App\Databases\Migrations\FormSubmissionDetails;
use FluentForm\App\Modules\Activator;
use FluentForm\App\Modules\Entries\Entries;
use FluentForm\App\Modules\ReCaptcha\ReCaptcha;
use FluentForm\App\Services\Browser\Browser;
use FluentForm\App\Services\FormBuilder\ShortCodeParser;
use FluentForm\Framework\Foundation\Application;
use FluentForm\Framework\Helpers\ArrayHelper as Arr;
use FluentForm\Framework\Helpers\ArrayHelper;

class FormHandler
{
    /**
     * @var \FluentForm\Framework\Foundation\Application
     */
    protected $app;

    /**
     * @var \FluentForm\Framework\Request\Request
     */
    protected $request;

    /**
     * @var array $formData
     */
    protected $formData;

    /**
     * The fluent form object.
     *
     * @var \stdClass
     */
    protected $form;

    /**
     * Form Handler constructor.
     *
     * @param \FluentForm\Framework\Foundation\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->request = $app->request;
    }

    /**
     * Set the form using it's ID.
     *
     * @param $formId
     * @return $this
     */
    public function setForm($formId)
    {
        $this->form = wpFluent()->table('fluentform_forms')->find($formId);
        return $this;
    }

    /**
     * Handle form submition
     */
    public function onSubmit()
    {
        // Parse the url encoded data from the request object.
        parse_str($this->app->request->get('data'), $data);

        // Merge it back again to the request object.
        $this->app->request->merge(['data' => $data]);

        $formId = intval($this->app->request->get('form_id'));

        $this->setForm($formId);

        // Parse the form and get the flat inputs with validations.
        $fields = FormFieldsParser::getInputs($this->form, ['rules', 'raw']);

        // Sanitize the data properly.
        $this->formData = fluentFormSanitizer($data, null, $fields);

        // Now validate the data using the previous validations.
        $this->validate($fields);

        // Prepare the data to be inserted to the DB.
        $insertData = $this->prepareInsertData();

        do_action('fluentform_before_insert_submission', $insertData, $data,  $formId);

        $insertId = wpFluent()->table('fluentform_submissions')->insert($insertData);

        if ($insertId) {
            ob_start();
            $entries = new Entries();
            $entries->recordEntryDetails($insertId, $this->form->id, $this->formData);
            $isError = ob_get_clean();
            if($isError) {
                FormSubmissionDetails::migrate();
            }
        }

        $returnData = $this->getReturnData($insertId);

        try {
            $this->app->doAction(
                'fluentform_submission_inserted',
                $insertId,
                $this->formData,
                $this->form
            );

            $this->app->doAction(
                'fluentform_submission_inserted_'.$this->form->type.'_form',
                $insertId,
                $this->formData,
                $this->form
            );
            
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                return $e;
            }
        }

        $this->sendResponse($insertId, $returnData);
    }

    /**
     * Prepare response and do actions/filters
     * and send the response to the client.
     *
     * @param int $insertId
     */
    private function sendResponse($insertId, $returnData)
    {
        do_action('fluenform_before_submission_confirmation', $insertId, $this->formData, $this->form);
        wp_send_json_success([
            'insert_id' => $insertId,
            'result'    => $returnData
        ], 200);
    }


    private function getReturnData($insertId)
    {
        $confirmation = apply_filters(
            'fluentform_form_submission_confirmation',
            $this->form->settings['confirmation'],
            $this->formData,
            $this->form
        );

        if ($confirmation['redirectTo'] == 'samePage') {
            $message = ShortCodeParser::parse(
                $confirmation['messageToShow'],
                $insertId,
                $this->formData,
                $this->form
            );

            $message = $message ? $message : 'The form has been successfully submitted.';

            $returnData = [
                'message' => do_shortcode($message),
                'action'  => $confirmation['samePageFormBehavior'],
            ];

        } else {
            $redirectUrl = Arr::get($confirmation, 'customUrl');

            if ($confirmation['redirectTo'] == 'customPage') {
                $redirectUrl = get_permalink($confirmation['customPage']);
            }

            if(
                (Arr::get($confirmation, 'enable_query_string') == 'yes') &&
                Arr::get($confirmation, 'query_strings')
            ) {
                if(strpos($redirectUrl, '?')) {
                    $redirectUrl .= '&'. Arr::get($confirmation, 'query_strings');
                } else {
                    $redirectUrl .= '?'. Arr::get($confirmation, 'query_strings');
                }
            }

            $redirectUrl = ShortCodeParser::parse(
                $redirectUrl,
                $insertId,
                $this->formData,
                $this->form
            );

            $message = ShortCodeParser::parse(
                ArrayHelper::get($confirmation, 'redirectMessage', ''),
                $insertId,
                $this->formData,
                $this->form
            );

            $returnData = [
                'redirectUrl' => $redirectUrl,
                'message' => $message
            ];
        }

        return $this->app->applyFilters(
            'fluentform_submission_confirmation',
            $returnData,
            $this->form,
            $confirmation
        );
    }

    /**
     * Validate form data.
     *
     * @param $fields
     * @return bool
     */
    private function validate(&$fields)
    {
        $this->validateRestrictions($fields);

        $this->validateNonce();

        $this->validateReCaptcha();

        $validations = FormFieldsParser::getValidations($this->form, $this->formData, $fields);

        // Fire an event so that one can hook into it to work with the rules & messages.
        $validations = $this->app->applyFilters('fluentform_validations', $validations, $this->form);

        $validator = \FluentValidator\Validator::make($this->formData, $validations[0], $validations[1]);

        $errors = [];
        if ($validator->validate()->fails()) {
            foreach ($validator->errors() as $attribute => $rules) {
                $position = strpos($attribute, ']');

                if ($position) {
                    $attribute = substr($attribute, 0, strpos($attribute, ']') + 1);
                }

                $errors[$attribute] = $rules;
            }

            // Fire an event so that one can hook into it to work with the errors.
            $errors = $this->app->applyFilters('fluentform_validation_error', $errors, $this->form, $fields);
        }

        foreach ($fields as $fieldKey => $field) {
            $field['data_key'] = $fieldKey;
            $inputName = \FluentForm\Framework\Helpers\ArrayHelper::get($field, 'raw.attributes.name');
            $field['name'] = $inputName;
            $error = apply_filters('fluentform_validate_input_item_' . $field['element'], '', $field, $this->formData, $fields, $this->form, $errors);
            if ($error) {
                if(empty($errors[$inputName])) {
                    $errors[$inputName] = [];
                }
                $errors[$inputName] = array_merge($error, $errors[$inputName]);
            }
        }

        if ($errors) {
            wp_send_json(['errors' => $errors], 422);
        }

        return true;
    }

    /**
     * Validate nonce.
     */
    protected function validateNonce()
    {
        $formId = $this->form->id;

        $shouldVerifyNonce = $this->app->applyFilters('fluentform_nonce_verify', false, $formId);

        if ($shouldVerifyNonce) {
            $nonce = Arr::get($this->formData, '_fluentform_' . $formId . '_fluentformnonce');
            if (!wp_verify_nonce($nonce, 'fluentform-submit-form')) {
                $errors = $this->app->applyFilters('fluentForm_nonce_error', [
                    '_fluentformnonce' => [
                        __('Nonce verification failed, please try again.', 'fluentform')
                    ]
                ]);
                wp_send_json(['errors' => $errors], 422);
            }
        }
    }

    /**
     * Validate reCaptcha.
     */
    private function validateReCaptcha()
    {
        if (FormFieldsParser::hasElement($this->form, 'recaptcha')) {
            $isValid = ReCaptcha::validate(Arr::get($this->formData, 'g-recaptcha-response'));

            if (!$isValid) {
                wp_send_json([
                    'errors' => [
                        'g-recaptcha-response' => [
                            __('reCaptcha verification failed, please try again.', 'fluentform')
                        ]
                    ]
                ], 422);
            }
        }
    }

    /**
     * Validate form data based on the form restrictions settings.
     *
     * @param $fields
     */
    private function validateRestrictions(&$fields)
    {
        $formSettings = wpFluent()->table('fluentform_form_meta')
            ->where('form_id', $this->form->id)
            ->where('meta_key', 'formSettings')
            ->first();

        $this->form->settings = $formSettings ? json_decode($formSettings->value, true) : [];

        $isAllowed = [
            'status'  => true,
            'message' => ''
        ];

        // This will check the following restriction settings.
        // 1. limitNumberOfEntries
        // 2. scheduleForm
        // 3. requireLogin
        $isAllowed = apply_filters('fluentform_is_form_renderable', $isAllowed, $this->form);

        if (!$isAllowed['status']) {
            wp_send_json([
                'errors' => [
                    'restricted' => [
                        __($isAllowed['message'], 'fluentform')
                    ]
                ]
            ], 422);
        }

        // Since we are here, we should now handle if the form should be allowed to submit empty.
        $restrictions = Arr::get($this->form->settings, 'restrictions.denyEmptySubmission', []);

        $this->handleDenyEmptySubmission($restrictions, $fields);
    }

    /**
     * Handle response when empty form submission is not allowed.
     *
     * @param array $settings
     * @param $fields
     */
    private function handleDenyEmptySubmission($settings = [], &$fields)
    {
        // Determine whether empty form submission is allowed or not.
        if (Arr::get($settings, 'enabled')) {
            // confirm this form has no required fields.
            if (!FormFieldsParser::hasRequiredFields($this->form, $fields)) {
                // Filter out the form data which doesn't have values.
                $filteredFormData = array_filter(
                    // Filter out the other meta fields that aren't actual inputs.
                    array_intersect_key($this->formData, $fields)
                );

                // TODO: Extract this function into global functions file...
                $arrayFilterRecursive = function ($array) use (&$arrayFilterRecursive) {
                    foreach ($array as $key => $item) {
                        is_array($item) && $array[$key] = $arrayFilterRecursive($item);
                        if (empty($array[$key])) {
                            unset($array[$key]);
                        }
                    }
                    return $array;
                };

                if (!count($arrayFilterRecursive($filteredFormData))) {
                    wp_send_json([
                        'errors' => [
                            'restricted' => [
                                __(
                                    !($m = Arr::get($settings, 'message'))
                                        ? 'Sorry! You can\'t submit an empty form.'
                                        : $m,
                                    'fluentform'
                                )
                            ]
                        ]
                    ], 422);
                }
            }
        }
    }

    /**
     * Prepare the data to be inserted to the database.
     *
     * @param  boolean $formData
     * @return array
     */
    public function prepareInsertData($formData = false)
    {
        $formId = $this->form->id;

        if (!$formData) {
            $formData = $this->formData;
        }

        $previousItem = wpFluent()->table('fluentform_submissions')
            ->where('form_id', $formId)
            ->orderBy('id', 'DESC')
            ->first();

        $serialNumber = 1;

        if ($previousItem) {
            $serialNumber = $previousItem->serial_number + 1;
        }

        $browser = new Browser;

        $inputConfigs = FormFieldsParser::getEntryInputs($this->form, array('admin_label', 'raw'));

        $this->formData = apply_filters('fluentform_insert_response_data', $formData, $formId, $inputConfigs);

        $ipAddress = $this->app->request->getIp();

        if ((defined('FLUENTFROM_DISABLE_IP_LOGGING') && FLUENTFROM_DISABLE_IP_LOGGING) || apply_filters('fluentform_disable_ip_logging', false, $formId)) {
            $ipAddress = false;
        }

        $response = [
            'form_id'       => $formId,
            'serial_number' => $serialNumber,
            'response'      => json_encode($this->formData),
            'source_url'    => site_url(Arr::get($this->formData, '_wp_http_referer')),
            'user_id'       => get_current_user_id(),
            'browser'       => $browser->getBrowser(),
            'device'        => $browser->getPlatform(),
            'ip'            => $ipAddress,
            'created_at'    => current_time('mysql'),
            'updated_at'    => current_time('mysql')
        ];

        return apply_filters('fluentform_filter_insert_data', $response);
    }

    /**
     * Delegate the validation rules & messages to the
     * ones that the validation library recognizes.
     *
     * @param  $rules
     * @param  $messages
     * @return array
     */
    protected function delegateValidations($rules, $messages, $search = [], $replace = [])
    {
        $search = $search ?: ['max_file_size', 'allowed_file_types'];
        $replace = $replace ?: ['max', 'mimes'];

        foreach ($rules as &$rule) {
            $rule = str_replace($search, $replace, $rule);
        }

        foreach ($messages as $key => $message) {
            $newKey = str_replace($search, $replace, $key);
            $messages[$newKey] = $message;
            unset($messages[$key]);
        }

        return [$rules, $messages];
    }

}
