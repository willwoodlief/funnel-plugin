<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Ecomhub_Fi
 * @subpackage Ecomhub_Fi/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Ecomhub_Fi
 * @subpackage Ecomhub_Fi/admin
 * @author     Your Name <email@example.com>
 */
class Ecomhub_Fi_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

        $b_check = strpos($_SERVER['QUERY_STRING'], 'ecomhub-fi');
        if ($b_check !== false) {



            wp_enqueue_style( $this->plugin_name.'slick', plugin_dir_url( __DIR__ ) . 'lib/SlickGrid/slick.grid.css', array(), $this->version, 'all' );
         //   wp_enqueue_style( $this->plugin_name.'slickuismooth', plugin_dir_url( __DIR__ ) . 'lib/SlickGrid/css/smoothness/jquery-ui-1.11.3.custom.css', array(), $this->version, 'all' );
            wp_enqueue_style( $this->plugin_name.'slickexamps', plugin_dir_url( __DIR__ ) . 'lib/SlickGrid/css/working.css', array(), $this->version, 'all' );
            wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ecomhub-fi-admin.css', array(), $this->version, 'all' );
        }


	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {


        $b_check = strpos($_SERVER['QUERY_STRING'], 'ecomhub-fi');


        if ($b_check !== false) {


          //  wp_enqueue_script($this->plugin_name.'slickcorejqui', plugin_dir_url(__DIR__) . 'lib/SlickGrid/lib/jquery-ui-1.11.3.js', array('jquery'), $this->version, false);

            wp_enqueue_script($this->plugin_name.'slickcoredrag', plugin_dir_url(__DIR__) . 'lib/SlickGrid/lib/jquery.event.drag-2.3.0.js', array('jquery'), $this->version, false);
            wp_enqueue_script($this->plugin_name.'slickcorejsonp', plugin_dir_url(__DIR__) . 'lib/SlickGrid/lib/jquery.jsonp-2.4.min.js', array('jquery'), $this->version, false);
            wp_enqueue_script($this->plugin_name.'slickcore', plugin_dir_url(__DIR__) . 'lib/SlickGrid/slick.core.js', array('jquery'), $this->version, false);
            wp_enqueue_script( $this->plugin_name.'a', plugin_dir_url( __FILE__ ) . 'js/ecomhub-fi-admin.js', array( 'jquery' ), $this->version, false );
            wp_enqueue_script($this->plugin_name.'slickgrid', plugin_dir_url(__DIR__) . 'lib/SlickGrid/slick.grid.js', array('jquery'), $this->version, false);
            wp_enqueue_script($this->plugin_name.'slicksel', plugin_dir_url(__DIR__) . 'lib/SlickGrid/plugins/slick.rowselectionmodel.js', array('jquery'), $this->version, false);



            wp_enqueue_script($this->plugin_name, plugin_dir_url(__DIR__) . 'lib/Chart.min.js', array('jquery'), $this->version, false);

            $title_nonce = wp_create_nonce( 'Ecomhub_Fi_Admin' );
            wp_localize_script('ecomhub-fi', 'ecomhub_fi_backend_ajax_obj', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'action' => 'ecombhub_fi_admin',
                'nonce' => $title_nonce,
            ));
        }

	}

    public function my_admin_menu() {

	    add_options_page( 'Funnels Integration Option', 'Funnels', 'manage_options',
		    'ecomhub-fi-funnels', array( $this, 'create_admin_interface') );//
	}



    /**
     * Callback function for the admin settings page.
     *
     * @since    1.0.0
     */
    public function create_admin_interface(){

	    $this->options = get_option( 'ecomhub_fi_options' );
        /** @noinspection PhpIncludeInspection */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/ecomhub-fi-admin-display.php';

    }

    public function add_settings() {

	    register_setting(
		    'ecomhub-fi-options-group', // Option group
		    'ecomhub_fi_options', // Option name
		    array( $this, 'sanitize' ) // Sanitize
	    );

	    add_settings_section(
		    'setting_section_id', // ID
		    'Click Funnel Integration Settings', // Title
		    array( $this, 'print_section_info' ), // Callback
		    'comhub-fi-funnels' // Page
	    );

	    add_settings_field(
		    'log_name', // ID
		    'Log Name For Debugging', // Title
		    array( $this, 'log_name_callback' ), // Callback
		    'comhub-fi-funnels', // Page
		    'setting_section_id' // Section
	    );



	    add_settings_field(
		    'is_listening',
		    'Is This Listening?',
		    array( $this, 'listening_callback' ),
		    'comhub-fi-funnels',
		    'setting_section_id'
	    );


    }


	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $options;



	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 * @return array
	 */
	public function sanitize( $input )
	{
		$new_input = array();
		if( isset( $input['log_name'] ) ) {
			$new_input['log_name'] = sanitize_text_field( $input['log_name'] );
		}




		if( isset( $input['is_listening'] ) ) {
			$new_input['is_listening'] = sanitize_text_field( $input['is_listening'] );
		} else {
			$new_input['is_listening'] = '0' ;
		}


		return $new_input;
	}

	/**
	 * Print the Section text
	 */
	public function print_section_info()
	{
		print 'Enter your settings below:';
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function log_name_callback()
	{
		printf(
			'<input type="text" id="log_name" name="ecomhub_fi_options[log_name]" value="%s" />',
			isset( $this->options['log_name'] ) ? esc_attr( $this->options['log_name']) : ''
		);
	}

	public function listening_callback() {

		$setting = isset( $this->options['is_listening'] ) ? esc_attr( $this->options['is_listening']) : 0;
		$checked = checked( 1, $setting, false );

		printf(
			'<input type="checkbox" id="is_listening" name="ecomhub_fi_options[is_listening]" value="1" %s />',
			$checked
		);

	}




    public function query_survey_ajax_handler() {

	    require_once plugin_dir_path(dirname(__FILE__)) . 'admin/ecomhub-fi-list-events.php';

        check_ajax_referer( 'Ecomhub_Fi_Admin' );

        if (array_key_exists( 'method',$_POST) && $_POST['method'] == 'stats') {
            try {
                $stats = EcomhubFiListEvents::get_stats_array();
                wp_send_json(['is_valid' => true, 'data' => $stats, 'action' => 'stats']);
                die();
            } catch (Exception $e) {
                wp_send_json(['is_valid' => false, 'message' => $e->getMessage(), 'trace'=>$e->getTrace(), 'action' => 'stats' ]);
                die();
            }
        }
        elseif (array_key_exists( 'method',$_POST) && $_POST['method'] == 'list') {

	        try {

		        $chi_enquete_list_obj = EcomhubFiListEvents::do_query_from_post();
		        wp_send_json(['is_valid' => true, 'data' => $chi_enquete_list_obj, 'action' => 'list']);
		        die();
	        } catch (Exception $e) {
		        wp_send_json(['is_valid' => false, 'message' => $e->getMessage(), 'trace'=>$e->getTrace(), 'action' => 'list' ]);
		        die();
	        }

        }
        elseif (array_key_exists( 'method',$_POST) && $_POST['method'] == 'detail') {
	         global $ecombhub_fi_details_object;
	        try {
		        $ecombhub_fi_details_object = EcomhubFiListEvents::get_details_of_one(intval($_POST['id']));
		        ob_start();
		        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ecomhub-fi-admin-detail.php';
		        $html = ob_get_contents();
		        ob_end_clean();
		        $chi_enquete_details_object['html'] = $html;
		        wp_send_json(['is_valid' => true, 'data' => $chi_enquete_details_object, 'action' => 'detail']);
		        die();
	        } catch (Exception $e) {
		        wp_send_json(['is_valid' => false, 'message' => $e->getMessage(), 'trace'=>$e->getTrace(), 'action' => 'detail' ]);
		        die();
	        }

        }
        else {
            //unrecognized
            wp_send_json(['is_valid' => false, 'message' => "unknown action"]);
            die();
        }
    }



}
