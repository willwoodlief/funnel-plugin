<?php
require_once realpath( dirname( __FILE__ ) ) . '/../vendor/autoload.php';
class EcomhubFiUserHelperException extends Exception {}
use ZxcvbnPhp\Zxcvbn;


class EcomhubFiUserHelper
{
    /**
     * checks if user is logged in here
     * @return false|array
     * @throws
     */
    public static function is_logged_in() {
	    $user = wp_get_current_user();
	    if ( $user->exists() ) {

	    	$code = self::set_user_reference($user->ID);
		    // do something
		    return ['email'=> $user->user_email,'id'=> $user->ID, 'first_name' => $user->first_name,
		            'last_name' => $user->last_name,
			    'login_name'=>$user->user_login, 'user_reference' => $code];
	    } else {
	    	return false;
	    }

    }

	/**
	 * sets a user reference only one time, if set returns what was set earlier
	 * @param integer $user_id
	 *
	 * @return string
	 * @throws Exception
	 */
    public static function set_user_reference($user_id) {

	    $key = '_ecomhub_fi_funnel_reference';
	    //check if meta already exists
	    $what = get_user_meta( $user_id, $key,  false );
	    if($what) {return $what;}


	    $data = md5(uniqid('use_user_id: '.$user_id , true));
	    $b_ret = add_user_meta( $user_id, $key, $data,true);
	    if (!$b_ret) {
		    throw  new Exception("Key [$key] already exists");
	    }
	    return $data;
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
    	//todo implement whitelist (look up server values after actually called from places)
    	return true;
    }

    public static function logout_user($passthrough=null) {
	    wp_logout();
	    return ["pass_through"=>$passthrough];
    }

	/**
	 * @param string $user_login
	 * @param string $password
	 *
	 * @return array
	 * @throws EcomhubFiUserHelperException
	 * @throws Exception
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

	    $code = self::set_user_reference($user->ID);

	    return ['email'=> $user->user_email,'id'=> $user->ID, 'first_name' => $user->first_name, 'last_name' => $user->last_name,
	            'login_name'=>$user->user_login, 'user_reference' => $code];

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
	 * @return array
	 * @throws EcomhubFiUserHelperException
	 * @throws Exception
	 */
    public static function create_user($user_name,$user_email,$password) {
    	if (EcomhubFiUserHelper::is_logged_in()) {
    		throw new EcomhubFiUserHelperException("Cannot create user while logged in");
	    }

	    if (empty($user_email)) {
		    throw new EcomhubFiUserHelperException("Need an email to create an account");
	    }

	    if (empty($user_name)) {
    		$user_name = $user_email;
	    }

	    if (empty($password)) {
		    $password = wp_generate_password( 12, true );
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
		    if ( is_wp_error( $user_id ) ) {
			    $error_message =  $user_id->get_error_message();
			    throw new EcomhubFiUserHelperException($error_message);
		    }

		    self::associate_user_data($user_id,'new_password',$password);
		    wp_mail( $user_email, "Your New Account for Ecomhub.com", "Hello,\n\nYou have successfully registered for EcomHub.com\nHere are your credentials\n\nLogin:  $user_email \nPassword: $password \n\nPlease go to the User Dashboard at https://ecomhub.com/user-dashboard/?user-action=courses" );


		    return ['user_id' =>$user_id,'email'=>$user_email,'password'=>$password];
	    } else {
	    	if ($user_name == $user_email) {
	    		$message = "Cannot create new account, $user_email is already being used";
		    } else {
			    $message = "Cannot create new account, The user name of $user_name or email of $user_email is already taken";
		    }
		    throw new EcomhubFiUserHelperException($message);
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

    	$key = '_ecomhub_fi_'.$base_key;
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
	    $key = '_ecomhub_fi_'.$base_key;
	    $what = get_user_meta( $user_id, $key,  true );
	    return ['value'=>$what,'key' => $key,'base_key'=>$base_key];

    }


	/**
	 * @param string $key
	 * @param boolean $optional - default false
	 * @return string
	 * @throws Exception
	 */
    public static function get_post_key($key,$optional = false) {
	    if (array_key_exists( $key,$_POST) ) {
		    $thing = sanitize_text_field($_POST[$key]);
		    if ($thing) {
			    if (strlen($key) > 100) {
				    throw new EcomhubFiUserHelperException("value of $key is too long, capped at 100 characters");
			    }
		    	return $thing;
		    } else {
		    	if ($optional) {
		    		return null;
			    }
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
		    	    $passthrough =  EcomhubFiUserHelper::get_post_key('passthrough',true);
				    return EcomhubFiUserHelper::logout_user($passthrough);
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
			    $password = EcomhubFiUserHelper::get_post_key('password2',true);
			    $user_name = EcomhubFiUserHelper::get_post_key('user_name',true);

			    return EcomhubFiUserHelper::create_user($user_name,$user_email,$password);
		    }
		    case 'set_user_meta': {
		    	$user_id = get_current_user_id();
		    	if ($user_id == 0) {
		    		throw new EcomhubFiUserHelperException("Not logged in");
			    }
			    $key = 'funnel_reference';

			    $data = md5(uniqid('use_user_id: '.$user_id , true));


			    return EcomhubFiUserHelper::associate_user_data($user_id,$key,$data);
		    }

		    default: {
		        throw new Exception("Unknown Method of [$method]");
	        }
	    }


    }




}