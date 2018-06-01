<?php
require_once realpath(dirname(__FILE__)) . '/page_on_fly.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'public/ecomhub-fi-user-helper.php';
/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Ecomhub_Fi
 * @subpackage Ecomhub_Fi/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Ecomhub_Fi
 * @subpackage Ecomhub_Fi/public
 * @author     Your Name <email@example.com>
 */
class Ecomhub_Fi_Public
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string $plugin_name The name of the plugin.
     * @param      string $version The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;


	    $args = array(
		    'slug' => 'ecomhub-fi/js_api',
		    'post_title' => 'Designed for iframe communication between public and server',
		    'partial' => plugin_dir_path(dirname(__FILE__)) . 'public/partials/ecomhub-fi-public-api.php'
	    );
	    new WP_EX_PAGE_ON_THE_FLY($args);

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

//ecomhub-fi
	    $b_check = strpos($_SERVER['REQUEST_URI'], 'ecomhub-fi');
	    if (!$b_check) {
		    $b_check = strpos($_SERVER['QUERY_STRING'], 'ecomhub-fi');
	    }
	    if ($b_check !== false) {
		    wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/ecomhub-fi-public.css', array(), $this->version, 'all');
	    }


    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

    	//ecomhub-fi
	    $b_check = strpos($_SERVER['REQUEST_URI'], 'ecomhub-fi');
	    if (!$b_check) {
		    $b_check = strpos($_SERVER['QUERY_STRING'], 'ecomhub-fi');
	    }
	    if ($b_check !== false) {
//		    wp_enqueue_script( $this->plugin_name, plugin_dir_url( __DIR__ ) . 'lib/Chart.min.js', array( 'jquery' ), $this->version, false );
		    wp_enqueue_script( $this->plugin_name . 'a', plugin_dir_url( __FILE__ ) . 'js/ecomhub-fi-public.js', array( 'jquery' ), $this->version, false );
		    $title_nonce = wp_create_nonce( 'ecombhub_fi_public' );
		    wp_localize_script( $this->plugin_name . 'a', 'ecombhub_fi_public_ajax_obj', array(
			    'ajax_url' => admin_url( 'admin-ajax.php' ),
			    'action'   => 'ecombhub_fi_public',
			    'nonce'    => $title_nonce,
		    ) );
	    }

    }

    //JSON
    function send_survey_ajax_handler()
    {



//	    check_ajax_referer('ecombhub_fi_public');
	    $method = null;
	    try {

		    if ( ! EcomhubFiUserHelper::is_callee_on_whitelist() ) {
			    throw new Exception( "Callee is not on whitelist" );
		    }

		    $method = EcomhubFiUserHelper::get_method_from_post();
			$response = EcomhubFiUserHelper::do_action_from_post();
		    $nonce = wp_create_nonce( 'ecombhub_fi_public' ); //changes if the person is logged in vs not
		    wp_send_json( [ 'is_valid' => true, 'data' => $response, 'method' => $method,'new_nonce' => $nonce] );
	    }
	    catch (EcomhubFiUserHelperException $to_user) {
		    wp_send_json(['method' => $method,'is_valid' => false, 'message' => $to_user->getMessage(), 'trace'=>$to_user->getTrace(), 'post' => $_POST,'for_user' => true ]);
	    }
	    catch (Exception $last) {
		    wp_send_json(['method' => $method,'is_valid' => false, 'message' => $last->getMessage(), 'trace'=>$last->getTrace(), 'post' => $_POST,'for_user' => false ]);
	    }
    }

    public function shortcut_code()
    {
        add_shortcode($this->plugin_name, array($this, 'manage_shortcut'));
    }

    /**
     * @param array $attributes - [$tag] attributes
     * @param null $content - post content
     * @param string $tag
     * @return string - the html to replace the shortcode
     */
    public
    function manage_shortcut($attributes = [], $content = null, $tag = '')
    {
        global $ecombhub_fi_custom_header;
// normalize attribute keys, lowercase
        $atts = array_change_key_case((array)$attributes, CASE_LOWER);

        // override default attributes with user attributes
        $our_atts = shortcode_atts([
            'border' => 1,
            'results' => 0,
        ], $atts, $tag);

        // start output
        $o = '';

        $ecombhub_fi_custom_header = '';
        // enclosing tags
        if (!is_null($content)) {

            // run shortcode parser recursively
            $expanded__other_shortcodes = do_shortcode($content);
            // secure output by executing the_content filter hook on $content, allows site wide auto formatting too
            $ecombhub_fi_custom_header .= apply_filters('the_content', $expanded__other_shortcodes);

        }
	    require_once plugin_dir_path(dirname(__FILE__)) . 'public/partials/ecomhub-fi-shortcode.php';
        // return output
        return $o;
    }

}
