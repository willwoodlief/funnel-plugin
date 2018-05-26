#!/usr/bin/php -q
<?php
//#!/usr/local/php70/bin/php-cli -q
// Set a long timeout in case we're dealing with big files
set_time_limit(600);
ini_set('max_execution_time',600);
try {
	require_once realpath( dirname( __FILE__ ) ) . '/../config.php';
	require_once realpath( dirname( __FILE__ ) ) . '/../includes/log.php';
	require_once realpath( dirname( __FILE__ ) ) . '/../includes/mailReader.php';

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
	global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
	require( BASE_PATH . 'wp-load.php' );

	$options = get_option( 'ecomhub_fi_options' );
	$log_name   = $options['log_name'] ? $options['log_name'] : 'test';
	$b_continue = $options['is_listening'] == 1 ? true : false;

	if ( ! $b_continue ) {
		Ecomhub_Fi_Log::log_to( $log_name, 'mail recieved but turned off', Ecomhub_Fi_Log::NOTICE );
		die();
	}

// Where should discovered files go
	$save_directory = __DIR__; // stick them in the current directory
	$pdo            = null;
    throw new LogicException("test");

// Who can send files to through this script?
	$allowed_senders = Array(
		'news@jetbrains.com',
		'whatever@example.com'
	); //todo make allowed to be regular expression


	$mr                 = new mailReader( $save_directory, $allowed_senders, $pdo );
	$mr->save_msg_to_db = false;
	$mr->send_email     = false;
// Example of how to add additional allowed mime types to the list
// $mr->allowed_mime_types[] = 'text/csv';
	$mr->readEmail();
	if ( empty( $mr->body ) ) {
		Ecomhub_Fi_Log::log_to( $log_name, 'empty body', Ecomhub_Fi_Log::WARNING, $mr );
		die();
	}

	$from = $mr->from;
	$to   = $mr->to;
	$body = $mr->body;
	$data = [ 'to' => $to, 'from' => $from, 'subject' => $mr->subject,'body' =>$body ];
	Ecomhub_Fi_Log::log_to( $log_name, 'mail message recieved', Ecomhub_Fi_Log::INFO, $data );
} catch (Exception $e) {
	$error_info = $e->getMessage()." \n ". $e->getTraceAsString();
    Ecomhub_Fi_Log::sns_alert("Error in incoming mail",$error_info);
}
