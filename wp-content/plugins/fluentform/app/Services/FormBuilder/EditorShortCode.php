<?php

namespace FluentForm\App\Services\FormBuilder;

use FluentForm\App\Modules\Form\FormFieldsParser;

class EditorShortcode
{
    public static function getGeneralShortCodes()
    {
        return [
            'title'      => 'General Shortcodes',
            'shortcodes' => [
                '{wp.admin_email}'        => __('Admin Email', 'fluentform'),
                '{wp.site_url}'           => __('Site URL', 'fluentform'),
                '{wp.site_title}'         => __('Site Title', 'fluentform'),
                '{ip}'                    => __('IP Address', 'fluentform'),
                '{date.m/d/Y}'            => __('Date (mm/dd/yyyy)', 'fluentform'),
                '{date.d/m/Y}'            => __('Date (dd/mm/yyyy)', 'fluentform'),
                '{embed_post.ID}'         => __('Embebeded Post/Page ID', 'fluentform'),
                '{embed_post.post_title}' => __('Embebeded Post/Page Title', 'fluentform'),
                '{embed_post.permalink}'  => __('Embebeded URL', 'fluentform'),
                '{http_referer}'          => __('HTTP Referer URL', 'fluentform'),
                '{user.ID}'               => __('User ID', 'fluentform'),
                '{user.display_name}'     => __('User Display Name', 'fluentform'),
                '{user.first_name}'       => __('User First Name', 'fluentform'),
                '{user.last_name}'        => __('User Last Name', 'fluentform'),
                '{user.user_email}'       => __('User Email', 'fluentform'),
                '{user.user_login}'       => __('User Username', 'fluentform'),
                '{browser.name}'          => __('User Browser Client', 'fluentform'),
                '{browser.platform}'      => __('User Operating System', 'fluentform')
            ]
        ];
    }

    public static function getFormShortCodes($form)
    {
        $formFields = FormFieldsParser::getShortCodeInputs(
            static::getForm($form), [
            'admin_label', 'attributes', 'options'
        ]);

        $formShortCodes = [
            'shortcodes' => [],
            'title'      => 'Input Options'
        ];

        $formShortCodes['shortcodes']['{all_data}'] = 'All Submitted Data';
        foreach ($formFields as $key => $value) {
            $formShortCodes['shortcodes']['{inputs.' . $key . '}'] = $value['admin_label'];
        }

        return $formShortCodes;
    }


    public static function getSubmissionShortcodes()
    {
        $submissionProperties = [
            '{submission.id}'            => __('Submission ID', 'fluentform'),
            '{submission.serial_number}' => __('Submission Serial Number', 'fluentform'),
            '{submission.source_url}'    => __('Source URL', 'fluentform'),
            '{submission.user_id}'       => __('User Id', 'fluentform'),
            '{submission.browser}'       => __('Submitter Browser', 'fluentform'),
            '{submission.device}'        => __('Submitter Device', 'fluentform'),
            '{submission.status}'        => __('Submission Status', 'fluentform'),
            '{submission.created_at}'    => __('Submission Create Date', 'fluentform')
        ];

        return [
            'title'      => 'Entry Attributes',
            'shortcodes' => $submissionProperties
        ];
    }

    public static function getShortCodes($form)
    {
        return [
            static::getFormShortCodes($form),
            static::getGeneralShortCodes(),
            static::getSubmissionShortcodes()
        ];
    }

    public static function parse($string, $data, callable $arrayFormatter = null)
    {
        if (is_array($string)) {
            return static::parseArray($string, $data, $arrayFormatter);
        }

        return static::parseString($string, $data, $arrayFormatter);
    }

    public static function parseArray($string, $data, $arrayFormatter)
    {
        foreach ($string as $key => $value) {
            if (is_array($value)) {
                $string[$key] = static::parseArray($value, $data, $arrayFormatter);
            } else {
                $string[$key] = static::parseString($value, $data, $arrayFormatter);
            }
        }

        return $string;
    }

    public static function parseString($string, $data, callable $arrayFormatter = null)
    {
        return preg_replace_callback('/{+(.*?)}/', function ($matches) use (&$data, &$arrayFormatter) {
            if (!isset($data[$matches[1]])) {
                return $matches[0];
            } elseif (is_array($value = $data[$matches[1]])) {
                return is_callable($arrayFormatter) ? $arrayFormatter($value) : implode(', ', $value);
            }

            return $data[$matches[1]];

        }, $string);
    }

    protected static function getForm($form)
    {
        if (is_object($form)) {
            return $form;
        }

        return wpFluent()->table('fluentform_forms')->find($form);
    }
}
