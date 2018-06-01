<?php
require_once realpath( dirname( __FILE__ ) ) . '/../vendor/autoload.php';
class EcomhubFiUserHelperException extends Exception {}
use ZxcvbnPhp\Zxcvbn;


class EcomhubFiUserHelper
{
    /**
     * checks if user is logged in here
     * @return false|integer
     * @throws
     */
    public static function is_logged_in() {
        $b_what =  is_user_logged_in();
        if ($b_what) {
        	return get_current_user_id();
        } else {
        	return false;
        }
    }

	/**
	 * checks to see if a user exists via email
	 * @param string $email
	 * @return false|integer
	 */
    public static function find_user_via_email($email) {
	    return email_exists( $email);
    }

	/**
	 * checks to see if the website calling this is on a whitelist
	 * @return bool
	 */
    public static function is_callee_on_whitelist() {
    	return true;
    }

    public static function logout_user() {
	    wp_logout();
	    return true;
    }

	/**
	 * @param string $user_login
	 * @param string $password
	 *
	 * @return true
	 * @throws EcomhubFiUserHelperException
	 */
    public static function login_user($user_login,$password) {

	    if (EcomhubFiUserHelper::is_logged_in()) {
		    throw new EcomhubFiUserHelperException("Cannot Login, already logged in");
	    }

	    $creds = array(
		    'user_login'    => $user_login,
		    'user_password' => $password,
		    'remember'      => true
	    );

	    $user = wp_signon( $creds, false );

	    if ( is_wp_error( $user ) ) {
		    $error_message =  $user->get_error_message();
		    throw new EcomhubFiUserHelperException($error_message);
	    }
	    return true;
    }

	/**
	 * Creates a user
	 *  if already exist will return an exception
	 *  if password too weak will return an exception
	 *  else it returns the new user id
	 * @param string $user_name
	 * @param string $user_email
	 * @param string $password
	 *
	 * @return false|int|WP_Error
	 * @throws EcomhubFiUserHelperException
	 */
    public static function create_user($user_name,$user_email,$password) {
    	if (EcomhubFiUserHelper::is_logged_in()) {
    		throw new EcomhubFiUserHelperException("Cannot create user while logged in");
	    }

	    $user_id = username_exists( $user_name );
	    if ( !$user_id and email_exists($user_email) == false ) {

		    $userData = array(
			    $user_name,
			    $user_email
		    );

		    $zxcvbn = new Zxcvbn();
		    $strength = $zxcvbn->passwordStrength($password, $userData);
		    if (  $strength['score'] < 4 ) {
			    throw new EcomhubFiUserHelperException("The password is too weak, try to mix in some numbers, letters and punctuation");
		    }

		    $user_id = wp_create_user( $user_name, $password, $user_email );
		    return $user_id;
	    } else {
		    throw new EcomhubFiUserHelperException("That user name is already taken");
	    }

    }

	/**
	 * Adds meta data to user, unique keys only, if a key already exists then will delete previous key first
	 * @param integer $user_id
	 * @param string $base_key
	 * @param mixed $data
	 * @return array
	 * @throws Exception if cannot do an operation of delete or insert
	 */
    public static function associate_user_data($user_id,$base_key,$data) {

    	$key = 'ecomhub-fi-'.$base_key;
    	//check if meta already exists
	    $what = get_user_meta( $user_id, $key,  false );
	    if ($what) {
		    $b_del_check = delete_user_meta( $user_id, $key );
		    if (!$b_del_check) {
		    	throw new Exception("Could not delete older meta data of key $key");
		    }
	    }


	    $b_ret = add_user_meta( $user_id, $key, $data,true);
	    if (!$b_ret) {
		    throw  new Exception("Key [$key] already exists");
	    }
	    return ['value'=>$data,'key' => $key,'base_key'=>$base_key];

    }

	/**
	 * @param integer $user_id
	 * @param $base_key $key
	 *
	 * @return array
	 */
    public static function get_assocated_user_data($user_id,$base_key) {
	    $key = 'ecomhub-fi-'.$base_key;
	    $what = get_user_meta( $user_id, $key,  true );
	    return ['value'=>$what,'key' => $key,'base_key'=>$base_key];

    }


	/**
	 * @param string $key
	 *
	 * @return string
	 * @throws Exception
	 */
    public static function get_post_key($key) {
	    if (array_key_exists( $key,$_POST) ) {
		    $thing = sanitize_text_field($_POST[$key]);
		    if ($thing) {
			    if (strlen($key) > 100) {
				    throw new EcomhubFiUserHelperException("value of $key is too long, capped at 100 characters");
			    }
		    	return $thing;
		    } else {
			    throw new Exception("$key was set in post, but had no value");
		    }
	    } else {
		    throw new Exception("$key was not set in post");
	    }
    }

	/**
	 * @return string
	 * @throws Exception
	 */
    public static function get_method_from_post() {
    	return EcomhubFiUserHelper::get_post_key('method');
    }

	/**
	 * helper for handling public requests. All data in post needs sanity checking,
	 *  sanitizing and checking is done in get_post_key
	 * @return mixed
	 * @throws EcomhubFiUserHelperException
	 * @throws Exception
	 */
    public static function do_action_from_post() {

	    $method = EcomhubFiUserHelper::get_method_from_post();

	    switch ($method) {
	    	case 'is_logged_in':
			    {
				    return EcomhubFiUserHelper::is_logged_in();
			    }
		    case 'logout_user':{
				    return EcomhubFiUserHelper::logout_user();
			    }
		    case 'find_user': {
			    $email = EcomhubFiUserHelper::get_post_key('email');
			    return EcomhubFiUserHelper::find_user_via_email($email);
		    }
		    case 'login_user': {
			    $user_login = EcomhubFiUserHelper::get_post_key('user_login');
			    $password = EcomhubFiUserHelper::get_post_key('password');
			    return EcomhubFiUserHelper::login_user($user_login,$password);
		    }
		    case 'create_user': {
			    $user_email = EcomhubFiUserHelper::get_post_key('user_email');
			    $password = EcomhubFiUserHelper::get_post_key('password2');
			    $user_name = EcomhubFiUserHelper::get_post_key('user_name');

			    return EcomhubFiUserHelper::create_user($user_name,$user_email,$password);
		    }
		    case 'set_user_meta': {
		    	$user_id = get_current_user_id();
		    	if ($user_id == 0) {
		    		throw new EcomhubFiUserHelperException("Not logged in");
			    }
			    $key = EcomhubFiUserHelper::get_post_key('meta_key');

			    $data = EcomhubFiUserHelper::get_post_key('meta_data');


			    return EcomhubFiUserHelper::associate_user_data($user_id,$key,$data);
		    }
		    case 'get_user_meta': {
			    $user_id = get_current_user_id();
			    if ($user_id == 0) {
				    throw new EcomhubFiUserHelperException("Not logged in");
			    }
			    $key = EcomhubFiUserHelper::get_post_key('meta_key');


			    return  EcomhubFiUserHelper::get_assocated_user_data($user_id,$key);

		    }
		    default: {
		        throw new Exception("Unknown Method of [$method]");
	        }
	    }


    }




}