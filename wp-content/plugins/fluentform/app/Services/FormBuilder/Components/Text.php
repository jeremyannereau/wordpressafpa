<?php

namespace FluentForm\App\Services\FormBuilder\Components;

use FluentForm\Framework\Helpers\ArrayHelper;

class Text extends BaseComponent
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

        // </mask input>
		if ( isset($data['settings']['temp_mask']) && $data['settings']['temp_mask'] != 'custom' ) {
			$data['attributes']['data-mask'] = $data['settings']['temp_mask'];
		}

        if(isset($data['attributes']['data-mask'])) {
            wp_enqueue_script(
                'jquery-mask',
                $this->app->publicUrl('libs/jquery.mask.min.js'),
                array('jquery'),
                false,
                true
            );
        }

        if($data['element'] == 'input_number') {
            if(
                ArrayHelper::get($data, 'settings.calculation_settings.status') &&
                $formula = ArrayHelper::get($data, 'settings.calculation_settings.formula')
            ) {
                $data['attributes']['data-calculation_formula'] = $formula;
                $data['attributes']['class'] .= ' ff_has_formula';
                $data['attributes']['readonly'] = true;
                $data['attributes']['type'] = 'text';

                add_filter('fluentform_form_class', function ($css_class , $targetForm) use ($form) {
                    if($targetForm->id == $form->id) {
                        $css_class .= ' ff_calc_form';
                    }
                    return $css_class;
                }, 10, 2);

                do_action('ff_rendering_calculation_form', $form, $data);
            }

            if($step = ArrayHelper::get($data, 'settings.number_step')) {
                $data['attributes']['step'] =  $step;
            }
            
            if($min = ArrayHelper::get($data, 'settings.validation_rules.min.value')) {
                $data['attributes']['min'] =  $min;
            }

            if($max = ArrayHelper::get($data, 'settings.validation_rules.max.value')) {
                $data['attributes']['max'] =  $max;
            }
        }


        // For hidden input
		if ($data['attributes']['type'] == 'hidden') {
			echo "<input ".$this->buildAttributes($data['attributes'], $form).">";
			return;
		}

        if($tabIndex = \FluentForm\App\Helpers\Helper::getNextTabIndex()) {
            $data['attributes']['tabindex'] = $tabIndex;
        }


        $data['attributes']['class'] = @trim('ff-el-form-control '. $data['attributes']['class']);
        $data['attributes']['id'] = $this->makeElementId($data, $form);

		$elMarkup = $this->buildInputGroup($data, $form);

		$html =  $this->buildElementMarkup($elMarkup, $data, $form);
        echo apply_filters('fluenform_rendering_field_html_'.$elementName, $html, $data, $form);
    }

    private function buildInputGroup($data, $form)
    {
        $input =  "<input ".$this->buildAttributes($data['attributes'], $form).">";
        $prefix = ArrayHelper::get($data, 'settings.prefix_label');
        $suffix = ArrayHelper::get($data, 'settings.suffix_label');
        if($prefix || $suffix) {
            $wrapper = '<div class="ff_input-group">';
            if($prefix) {
                $wrapper .= '<div class="ff_input-group-prepend"><span class="ff_input-group-text">'.$prefix.'</span></div>';
            }
            $wrapper .= $input;
            if($suffix) {
                $wrapper .= '<div class="ff_input-group-append"><span class="ff_input-group-text">'.$suffix.'</span></div>';
            }
            $wrapper .= '</div>';
            return $wrapper;
        }
        return $input;
    }

}
