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
	$course_id = 62;

	$users_to_add = [];
	$users_have = [];
	$min = 999999999999999999999;
	$human_min = 'not set';
	date_default_timezone_set('America/Chicago');
	$time_per_section = (60*60 * 24 * 6) + (60*60*18);
	foreach ($user_res as $s) {
		$user_id = $s->ID;
		$start_times = get_user_meta( $user_id, 'ecomhub_fi_user_start_course', true );
	//	print_r($start_times);
		if (is_array($start_times) && array_key_exists($course_id,$start_times)) {
		    $ts = intval($start_times[$course_id]);
		    $human = date('l jS \of F Y h:i:s A',$ts);
		    $future = $time_per_section + $ts;
		    $human_future = date('l jS \of F Y h:i:s A',$future);
			print "{$s->user_email} [{$s->ID}] has ts of [$ts] {$human}  and future of {$human_future}\n";
		    if ($ts < $min) {
		         $min = $ts;
			    $human_min = $human_future;
            }
		}


	}

	print "\n\nmin is $human_min\n";






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
