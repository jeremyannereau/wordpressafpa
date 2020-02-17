<?php

namespace FluentForm\App\Services\FormBuilder\Components;

class TermsAndConditions extends BaseComponent
{
    /**
     * Compile and echo the html element
     * @param  array $data [element data]
     * @param  stdClass $form [Form Object]
     * @return viod
     */
    public function compile($data, $form)
    {
        $elementName = $data['element'];
        $data = apply_filters('fluenform_rendering_field_data_' . $elementName, $data, $form);

        $hasConditions = $this->hasConditions($data) ? 'has-conditions' : '';

        $cls = trim(
            $this->getDefaultContainerClass()
            . ' ' . @$data['settings']['container_class']
            . ' ' . $hasConditions
            .' ff-el-input--content'
        );

        $uniqueId = $this->getUniqueId($data['attributes']['name']);

        $data['attributes']['id'] = $uniqueId;
        $data['attributes']['class'] = trim(
            'ff-el-form-check-input ' .
            $data['attributes']['class']
        );

        if($tabIndex = \FluentForm\App\Helpers\Helper::getNextTabIndex()) {
            $data['attributes']['tabindex'] = $tabIndex;
        }

        $atts = $this->buildAttributes($data['attributes']);
        $checkbox = '';
        if ($data['settings']['has_checkbox']) {
            $checkbox = "<input {$atts} value='on'>";
        }

        $html = "<div class='{$cls}'>";
        $html .= "<div class='ff-el-form-check'>";
        $html .= "<label class='ff-el-form-check-label' for={$uniqueId}>";
        $html .= "{$checkbox} {$data['settings']['tnc_html']}";
        $html .= "</label>";
        $html .= "</div>";
        $html .= "</div>";
        echo apply_filters('fluenform_rendering_field_html_'.$elementName, $html, $data, $form);
    }
}
