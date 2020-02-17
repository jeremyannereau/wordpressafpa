<?php

namespace FluentForm\App\Modules\Entries;

use FluentForm\App\Modules\Form\FormDataParser;
use FluentForm\App\Modules\Form\FormFieldsParser;
use FluentForm\Framework\Foundation\Application;
use FluentForm\Framework\Helpers\ArrayHelper as Arr;

class Export
{
    /**
     * @var \FluentForm\Framework\Request\Request
     */
    protected $request;

    protected $app;

    /**
     * Export constructor.
     *
     * @param \FluentForm\Framework\Foundation\Application $application
     */
    public function __construct(Application $application)
    {
        $this->request = $application->request;
        $this->app = $application;
    }

    /**
     * Exports form entries as CSV.
     *
     * @todo:: refactor.
     */
    public function index()
    {
        if (!defined('FLUENTFORM_DOING_CSV_EXPORT')) {
            define('FLUENTFORM_DOING_CSV_EXPORT', true);
        }

        $formId = intval($this->request->get('form_id'));

        $form = wpFluent()->table('fluentform_forms')->find($formId);

        if (!$form) {
            exit('No Form Found');
        }

        $type = sanitize_text_field($this->request->get('format', 'csv'));
        if (!in_array($type, ['csv', 'ods', 'xlsx', 'json'])) {
            exit('Invalid requested format');
        }

        if ($type == 'json') {
            $this->exportAsJSON($form);
        }

        $formInputs = FormFieldsParser::getEntryInputs($form, array('admin_label', 'raw'));

        $inputLabels = FormFieldsParser::getAdminLabels($form, $formInputs);

        $submissions = $this->getSubmissions($formId);

        $submissions = FormDataParser::parseFormEntries($submissions, $form, $formInputs);
        $exportData = [];


        foreach ($submissions as $submission) {
            $submission->response = json_decode($submission->response, true);
            $temp = [];
            foreach ($inputLabels as $field => $label) {
                $temp[] = trim(wp_strip_all_tags(FormDataParser::formatValue(Arr::get($submission->user_inputs, $field))));
            }
            $temp[] = $submission->id;
            $temp[] = $submission->status;
            $temp[] = $submission->created_at;
            $exportData[] = $temp;
        }

        $extraLabels = [
            'entry_id',
            'entry_status',
            'created_at'
        ];

        $inputLabels = array_merge($inputLabels, $extraLabels);

        $data = array_merge([array_values($inputLabels)], $exportData);

        $data = apply_filters('fluentform_export_data', $data, $form, $exportData, $inputLabels);

        $fileName = sanitize_title($form->title, 'export', 'view') . '-' . date('Y-m-d');

        $this->downloadOfficeDoc($data, $type, $fileName);
    }


    private function downloadOfficeDoc($data, $type = 'csv', $fileName = null)
    {
        $data = array_map(function ($item) {
            return array_map(function ($itemValue) {
                if (is_array($itemValue)) {
                    return implode(', ', $itemValue);
                }
                return $itemValue;
            }, $item);
        }, $data);
        require_once $this->app->appPath() . 'Services/Spout/Autoloader/autoload.php';
        $fileName = ($fileName) ? $fileName . '.' . $type : 'export-data-' . date('d-m-Y') . '.' . $type;
        $writer = \Box\Spout\Writer\WriterFactory::create($type);
        $writer->openToBrowser($fileName);
        $writer->addRows($data);
        $writer->close();
        die();
    }

    private function exportAsJSON($form)
    {
        $formInputs = FormFieldsParser::getEntryInputs($form, array('admin_label', 'raw'));

        $inputLabels = FormFieldsParser::getAdminLabels($form, $formInputs);

        $submissions = $this->getSubmissions($form->id);

        $submissions = FormDataParser::parseFormEntries($submissions, $form, $formInputs);
        $exportData = [];

        foreach ($submissions as $submission) {
            $submission->response = json_decode($submission->response, true);
        }

        header('Content-disposition: attachment; filename=' . sanitize_title($form->title, 'export', 'view') . '-' . date('Y-m-d') . '.json');
        header('Content-type: application/json');
        echo json_encode($submissions);
        exit();
    }

    private function getSubmissions($formId)
    {
        $query = wpFluent()->table('fluentform_submissions')
            ->where('form_id', $formId)
            ->orderBy('id', $this->request->get('sort_by', 'DESC'));

        $status = $this->request->get('entry_type');
        $isFavourite = $this->request->get('is_favourite');

        $dateRange = $this->request->get('date_range');
        if ($dateRange) {
            $query->where('created_at', '>=', $dateRange[0] .' 00:00:01');
            $query->where('created_at', '<=', $dateRange[1] .' 23:59:59');
        }

        if ($isFavourite == 'yes') {
            $query->where('is_favourite', '1');
        }

        if ($status == 'trashed') {
            $query->where('status', 'trashed');
        } else if($status && $status != 'all') {
            $query->where('status', $status);
        } else {
            $query->where('status', '!=', 'trashed');
        }

        $searchString = $this->request->get('search');
        if ($searchString) {
            $query->where(function ($q) use ($searchString) {
                $q->where('id', 'LIKE', "%{$searchString}%")
                    ->orWhere('response', 'LIKE', "%{$searchString}%")
                    ->orWhere('status', 'LIKE', "%{$searchString}%")
                    ->orWhere('created_at', 'LIKE', "%{$searchString}%");
            });
        }
        return $query->get();
    }
}
