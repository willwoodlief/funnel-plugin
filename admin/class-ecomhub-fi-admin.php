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

	        wp_enqueue_style( $this->plugin_name.'-fontawesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css', array(), $this->version, 'all' );
            wp_enqueue_style( $this->plugin_name.'-slickgrid', plugin_dir_url( __DIR__ ) . 'lib/SlickGrid/slick.grid.css', array(), $this->version, 'all' );
            wp_enqueue_style( $this->plugin_name.'-slick-styles', plugin_dir_url( __DIR__ ) . 'lib/SlickGrid/css/working.css', array(), $this->version, 'all' );
            wp_enqueue_style( $this->plugin_name.'-main', plugin_dir_url( __FILE__ ) . 'css/ecomhub-fi-admin.css', array(), $this->version, 'all' );
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

            wp_enqueue_script($this->plugin_name.'-slickcoredrag', plugin_dir_url(__DIR__) . 'lib/SlickGrid/lib/jquery.event.drag-2.3.0.js', array('jquery'), $this->version, false);
            wp_enqueue_script($this->plugin_name.'-slickcore', plugin_dir_url(__DIR__) . 'lib/SlickGrid/slick.core.js', array('jquery'), $this->version, false);
            wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ecomhub-fi-admin.js', array( 'jquery' ), $this->version, false );
            wp_enqueue_script($this->plugin_name.'-slickgrid', plugin_dir_url(__DIR__) . 'lib/SlickGrid/slick.grid.js', array('jquery'), $this->version, false);
            wp_enqueue_script($this->plugin_name.'-slicksel', plugin_dir_url(__DIR__) . 'lib/SlickGrid/plugins/slick.rowselectionmodel.js', array('jquery'), $this->version, false);



            wp_enqueue_script($this->plugin_name.'-chart', plugin_dir_url(__DIR__) . 'lib/Chart.min.js', array('jquery'), $this->version, false);

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
		    'is_listening',
		    'Is This Listening?',
		    array( $this, 'listening_callback' ),
		    'comhub-fi-funnels',
		    'setting_section_id'
	    );

	    add_settings_field(
		    'allowed_senders', // ID
		    'White List for Email Senders', // Title
		    array( $this, 'allowed_senders_callback' ), // Callback
		    'comhub-fi-funnels', // Page
		    'setting_section_id' // Section
	    );

	    add_settings_field(
		    'woo_rest_api_key', // ID
		    'WooCommerce Api Key this Plugin uses (requres read/write key)', // Title
		    array( $this, 'woo_rest_api_key_callback' ), // Callback
		    'comhub-fi-funnels', // Page
		    'setting_section_id' // Section
	    );

	    add_settings_field(
		    'woo_rest_api_secret', // ID
		    'WooCommerce Api Secret this Plugin uses ', // Title
		    array( $this, 'woo_rest_api_secret_callback' ), // Callback
		    'comhub-fi-funnels', // Page
		    'setting_section_id' // Section
	    );

	    add_settings_field(
		    'woo_api_endpoint', // ID
		    'The url used to talk to the WooCommerce API', // Title
		    array( $this, 'woo_api_endpoint_callback' ), // Callback
		    'comhub-fi-funnels', // Page
		    'setting_section_id' // Section
	    );

	    add_settings_field(
		    'woo_payment_type', // ID
		    'The payment type WooCommerce uses to log the orders ', // Title
		    array( $this, 'woo_payment_type_callback' ), // Callback
		    'comhub-fi-funnels', // Page
		    'setting_section_id' // Section
	    );



	    add_settings_field(
		    'log_name', // ID
		    'Log Name For Debugging', // Title
		    array( $this, 'log_name_callback' ), // Callback
		    'comhub-fi-funnels', // Page
		    'setting_section_id' // Section
	    );

	    add_settings_field(
		    'automatic_product_upsell', // ID
		    'Post ID that will be upsold', // Title
		    array( $this, 'automatic_product_upsell_callback' ), // Callback
		    'comhub-fi-funnels', // Page
		    'setting_section_id' // Section
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

		if( isset( $input['automatic_product_upsell'] ) ) {
			$new_input['automatic_product_upsell'] = sanitize_text_field( $input['automatic_product_upsell'] );
		}


		if( isset( $input['allowed_senders'] ) ) {
			$one_string = $input['allowed_senders'];
			$allowed_array_raw = preg_split('/\r\n|\r|\n/', $one_string);
			if ($allowed_array_raw === false) {$allowed_array_raw = [];}

			$allowed_array = [];
			foreach ($allowed_array_raw as $allowed_raw) {
				$allowed_raw = trim($allowed_raw,"\"', \t\n\r\0\x0B");
				if( preg_match("/^\/.+\/[a-z]*$/i",$allowed_raw)) {
					$allowed = $allowed_raw;
				} else {
					if (strpos($allowed_raw, '<') !== false) {
						$allowed = preg_replace('/.*<(.*)>.*/',"$1",$allowed_raw);
					} else {
						$allowed = $allowed_raw;
					}
				}


				$allowed = sanitize_text_field($allowed);
				if (!empty($allowed)) {
					array_push($allowed_array,$allowed);
				}
			}

			$new_input['allowed_senders'] = $allowed_array;
		}


		if( isset( $input['is_listening'] ) ) {
			$new_input['is_listening'] = sanitize_text_field( $input['is_listening'] );
		} else {
			$new_input['is_listening'] = '0' ;
		}

		if( isset( $input['woo_rest_api_key'] ) ) {
			$new_input['woo_rest_api_key'] = sanitize_text_field( $input['woo_rest_api_key'] );
		} else {
			$new_input['woo_rest_api_key'] = '' ;
		}

		if( isset( $input['woo_rest_api_secret'] ) ) {
			$new_input['woo_rest_api_secret'] = sanitize_text_field( $input['woo_rest_api_secret'] );
		} else {
			$new_input['woo_rest_api_secret'] = '' ;
		}

		if( isset( $input['woo_api_endpoint'] ) ) {
			$new_input['woo_api_endpoint'] = sanitize_text_field( $input['woo_api_endpoint'] );
		} else {
			$new_input['woo_api_endpoint'] = '' ;
		}

		if( isset( $input['woo_payment_type'] ) ) {
			$new_input['woo_payment_type'] = sanitize_text_field( $input['woo_payment_type'] );
		} else {
			$new_input['woo_payment_type'] = '' ;
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

	public function automatic_product_upsell_callback() {
		printf(
			'<input type="text" id="automatic_product_upsell" name="ecomhub_fi_options[automatic_product_upsell]" value="%s" />',
			isset( $this->options['automatic_product_upsell'] ) ? esc_attr( $this->options['automatic_product_upsell']) : ''
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

	public function allowed_senders_callback() {

		$array =  $this->options['allowed_senders'] ;
		if (!is_array($array)) {
			$array = [$array];
		}
		$lines_as_one_string = implode("\n",$array);

		printf(
			'
					<div style="display: inline-block">
 						<textarea  id="allowed_senders" name="ecomhub_fi_options[allowed_senders]" rows="4" cols="55" >%s</textarea>
 						<br>
 						<span style="font-size: smaller">One address per line, and can be in three formats: plain email address, bracked notation ,
 						 or regular expression. Regular expressions need to start with / and end with /, and can only match the plain email and not bracketed forms </span>
                    </div>',
			$lines_as_one_string
		);

	}


	public function woo_rest_api_key_callback() {
		printf(
			'<input type="text" id="woo_rest_api_key" name="ecomhub_fi_options[woo_rest_api_key]" value="%s" size="40" />',
			isset( $this->options['woo_rest_api_key'] ) ? esc_attr( $this->options['woo_rest_api_key']) : ''
		);
	}

	public function woo_rest_api_secret_callback() {
		printf(
			'<input type="password" id="woo_rest_api_secret"
 						name="ecomhub_fi_options[woo_rest_api_secret]"   size="40" value="%s"
 						 autocomplete="woocommerce-api-secret"
 						 />',
			isset( $this->options['woo_rest_api_secret'] ) ? esc_attr( $this->options['woo_rest_api_secret']) : ''
		);
	}

	public function woo_api_endpoint_callback() {
		printf(
			'<input type="text" id="woo_api_endpoint" name="ecomhub_fi_options[woo_api_endpoint]"  size="40" value="%s" />',
			isset( $this->options['woo_api_endpoint'] ) ? esc_attr( $this->options['woo_api_endpoint']) : ''
		);
	}

	public function woo_payment_type_callback() {
		//get all the woo payment types that are enabled
		$gateways = WC()->payment_gateways->get_available_payment_gateways();
		$enabled_gateways = [];

		if( $gateways ) {
			foreach( $gateways as $gateway ) {

				if( $gateway->enabled == 'yes' ) {

					$enabled_gateways[] = $gateway;

				}
			}
		}
		print "<select id=\"woo_payment_type\" name=\"ecomhub_fi_options[woo_payment_type]\">";
		foreach ($enabled_gateways as $gateway) {
			$code = $gateway->id;
			$name = $gateway->title;
			$default = '';
			if (isset($this->options['woo_payment_type']) ) {
				if ($code == $this->options['woo_payment_type']) {
					$default = "selected=\"selected\"";
				}
			}
			printf(
				'<option value="%s"  %s >%s</option>',
				$code,$default, $name
			);
		}
		print "</select>";
	}






    public function query_survey_ajax_handler() {

	    require_once plugin_dir_path(dirname(__FILE__)) . 'admin/ecomhub-fi-list-events.php';

        check_ajax_referer( 'Ecomhub_Fi_Admin' );

        if (array_key_exists( 'method',$_POST) && $_POST['method'] == 'stats') {
	        global $ecombhub_fi_stats_object;
            try {
	            $ecombhub_fi_stats_object = EcomhubFiListEvents::get_stats_array();
	            ob_start();
	            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ecomhub-fi-admin-stats.php';
	            $html = ob_get_contents();
	            ob_end_clean();
	            $ecombhub_fi_stats_object->html = $html;
                wp_send_json(['is_valid' => true, 'data' => $ecombhub_fi_stats_object, 'action' => 'stats']);
                die();
            } catch (Exception $e) {
                wp_send_json(['is_valid' => false, 'message' => $e->getMessage(), 'trace'=>$e->getTrace(), 'action' => 'stats' ]);
                die();
            }
        }

	    elseif (array_key_exists( 'method',$_POST) && $_POST['method'] == 'x_posts') {
		    global $ecombhub_fi_posts_array;
		    try {
		    	$data = [];
		    	if (isset($_POST['unbind'])) {
		    		$post_id_unbind = intval($_POST['unbind']);
				    $product_id = sanitize_text_field($_POST['product_id']);
		    		if ($post_id_unbind > 0 && $product_id) {
					    $b_unbind_result = EcomhubFiListEvents::unbind_post_from_funnel($post_id_unbind,$product_id);
					    $data['unbinding_id'] = $post_id_unbind;
					    $data['unbinding_result'] =$b_unbind_result;
				    } else {
		    			throw new Exception("Need both a unbind and a product id to unbind");
				    }
			    }

			    if (isset($_POST['bind'])) {
				    $post_id_bind = intval($_POST['bind']);
				    if ($post_id_bind > 0) {
				    	$product_id = sanitize_text_field($_POST['product_id']);
					    $b_bind_result = EcomhubFiListEvents::bind_post_to_funnel($post_id_bind,$product_id);
					    $data['binding_id'] = $post_id_bind;
					    $data['product_id'] = $product_id;
					    $data['$b_bind_result'] =$b_bind_result;
				    }
			    }
			    $ecombhub_fi_posts_array = EcomhubFiListEvents::get_store_funnel_codes();
			    ob_start();
			    require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ecomhub-fi-admin-meta-posts.php';
			    $html = ob_get_contents();
			    ob_end_clean();
			    $data['posts'] = $ecombhub_fi_posts_array;
			    $data['html'] = $html;
			    wp_send_json(['is_valid' => true, 'data' => $data, 'action' => 'x_posts']);
			    die();
		    } catch (Exception $e) {
			    wp_send_json(['is_valid' => false, 'message' => $e->getMessage(), 'trace'=>$e->getTrace(), 'action' => 'stats' ]);
			    die();
		    }
	    }
        elseif (array_key_exists( 'method',$_POST) && $_POST['method'] == 'update_funnel') {
	        global $ecombhub_fi_details_object;
	        require_once plugin_dir_path(dirname(__FILE__)) . 'scripts/connect_order.php';
	        try {
		        EcomhubFiConnectOrder::sort_all_the_good_and_ugly();
		        $ecombhub_fi_details_object = EcomhubFiListEvents::get_details_of_one(intval($_POST['id']));
		        ob_start();
		        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ecomhub-fi-admin-detail.php';
		        $html = ob_get_contents();
		        ob_end_clean();
		        $ecombhub_fi_details_object->html = $html;
		        wp_send_json(['is_valid' => true, 'data' => $ecombhub_fi_details_object, 'action' => 'update_funnel']);
		        die();
	        } catch (Exception $e) {
		        wp_send_json(['is_valid' => false, 'message' => $e->getMessage(), 'trace'=>$e->getTrace(), 'action' => 'update_funnel' ]);
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
		        $ecombhub_fi_details_object->html = $html;
		        wp_send_json(['is_valid' => true, 'data' => $ecombhub_fi_details_object, 'action' => 'detail']);
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
