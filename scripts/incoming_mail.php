#!/usr/bin/php -q
#!/usr/local/php70/bin/php-cli -q
<?php
// #!/usr/bin/php -q
// Set a long timeout in case we're dealing with big files
set_time_limit(600);
ini_set('max_execution_time',600);
try {
	require_once realpath( dirname( __FILE__ ) ) . '/../config.php';
	require_once realpath( dirname( __FILE__ ) ) . '/../includes/log.php';
	require_once realpath( dirname( __FILE__ ) ) . '/../includes/JsonHelpers.php';
	require_once realpath( dirname( __FILE__ ) ) . '/../includes/EcomhubFiMailReader.php';

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
	$b_continue = $options['is_listening'] == 1 ? true : false;

	if ( ! $b_continue ) {
		Ecomhub_Fi_Log::log_to( $log_name, 'mail recieved but turned off', Ecomhub_Fi_Log::NOTICE );
		die();
	}

// Where should discovered files go
	$save_directory = __DIR__.'/downloads'; // stick them in the current directory
	$pdo            = null;


// Who can send files to through this script?
	$allowed_senders = $options['allowed_senders'];


	$mr                 = new EcomhubFiMailReader( $save_directory, $allowed_senders, $pdo );
	$mr->save_msg_to_db = false;
	$mr->send_email     = false;
// Example of how to add additional allowed mime types to the list
// $mr->allowed_mime_types[] = 'text/csv';
    $file = null; //read from standard input
    // $file= '/home/will/htdocs/wordpress/wp-content/plugins/ecomhub-fi/scripts/email_samples/sample_cc.txt'; //when debugging in debugger
	$mr->readEmail();
	if ( empty( $mr->body ) ) {
		Ecomhub_Fi_Log::log_to( $log_name, 'empty body', Ecomhub_Fi_Log::WARNING, $mr );
		die();
	}

	$from = $mr->from;
	$to   = $mr->to;
	$body = $mr->body;
	$attachments = $mr->saved_files;

	$table_name = $wpdb->prefix . 'ecombhub_fi_funnels';

	$wpdb->insert(
		$table_name,
		array(
            'created_at_ts' => time(), //mysql server there is not in utc
            'is_completed' => 1,
            'user_id_read' => null,
            'comments' => "passively logging email",
            'invoice_number' => null,
            'email_to' => $to,
            'email_from' => $from,
            'email_subject' => $mr->subject,
            'email_body' => $body,
            'email_attachent_files_saved' => JsonHelpers::toStringAgnostic($attachments),
            'email_all_recipients' => $mr->all_recipients,
            'raw_email' =>  trim($mr->raw)
		)
	);
	if ($wpdb->last_error) {
		throw new Exception($wpdb->last_error );
	}
	$data = [ 'to' => $to, 'from' => $from, 'subject' => $mr->subject,'body' =>$body,'attachments'=> $attachments];
	Ecomhub_Fi_Log::log_to( $log_name, 'mail message recieved', Ecomhub_Fi_Log::INFO, $data );
}
catch (EcomhubFiNotAllowedSender $g) {

    try {
	    Ecomhub_Fi_Log::log_to( $log_name, 'Mail Message Not Processed', Ecomhub_Fi_Log::NOTICE, ['message'=>$g->getMessage() ]  );
    } catch (Exception $u) {
	    $error_info = $e->getMessage()." \n ". $e->getTraceAsString();
	    if ($e->getPrevious()) {
		    $prev_error_info = $e->getPrevious()->getMessage()." \n ". $e->getPrevious()->getTraceAsString();
		    $error_info .= "\n\nPrevious\n\n" . $prev_error_info;
        }
	    Ecomhub_Fi_Log::sns_alert("Error in logging other error",$error_info);
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
