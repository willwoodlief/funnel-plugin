#!/usr/local/php70/bin/php-cli -q
<?php
// #!/usr/bin/php -q
// Set a long timeout in case we're dealing with big files
set_time_limit(6000);
ini_set('max_execution_time',6000);
try {
	require_once realpath( dirname( __FILE__ ) ) . '/../config.php';
	require_once realpath( dirname( __FILE__ ) ) . '/../includes/log.php';
	require_once realpath( dirname( __FILE__ ) ) . '/../includes/JsonHelpers.php';
	require_once realpath( dirname( __FILE__ ) ) . '/../includes/EcomhubFiMailReader.php';
	require_once realpath( dirname( __FILE__ ) ) . '/connect_order.php';

	if ( php_sapi_name() !== 'cli' ) {
		die( "Meant to be run from command line" );
	}


	function find_wordpress_base_path() {
		$dir = dirname( __FILE__ );
		do {
			//it is possible to check for other files here
			if ( file_exists( $dir . "/wp-config.php" ) ) {
				return $dir;
			}
		} while ( $dir = realpath( "$dir/.." ) );

		return null;
	}

	define( 'BASE_PATH', find_wordpress_base_path() . "/" );
	define( 'WP_USE_THEMES', false );
	global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header,$wpdb;
	/** @noinspection PhpIncludeInspection */
	require( BASE_PATH . 'wp-load.php' );

	$options = get_option( 'ecomhub_fi_options' );
	$log_name   = $options['log_name'] ? $options['log_name'] : 'test';


	$table_name = $wpdb->prefix . 'users';

	$user_res = $wpdb->get_results( /** @lang text */
		"
        SELECT ID,user_email,display_name
        from $table_name 
         ;
        ");


	if ($wpdb->last_error) {
		throw new Exception($wpdb->last_error );
	}

	$course_id = 845;
	$membership_id = 5490;
	$users_to_add = [];
	$users_have = [];
	foreach ($user_res as $s) {
		$user_id = $s->ID;
		$b_has_course = wc_customer_bought_product(null,$user_id,$course_id);
		if ($b_has_course) {
			$b_has_membership = wc_customer_bought_product(null,$user_id,$membership_id);
			if ($b_has_membership) {
				$users_have[] = $user_id;
				print "{$s->user_email} [{$s->ID}] has membership \n";
			} else {
				$users_to_add[] = $user_id;
				print "ADDING  {$s->user_email} [{$s->ID}]  \n";
				$uid = $user_id;
				$woo = EcomhubFiConnectOrder::make_woo_order( $uid, $membership_id, 'stripe', $http_code, null );
				if ( $http_code != 201 ) {
					throw new Exception( "Did not get 201 code when creating order for id of [$uid] " );
				}
			}
		}
	}






}

catch (Exception $e) {
	$error_info = $e->getMessage()." \n ". $e->getTraceAsString();
	if ($e->getPrevious()) {
		$prev_error_info = $e->getPrevious()->getMessage()." \n ". $e->getPrevious()->getTraceAsString();
		$error_info .= "\n\nPrevious\n\n" . $prev_error_info;
	}
	//try to log, but if the log fails, silently discard any exception
	try {
		Ecomhub_Fi_Log::log_to( $log_name, $error_info, Ecomhub_Fi_Log::ERROR, [ ]  );
	} catch (Exception $e) {}

	Ecomhub_Fi_Log::sns_alert("Error in incoming mail",$error_info);
}
