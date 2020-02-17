<?php

namespace FluentForm\App\Services\FormBuilder\Notifications;

use FluentForm\Framework\Foundation\Application;

class EmailNotificationActions
{
    protected $app = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function register()
    {
        add_filter('fluentform_notifying_async_email_notifications', '__return_false', 9);
        if (fluentFormIsHandlingSubmission()) {
            add_filter('fluentform_global_notification_active_types', function ($types) {
                $types['notifications'] = 'email_notifications';
                return $types;
            });
            add_action('fluentform_integration_notify_notifications', array($this, 'notify'), 10, 4);
        }
    }

    public function notify($feed, $formData, $entry, $form)
    {
        $notifier = $this->app->make(
            'FluentForm\App\Services\FormBuilder\Notifications\EmailNotification'
        );
        $notifier->notify($feed['processedValues'], $formData, $form, $entry->id);
    }
}
