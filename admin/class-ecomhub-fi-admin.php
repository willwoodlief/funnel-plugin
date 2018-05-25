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
//        add_menu_page( 'Funnel Integration and Responses', 'Funnels',
//	        'manage_options', 'ecomhub-fi-admin-page', array( $this, 'create_admin_interface' ), 'dashicons-chart-line', null  );
	    //add a new options page
//	    add_options_page(
//		    'Funnels Integration Options',
//		    'Funnel',
//		    'manage_options',
//		    'funnels',
//		    ''
//	    );
	    add_options_page( 'Funnels Integration Option', 'Funnels', 'manage_options',
		    'ecomhub-fi-funnels', array( $this, 'create_admin_interface') );//
	}



    /**
     * Callback function for the admin settings page.
     *
     * @since    1.0.0
     */
    public function create_admin_interface(){

	    $this->options = get_option( 'my_option_name' );
        /** @noinspection PhpIncludeInspection */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/ecomhub-fi-admin-display.php';

    }

    public function add_settings() {





	    register_setting(
		    'ecomhub-fi-options-group', // Option group
		    'my_option_name', // Option name
		    array( $this, 'sanitize' ) // Sanitize
	    );

	    add_settings_section(
		    'setting_section_id', // ID
		    'My Custom Settings', // Title
		    array( $this, 'print_section_info' ), // Callback
		    'comhub-fi-funnels' // Page
	    );

	    add_settings_field(
		    'id_number', // ID
		    'ID Number', // Title
		    array( $this, 'id_number_callback' ), // Callback
		    'comhub-fi-funnels', // Page
		    'setting_section_id' // Section
	    );

	    add_settings_field(
		    'title',
		    'Title',
		    array( $this, 'title_callback' ),
		    'comhub-fi-funnels',
		    'setting_section_id'
	    );

	    add_settings_field(
		    'is_listening',
		    'Is This Listening?',
		    array( $this, 'listening_callback' ),
		    'comhub-fi-funnels',
		    'setting_section_id'
	    );


//	    add_settings_field(
//		    'ecomhub-fi-is-listening',
//		    'Is This Listening?',
//		    function() {
//                // get the value of the setting we've registered with register_setting()
//                $setting = get_option('ecombhub_fi_on_switch');
//                // output the field
//	            $html = '<input type="checkbox" id="ecombhub_fi_on_switch" name="ecombhub_fi_on_switch" value="1"' . checked( 1, $setting, false ) . '/>';
//	            $html .= '<label for="ecombhub_fi_on_switch">Is this Running to Process Funnels?</label>';
//
//	            echo $html;
//
//            },
//		    'comhub-fi-funnels',
//		    'setting_section_id'
//	    );




//        // register settings  for "reading" page
//        $args = array(
//            'type' => 'bool',
//            'sanitize_callback' => null,
//            'default' => '',
//        );
//
//        $args['default'] = true;
//        //for vitacheck header
//        register_setting('ecomhub-fi-funnels', 'ecombhub_fi_on_switch',$args);
//
//
//	    add_settings_section(
//		    'setting_section_id', // ID
//		    'My Custom Settings', // Title
//		    array( $this, 'print_section_info' ), // Callback
//		    'my-setting-admin' // Page
//	    );
//	    //ecomhub-fi-funnels is the page slug
//
//	    // register vitacheck field in the "ecombhub_fi_settings_section" section, inside the "reading" page
//        add_settings_field(
//            'ecombhub_fi_on_switch',
//            'Process Funnel Callbacks',
//            function() {
//                // get the value of the setting we've registered with register_setting()
//                $setting = get_option('ecombhub_fi_on_switch');
//                // output the field
//	            $html = '<input type="checkbox" id="ecombhub_fi_on_switch" name="ecombhub_fi_on_switch" value="1"' . checked( 1, $setting, false ) . '/>';
//	            $html .= '<label for="ecombhub_fi_on_switch">Is this Running to Process Funnels?</label>';
//
//	            echo $html;
//
//            },
//            'reading',
//            'ecombhub_fi_settings_section'
//        );


    }

    //todo remove this block

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
		if( isset( $input['id_number'] ) )
			$new_input['id_number'] = absint( $input['id_number'] );

		if( isset( $input['title'] ) )
			$new_input['title'] = sanitize_text_field( $input['title'] );

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
	public function id_number_callback()
	{
		printf(
			'<input type="text" id="id_number" name="my_option_name[id_number]" value="%s" />',
			isset( $this->options['id_number'] ) ? esc_attr( $this->options['id_number']) : ''
		);
	}

	public function listening_callback() {

		$setting = isset( $this->options['is_listening'] ) ? esc_attr( $this->options['is_listening']) : 0;
		$checked = checked( 1, $setting, false );

		printf(
			'<input type="checkbox" id="is_listening" name="my_option_name[is_listening]" value="1" %s />',
			$checked
		);

	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function title_callback()
	{
		printf(
			'<input type="text" id="title" name="my_option_name[title]" value="%s" />',
			isset( $this->options['title'] ) ? esc_attr( $this->options['title']) : ''
		);
	}
	//end todo remove block above

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
