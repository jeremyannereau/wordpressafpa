<?php

namespace FluentForm\App\Services\FormBuilder\Components;

use FluentForm\Framework\Helpers\ArrayHelper;

class CustomHtml extends BaseComponent
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

        $hasConditions = $this->hasConditions($data) ? 'has-conditions' : '';
		$cls = trim($this->getDefaultContainerClass() .' '.$hasConditions);

		if($containerClass = ArrayHelper::get($data, 'settings.container_class')) {
            $cls .= ' '.$containerClass;
        }

		$atts = $this->buildAttributes(
			\FluentForm\Framework\Helpers\ArrayHelper::except($data['attributes'], 'name')
		);
		$html = "<div class='{$cls}' {$atts}>{$data['settings']['html_codes']}</div>";
        echo apply_filters('fluenform_rendering_field_html_'.$elementName, $html, $data, $form);
    }
}
