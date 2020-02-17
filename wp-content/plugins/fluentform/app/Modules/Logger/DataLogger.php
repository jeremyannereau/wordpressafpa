<?php namespace FluentForm\App\Modules\Logger;

use FluentForm\App\Databases\Migrations\FormLogs;
use FluentForm\Framework\Foundation\Application;
use FluentForm\Framework\Helpers\ArrayHelper;

class DataLogger
{
    public $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getLogFilters()
    {
        $statuses = wpFluent()->table('fluentform_logs')
                        ->select('status')
                        ->groupBy('status')
                        ->get();
        $formattedStatuses = [];

        foreach ($statuses as $status) {
            $formattedStatuses[] = $status->status;
        }

        $components = wpFluent()->table('fluentform_logs')
            ->select('component')
            ->groupBy('component')
            ->get();

        $formattedComponents = [];
        foreach ($components as $component) {
            $formattedComponents[] = $component->component;
        }

        $forms = wpFluent()->table('fluentform_logs')
            ->select('fluentform_logs.parent_source_id', 'fluentform_forms.title')
            ->groupBy('fluentform_logs.parent_source_id')
            ->orderBy('fluentform_logs.parent_source_id', 'DESC')
            ->join('fluentform_forms', 'fluentform_forms.id', '=', 'fluentform_logs.parent_source_id')
            ->get();

        $formattedForms = [];

        foreach ($forms as $form) {
            $formattedForms[] = [
                'form_id' => $form->parent_source_id,
                'title' => $form->title
            ];;
        }

        wp_send_json_success([
            'available_statuses' => $formattedStatuses,
            'available_components' => $formattedComponents,
            'available_forms' => $formattedForms
        ]);
    }

    public function log($data)
    {
        if (!$data) {
            return;
        }
        $data['created_at'] = current_time('mysql');

        if (!get_option('fluentform_db_fluentform_logs_added')) {
            FormLogs::migrate();
        }

        return wpFluent()->table('fluentform_logs')
            ->insert($data);
    }

    public function getLogsByEntry($entry_id, $sourceType = 'submission_item')
    {
        $logs = wpFluent()->table('fluentform_logs')
            ->where('source_id', $entry_id)
            ->where('source_type', $sourceType)
            ->orderBy('id', 'DESC')
            ->get();

        $logs = apply_filters('fluentform_entry_logs', $logs, $entry_id);

        wp_send_json_success([
            'logs' => $logs
        ], 200);
    }

    public function getAllLogs()
    {
        $limit = intval($_REQUEST['per_page']);
        $pageNumber = intval($_REQUEST['page_number']);

        $skip = ($pageNumber - 1) * $limit;

        global $wpdb;
        $logsQuery = wpFluent()->table('fluentform_logs')
            ->select([
                'fluentform_logs.*'
            ])
            ->select(wpFluent()->raw( $wpdb->prefix.'fluentform_forms.title as form_title' ))
            ->join('fluentform_forms', 'fluentform_forms.id', '=', 'fluentform_logs.parent_source_id')
            ->orderBy('fluentform_logs.id', 'DESC')
            ->whereIn('fluentform_logs.source_type', ['submission_item', 'form_item']);


        if($parentSourceId = ArrayHelper::get($_REQUEST, 'parent_source_id')) {
            $logsQuery = $logsQuery->where('fluentform_logs.parent_source_id', intval($parentSourceId));
        }

        if($status = ArrayHelper::get($_REQUEST, 'status')) {
            $logsQuery = $logsQuery->where('fluentform_logs.status', $status);
        }

        if($component = ArrayHelper::get($_REQUEST, 'component')) {
            $logsQuery = $logsQuery->where('fluentform_logs.component', $component);
        }


        $logsQueryMain = $logsQuery;

            $logs = $logsQuery->offset($skip)
            ->limit($limit)
            ->get();

        $logs = apply_filters('fluentform_all_logs', $logs);

        $total = $logsQueryMain->count();

        wp_send_json_success([
            'logs'  => $logs,
            'total' => $total
        ], 200);

    }

    public function deleteLogsByIds($ids = [])
    {
        if(!$ids) {
            $ids = wp_unslash($_REQUEST['log_ids']);
        }

        if(!$ids) {
            wp_send_json_error([
                'message' => 'No selections found'
            ], 423);
        }

       wpFluent()->table('fluentform_logs')
            ->whereIn('id', $ids)
           ->delete();

        wp_send_json_success([
            'message' => __('Selected logs successfully deleted', 'fluentform')
        ], 200);
    }
}
