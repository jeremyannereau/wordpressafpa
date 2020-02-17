<?php

namespace FluentForm\App\Services\WPAsync;

use FluentForm\Framework\Foundation\Application;

class FluentFormAsyncRequest extends WPAsyncRequest
{
    /**
     * $prefix The prefix for the identifier
     * @var string
     */
    protected $prefix = 'fluentform';

    /**
     * $action The action for the identifier
     * @var string
     */
    protected $action = 'async_request';

    /**
     * $actions Actions to be fired when an async request is sent
     * @var array
     */
    protected $actions = array();

    /**
     * $app Instance of Application/Framework
     * @var FluentForm\Framework\Foundation\Application
     */
    protected $app = null;

    /**
     * Construct the Object
     * @param FluentForm\Framework\Foundation\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        parent::__construct();
    }

    /**
     * Add the action to be fired when an async request is sent
     * @return void
     */
    public function shouldBeAsync($action, $data)
    {
        $this->actions = array_unique(
            array_filter(
                array_merge($this->actions, (array)$action)
            )
        );
        $data['__fluentform_async_request_actions'] = $this->actions;
        $data = array_merge($data, $this->data);
        $this->data($data);
    }

    /**
     * Get the actions to be fired when an async request is sent
     * @return int
     */
    public function hasActions()
    {
        return count($this->actions);
    }

    /**
     * Handle the async request
     * @return void
     */
    protected function handle()
    {
        $actions = $this->app['request']->get('__fluentform_async_request_actions');
        if (!$actions) {
            return;
        }

        $form = wpFluent()->table('fluentform_forms')->find(intval($_POST['form_id']));
        $entryId = intval($_POST['entry_id']);
        foreach ($actions as $action) {
            try {
                $this->app->doAction(
                    $action,
                    intval($entryId),
                    $_POST['feed_ids'],
                    $form
                );
            } catch (\Exception $exception) {

            }
        }

        do_action('fluentform_global_notify_completed', $entryId, $form);
        wp_die();
    }
}
