<?php

namespace FluentForm\App\Services\FormBuilder\Components;

use FluentForm\App\Helpers\Helper;
use FluentForm\Framework\Helpers\ArrayHelper;

class Select extends BaseComponent
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
        $data = apply_filters('fluenform_rendering_field_data_'.$elementName, $data, $form);

        $data['attributes']['id'] = $this->makeElementId($data, $form);
		
		if (ArrayHelper::get($data['attributes'], 'multiple')) {
			$data['attributes']['name'] = $data['attributes']['name'].'[]';
			wp_enqueue_script('selectWoo');
			wp_enqueue_style('select2');
            $data['attributes']['class'] .= ' ff_has_multi_select';
		}

        $data['attributes']['data-calc_value'] = 0;

        $data['attributes']['class'] = trim('ff-el-form-control ' . $data['attributes']['class']);

        if($tabIndex = \FluentForm\App\Helpers\Helper::getNextTabIndex()) {
            $data['attributes']['tabindex'] = $tabIndex;
        }

        $defaultValues = (array) $this->extractValueFromAttributes($data);

        $elMarkup = "<select ".$this->buildAttributes($data['attributes']).">".$this->buildOptions($data, $defaultValues)."</select>";

        $html = $this->buildElementMarkup($elMarkup, $data, $form);
        echo apply_filters('fluenform_rendering_field_html_'.$elementName, $html, $data, $form);
    }

	/**
	 * Build options for select
	 * @param  array $options
	 * @return string/html [compiled options]
	 */
	protected function buildOptions($data, $defaultValues)
	{
        if(!$formattedOptions = ArrayHelper::get($data, 'settings.advanced_options')) {
            $options = ArrayHelper::get($data, 'options', []);
            $formattedOptions = [];
            foreach($options as $value => $label) {
                $formattedOptions[] = [
                    'label' => $label,
                    'value' => $value,
                    'calc_value' => ''
                ];
            }
        }

        $opts = '';
        if (!empty($data['settings']['placeholder'])) {
            $opts .= '<option value="">'.$data['settings']['placeholder'].'</option>';
        }

		foreach ($formattedOptions as $option) {
			if(in_array($option['value'], $defaultValues)) {
				$selected = 'selected';
			} else {
				$selected = '';
			}

			$atts = [
			    'data-calc_value' =>  ArrayHelper::get($option, 'calc_value'),
                'value' => ArrayHelper::get($option, 'value'),
            ];

			$opts .="<option ".$this->buildAttributes($atts)." {$selected}>{$option['label']}</option>";
		}
		return $opts;
	}
}
