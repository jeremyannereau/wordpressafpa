<?php

namespace FluentForm\App\Services\FormBuilder\Components;

use FluentForm\Framework\Helpers\ArrayHelper;

class Container extends BaseComponent
{
	/**
	 * Max columns for container
	 * @var integer
	 */
	protected $maxColumns = 12;

	/**
	 * Container column class
	 * @var string
	 */
	protected $columnClass = 'ff-t-cell';

	/**
	 * Container wrapper class
	 * @var string
	 */
	protected $wrapperClass = 'ff-t-container ff-column-container';

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

        $containerClass = ArrayHelper::get($data, 'settings.container_class');

        $conatiner_css_class = $this->wrapperClass.' ff_columns_total_'.count($data['columns']);
        if($containerClass) {
            $conatiner_css_class = $conatiner_css_class.' '.strip_tags($containerClass);
        }

        $columnClass = $this->columnClass;
		echo "<div class='".$conatiner_css_class."'>";
		if (isset($data['settings']['label'])) {
            echo "<strong>{$data['settings']['label']}</strong>";
        }
		foreach ($data['columns'] as $columnIndex => $column) {
            $newColumnClass = $columnClass.' ff-t-column-'.($columnIndex + 1);
			echo "<div class='{$newColumnClass}'>";
			foreach ($column['fields'] as $item) {
				$item = $this->app->applyFilters('fluentform_before_render_item', $item, $form);
				$this->app->doAction('fluentform_render_item_'.$item['element'], $item, $form);
			}
			echo "</div>";
		}
		echo "</div>";
	}
}
