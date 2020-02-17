<?php

namespace FluentForm\App\Databases\Migrations;

class FormTransactions
{
    /**
     * Migrate the table.
     *
     * @return void
     */
    public static function migrate()
    {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();

        $table = $wpdb->prefix.'fluentform_transactions';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $sql = "CREATE TABLE $table (
			  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			  `form_id` INT UNSIGNED NULL,
			  `response_id` BIGINT(20) UNSIGNED NULL,
			  `currency` VARCHAR(45) NULL,
			  `charge_id` VARCHAR(45) NULL,
			  `card_type` VARCHAR(45) NULL,
			  `amount` VARCHAR(45) NULL,
			  `payment_method` VARCHAR(45) NULL,
			  `payment_status` VARCHAR(45) NULL,
			  `payment_type` VARCHAR(45) NULL,
			  `payer_email` VARCHAR(255) NULL,
			  `user_id` INT UNSIGNED NULL,
			  `created_at` TIMESTAMP NULL,
			  `updated_at` TIMESTAMP NULL,
			  PRIMARY KEY (`id`)) $charsetCollate;";

            require_once(ABSPATH.'wp-admin/includes/upgrade.php');

            dbDelta($sql);
        }
    }
}