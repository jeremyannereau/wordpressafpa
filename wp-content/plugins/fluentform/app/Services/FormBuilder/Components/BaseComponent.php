<?php

namespace FluentForm\App\Services\FormBuilder\Components;

use FluentForm\App;
use FluentForm\Framework\Helpers\ArrayHelper;

class BaseComponent
{
    /**
     * $app Application Instance
     * @var Framework\Foundation\Application
     */
    protected $app = null;

    /**
     * Construct the base component object
     */
    public function __construct()
    {
        $this->app = App::make();
    }

    /**
     * Build unique ID concating form id and name attribute
     * @param  array  $data $form
     * @return string for id value
     */
    protected function makeElementId($data, $form)
    {
        if (isset($data['attributes']['name'])) {
            $elementName = $data['attributes']['name'];
            $elementName = str_replace(['[', ']', ' '], '_', $elementName);
            return "ff_{$form->id}_{$elementName}";
        }
    }

    /**
     * Build attributes for any html element
     * @param  array  $attributes
     * @return string [Compiled key='value' attributes]
     */
    protected function buildAttributes($attributes, $form = null)
    {
        $atts = '';
        foreach ($attributes as $key => $value) {
            if ($value || $value === 0 || $value === '0') {
                $value = htmlspecialchars($value);
                $atts .= $key.'="'.$value.'" ';
            }
        }
        return $atts;
    }

    /**
     * Extract value attribute from attribute list
     * @param  array  &$element
     * @return string
     */
    protected function extractValueFromAttributes(&$element)
    {
        $value = '';
        if(isset($element['attributes']['value'])) {
            $value = $element['attributes']['value'];
            unset($element['attributes']['value']);
        }
        return $value;
    }

    /**
     * Determine if the given element has conditions bound
     * @param  array   $element [Html element being compiled]
     * @return boolean
     */
    protected function hasConditions($element)
    {
        $conditionals = @$element['settings']['conditional_logics'];
        if (isset($conditionals['status']) && $conditionals['status']) {
            return array_filter($conditionals['conditions'], function($item) {
                return $item['field'] && $item['operator'];
            });
        }
    }

    /**
     * Generate a unique id for an element
     * @param  string $str [preix]
     * @return string [Unique id]
     */
    protected function getUniqueId($str)
    {
        return $str.'_'.md5(uniqid(mt_rand(), true));
    }

    /**
     * Get a default class for each form element wrapper
     * @return string
     */
    protected function getDefaultContainerClass()
    {
        return 'ff-el-group ';
    }

    /**
     * Get required class for form element wrapper
     * @param  array $rules [Validation rules]
     * @return mixed
     */
    protected function getRequiredClass($rules)
    {
        if (isset($rules['required'])) {
            return $rules['required']['value'] ? 'ff-el-is-required ' : '';
        }
    }

    /**
     * Get asterisk placement for the required form elements
     * @return String
     */
    protected function getAsteriskPlacement($form)
    {
        // for older version compatibility
        $asteriskPlacement = 'asterisk-left';

        if (isset($form->settings['layout']['asteriskPlacement'])) {
            $asteriskPlacement = $form->settings['layout']['asteriskPlacement'];
        }

        return $asteriskPlacement.' ';
    }

    /**
     * Generate a label for any element
     * @param  array  $data
     * @return string [label Html element]
     */
    protected function buildElementLabel($data, $form)
    {
        $helpMessage = '';
        if ($form->settings['layout']['helpMessagePlacement'] == 'with_label') {
            $helpMessage = $this->getLabelHelpMessage($data);
        }

        $id = isset($data['attributes']['id']) ? $data['attributes']['id'] : '';
        $label = isset($data['settings']['label']) ? $data['settings']['label'] : '';
        $requiredClass = $this->getRequiredClass($data['settings']['validation_rules']);
        $classes = trim('ff-el-input--label ' . $requiredClass . $this->getAsteriskPlacement($form));

        return "<div class='".$classes."'><label for='".$id."'>".$label."</label>{$helpMessage}</div>";
    }

    /**
     * Generate html/markup for any element
     * @param  string   $elMarkup [Predifined partial markup]
     * @param  array    $data
     * @param  StdClass $form     [Form object]
     * @return string   [Compiled markup]
     */
    protected function buildElementMarkup($elMarkup, $data, $form)
    {
        $hasConditions = $this->hasConditions($data) ? 'has-conditions ' : '';
        $labelPlacementClass = isset($data['settings']['label_placement'])
            ? 'ff-el-form-'.$data['settings']['label_placement'].' '
            : '';
        $labelClass = trim(
            'ff-el-input--label '.
            $this->getRequiredClass($data['settings']['validation_rules']).
            $this->getAsteriskPlacement($form)
        );

        $formGroupClass = trim(
            $this->getDefaultContainerClass().
            $labelPlacementClass.
            $hasConditions.
            ArrayHelper::get($data, 'settings.container_class')
        );

        $labelHelpText = $inputHelpText = '';
        if ($form->settings['layout']['helpMessagePlacement'] == 'with_label') {
            $labelHelpText = $this->getLabelHelpMessage($data);
        } elseif ($form->settings['layout']['helpMessagePlacement'] == 'on_focus') {
            $inputHelpText = $this->getInputHelpMessage($data, 'ff-hidden');
        } else {
            $inputHelpText = $this->getInputHelpMessage($data);
        }

        $forStr = '';
        if (isset($data['attributes']['id'])) {
            $forStr = "for='{$data['attributes']['id']}'";
        }

        $labelMarkup = '';
        if (!empty($data['settings']['label'])) {
            $labelMarkup = "<div class='".$labelClass."'><label ".$forStr.">".@$data['settings']['label']."</label> {$labelHelpText}</div>";
        }

        return "<div class='".$formGroupClass."'>{$labelMarkup}<div class='ff-el-input--content'>{$elMarkup}{$inputHelpText}</div></div>";
    }

    /**
     * Generate a help message for any element beside label
     * @param  array  $data
     * @return string [Html]
     */
    protected function getLabelHelpMessage($data)
    {
        if (isset($data['settings']['help_message']) && $data['settings']['help_message'] != '') {
            return "<div class='ff-el-tooltip' data-content='{$data['settings']['help_message']}'>
                <i class='ff-icon icon-info-circle'></i>
            </div>";
        }
    }

    /**
     * Generate a help message for any element beside form element
     * @param  array  $data
     * @return string [Html]
     */
    protected function getInputHelpMessage($data, $hideClass = '')
    {
        $class = trim('ff-el-help-message '.$hideClass);

        if (isset($data['settings']['help_message']) && !empty($data['settings']['help_message'])) {
            return "<div class='{$class}'>{$data['settings']['help_message']}</div>";
        }
        return false;
    }
}
