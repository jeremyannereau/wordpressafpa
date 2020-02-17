<?php
$page = sanitize_text_field($_GET['page']);
?>
<div class="ff_form_main_nav">
    <span class="plugin-name">
        <?php _e('Fluent Forms', 'fluentform'); ?> <?php if(defined('FLUENTFORMPRO')): ?>Pro<?php endif; ?>
    </span>
    <a href="<?php echo admin_url('admin.php?page=fluent_forms'); ?>" class="ninja-tab <?php echo ($page == 'fluent_forms') ? 'ninja-tab-active' : '' ?>">
        <?php _e('All Forms', 'fluentform'); ?>
    </a>
    <a href="admin.php?page=fluent_forms#entries" class="ninja-tab">
        <?php _e('All Entries', 'fluentform'); ?>
    </a>
    <a href="<?php echo admin_url('admin.php?page=fluent_forms_settings'); ?>" class="ninja-tab <?php echo ($page == 'fluent_forms_settings') ? 'ninja-tab-active' : '' ?>">
        <?php _e('Settings', 'fluentform'); ?>
    </a>
    <a href="<?php echo admin_url('admin.php?page=fluent_form_add_ons'); ?>" class="ninja-tab <?php echo ($page == 'fluent_form_add_ons') ? 'ninja-tab-active' : '' ?>">
        <?php _e('Modules', 'fluentform'); ?>
    </a>
    <a href="<?php echo admin_url('admin.php?page=fluent_forms_docs'); ?>" class="ninja-tab <?php echo ($page == 'fluent_forms_docs') ? 'ninja-tab-active' : '' ?>">
        <?php _e('Support', 'fluentform'); ?>
    </a>
    <?php if(!defined('FLUENTFORMPRO')): ?>
    <a target="_blank" rel="noopener" href="https://wpmanageninja.com/downloads/fluentform-pro-add-on/?utm_source=plugin&utm_medium=wp_install&utm_campaign=ff_upgrade" class="ninja-tab buy_pro_tab">
        <?php _e('Upgrade to Pro', 'fluentform'); ?>
    </a>
    <?php endif; ?>
</div>