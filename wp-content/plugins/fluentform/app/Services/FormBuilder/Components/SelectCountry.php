<?php

namespace FluentForm\App\Services\FormBuilder\Components;

use FluentForm\App;
use FluentForm\App\Helpers\Helper;
use FluentForm\Framework\Helpers\ArrayHelper;

class SelectCountry extends BaseComponent
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

        $data = $this->loadCountries($data);
		$defaultValues = (array) $this->extractValueFromAttributes($data);
        $data['attributes']['class'] = trim('ff-el-form-control ' . $data['attributes']['class']);
        $data['attributes']['id'] = $this->makeElementId($data, $form);

        if($tabIndex = \FluentForm\App\Helpers\Helper::getNextTabIndex()) {
            $data['attributes']['tabindex'] = $tabIndex;
        }


        $elMarkup = "<select ".$this->buildAttributes($data['attributes']).">".$this->buildOptions($data['options'], $defaultValues)."</select>";

		$html = $this->buildElementMarkup($elMarkup, $data, $form);
        echo apply_filters('fluenform_rendering_field_html_'.$elementName, $html, $data, $form);
    }

	/**
	 * Load countt list from file
	 * @param  array $data
	 * @return array
	 */
	protected function loadCountries($data)
	{
		$app = App::make();
		$data['options'] = array();
		$activeList = $data['settings']['country_list']['active_list'];
		$countries = $app->load($app->appPath('Services/FormBuilder/CountryNames.php'));

		if ($activeList == 'all') {
			$data['options'] = $countries;
		} elseif ($activeList == 'visible_list') {
			foreach ($data['settings']['country_list'][$activeList] as $value) {
				$data['options'][$value] = $countries[$value];	
			}
		} elseif ($activeList == 'hidden_list') {
			$data['options'] = $countries;
			foreach ($data['settings']['country_list'][$activeList] as $value) {
				unset($data['options'][$value]);
			}
		}

		$placeholder = ArrayHelper::get($data, 'attributes.placeholder');
		
		$data['options'] = array_merge(['' => $placeholder], $data['options']);

		return $data;
	}

	/**
	 * Build options for country list/select
	 * @param  array $options
	 * @return string/html [compiled options]
	 */
	protected function buildOptions($options, $defaultValues)
	{
		$opts = '';
		foreach ($options as $value => $label) {
			if(in_array($value, $defaultValues)) {
				$selected = 'selected';
			} else {
				$selected = '';
			}
			$opts .="<option value='{$value}' {$selected}>{$label}</option>";
		}
		return $opts;
	}
}
