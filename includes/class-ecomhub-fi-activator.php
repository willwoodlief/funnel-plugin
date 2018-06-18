<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Ecomhub_Fi
 * @subpackage Ecomhub_Fi/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Ecomhub_Fi
 * @subpackage Ecomhub_Fi/includes
 * @author     Your Name <email@example.com>
 */
class Ecomhub_Fi_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */

	const DB_VERSION = 1.81;
	public static function activate() {
        global $wpdb;

		$installed_ver = get_option( "_ecombhub_fi_db_version" );
		if (Ecomhub_Fi_Activator::DB_VERSION != $installed_ver) {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			$charset_collate = $wpdb->get_charset_collate();

			//do main survey table
			$sql = "CREATE TABLE `{$wpdb->base_prefix}ecombhub_fi_funnels` (
              id int NOT NULL AUTO_INCREMENT,
              created_at_ts int NOT NULL,
              is_completed int NOT NULL DEFAULT 0,
              is_error int NOT NULL DEFAULT 0,
              user_id_read int DEFAULT NULL,
              order_total DECIMAL(15,5) DEFAULT NULL,
           	  order_items int DEFAULT NULL, 
              user_id_reference varchar(100) DEFAULT NULL,
              invoice_number varchar(100) DEFAULT NULL ,
              email_from_notice text DEFAULT NULL,
              email_to varchar(100) DEFAULT NULL,
              email_from varchar(100) DEFAULT NULL,
              email_subject varchar(200) DEFAULT NULL,
              email_body LONGTEXT DEFAULT NULL,
              email_attachment_files_saved LONGTEXT DEFAULT NULL ,
              email_all_recipients LONGTEXT DEFAULT NULL,
              raw_email LONGTEXT DEFAULT NULL,
              comments LONGTEXT DEFAULT NULL,
              error_message LONGTEXT DEFAULT NULL,
              error_trace LONGTEXT DEFAULT NULL,
              PRIMARY KEY  (id),
              key (created_at_ts),
              key (is_completed),
              key (user_id_read),
              key (invoice_number)
            ) $charset_collate;";


			dbDelta($sql);



			//do main survey table
			$sql = "CREATE TABLE `{$wpdb->base_prefix}ecombhub_fi_funnel_orders` (
              id int NOT NULL AUTO_INCREMENT,
              ecombhub_fi_funnel_id int  NOT NULL,
              funnel_product_id int  DEFAULT NULL,
              post_product_id int DEFAULT NULL,
              order_id int DEFAULT NULL,
              user_id int DEFAULT NULL,
              is_error int NOT NULL DEFAULT 0,
              order_total DECIMAL(15,5) DEFAULT NULL,
              payment_type varchar(20) DEFAULT NULL,
              order_output LONGTEXT DEFAULT NULL,  
              comments LONGTEXT DEFAULT NULL,
              error_message LONGTEXT DEFAULT NULL,
              error_trace LONGTEXT DEFAULT NULL,
              extra_order_product_id int DEFAULT NULL,
              extra_order_id int DEFAULT NULL,
              extra_order_output LONGTEXT DEFAULT NULL,
              extra_order_total DECIMAL(15,5) DEFAULT NULL,
              extra_order_payment_type varchar(20) DEFAULT NULL,
              extra_error_message LONGTEXT DEFAULT NULL,
              extra_error_trace LONGTEXT DEFAULT NULL,
              PRIMARY KEY  (id),
              key (ecombhub_fi_funnel_id),
              key (funnel_product_id),
              key (post_product_id),
              key (order_id),
              key (is_error)
            ) $charset_collate;";


			dbDelta($sql);

			update_option( '_ecombhub_fi_db_version', Ecomhub_Fi_Activator::DB_VERSION);
		}

	}


}
