<?php
/**
 * Created by PhpStorm.
 * User: will
 * Date: 5/26/18
 * Time: 12:53 AM
 */
require_once realpath( dirname( __FILE__ ) ) . '/../vendor/autoload.php';
require_once realpath( dirname( __FILE__ ) ) . '/JsonHelpers.php';
require_once realpath( dirname( __FILE__ ) ) . '/../config.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ChromePHPHandler;

class Ecomhub_Fi_Log {

	const EMERGENCY = Logger::EMERGENCY;
	const ALERT = Logger::ALERT;
	const CRITICAL = Logger::CRITICAL;
	const ERROR = Logger::ERROR;
	const WARNING = Logger::WARNING;
	const NOTICE = Logger::NOTICE;
	const INFO = Logger::INFO;
	const DEBUG = Logger::DEBUG;

	/**
	 * Will Write a log
	 * This is definitely extendable later, as it can also write to many other things, like syslog
	 *  If we ever get to using rsyslog then this will centralize all the logs and alerts
	 *  for right now, though, its just a backup in case other things go wrong
	 *   We are also using an older version of this library as we still use php 5.5.9
	 *
	 * There are over 20 different log plugins
	 * Right now, besides adding to a test log, I have the debug level entries only also going out to a  web console tool
	 *
	 *
	 * https://craig.is/writing/chrome-logger but there are other tools too
	 *
	 *
	 * @see https://github.com/Seldaek/monolog/blob/master/doc/02-handlers-formatters-processors.md
	 *
	 * @param $log_name string , the name of the log
	 * @param $message string
	 * @param int $level
	 *
	 * @see Logger
	 *
	 * @param array $info
	 *
	 * @see https://github.com/Seldaek/monolog/blob/master/doc/01-usage.md
	 * @return bool if log was created
	 * @throws
	 */
	public static function log_to( $log_name, $message, $level = Ecomhub_Fi_Log::INFO, $info = [] ) {
		$ret = false;
		try {


			// Create the logger
			$logger = new Logger( $log_name );
			// Now add some handlers
			$log_dir_raw = realpath( dirname( __FILE__ ) ) . "/../logs/$log_name.log";
			$log_dir     = Ecomhub_Fi_Log::get_absolute_path( $log_dir_raw );
			$streamer    = new StreamHandler( $log_dir, Logger::DEBUG );
			$logger->pushHandler( $streamer );
			// $logger->pushHandler(new FirePHPHandler());


			if ( Logger::DEBUG == $level ) {
				$logger->pushHandler( new ChromePHPHandler() );


				$ret = $logger->debug( $message, $info );
			} elseif ( Logger::INFO == $level ) {
				$ret = $logger->info( $message, $info );
			} elseif ( Logger::NOTICE == $level ) {
				$ret = $logger->notice( $message, $info );
			} elseif ( Logger::WARNING == $level ) {
				$ret = $logger->warning( $message, $info );
			} elseif ( Logger::ERROR == $level ) {
				$ret = $logger->error( $message, $info );
			} elseif ( Logger::CRITICAL == $level ) {
				$ret = $logger->critical( $message, $info );
			} elseif ( Logger::ALERT == $level ) {
				$ret = $logger->alert( $message, $info );
			} elseif ( Logger:: EMERGENCY == $level ) {
				$ret = $logger->emergency( $message, $info );
			} else {
				throw new InvalidArgumentException( "Log Level Unrecognized ? $level" );
			}


		} catch ( Exception $e ) {
			//print "Cannot log message"; print (string)$e;
			$json_info = JsonHelpers::toStringAgnostic($info);
			throw new Exception("Error while logging: $message \n\n$json_info ",0,$e);
		} finally {
			return $ret;
		}
	}

	//lifted from the comments at http://php.net/manual/en/function.realpath.php
	public static function get_absolute_path( $path ) {
		$path      = str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, $path );
		$parts     = array_filter( explode( DIRECTORY_SEPARATOR, $path ), 'strlen' );
		$absolutes = array();
		foreach ( $parts as $part ) {
			if ( '.' == $part ) {
				continue;
			}
			if ( '..' == $part ) {
				array_pop( $absolutes );
			} else {
				$absolutes[] = $part;
			}
		}

		return DIRECTORY_SEPARATOR . implode( DIRECTORY_SEPARATOR, $absolutes );
	}

	/**
	 * @param $title string
	 * @param $message
	 *
	 */
	public static function sns_alert( $title, $message ) {

		$title_to_use = "Default Title";
		$message_to_send = $message;
		try {
			$sharedConfig = [
				'region'  => getenv( 'AWS_REGION' ),
				'version' => 'latest'
			];

			// Create an SDK class used to share configuration across clients.
			$sdk = new Aws\Sdk( $sharedConfig );

			$client = $sdk->createSns();


			if ( is_array( $message ) ) {
				$message = JsonHelpers::toString( $message );
			} elseif ( ! is_string( $message ) ) {
				$message = JsonHelpers::toStringAgnostic( $message );
			}
			$message_to_send = JsonHelpers::to_utf8( $message );

			$title_to_use = JsonHelpers::to_utf8( $title );

			$title_to_use = preg_replace( "/\r|\n/", "", $title_to_use );
			$title_to_use = preg_replace( '/[[:cntrl:]]/', '', $title_to_use );
			if ( is_array( $message ) ) {
				$message_to_send = json_encode( $message_to_send );
			}

			if ( strlen( $title_to_use ) > 50 ) {
				$title_to_use = substr( $title_to_use, 0, 50 ) . '...';
			}

			$message_to_send = JsonHelpers::to_utf8( $message_to_send );
			$message_to_send = htmlentities( $message_to_send, ENT_NOQUOTES, "UTF-8" );
			$payload         = array(
				'TopicArn'         => SNS_ALERT_ARN,
				'Message'          => $message_to_send,
				'Subject'          => $title_to_use,
				'MessageStructure' => 'string',
			);


			$client->publish( $payload );
		} catch ( Exception $e ) {
			$message_to_send = 'Could not send this via sns because of ' . $e->getMessage() . "\n " . $message_to_send;
			self::send_emergancy_email( $title_to_use, $message_to_send );
		}

	}

	public static function send_emergancy_email( $subject, $message ) {

		$email_string = EMERGANCY_EMAILS;
		if ( empty( $email_string ) ) {
			$email_array = [ 'willwoodlief@gmail.com' ];  //to let me know to add it
		} else {
			$email_array = explode( '&', $email_string );
		}

		print "Could not email $subject : $message";
	}
}