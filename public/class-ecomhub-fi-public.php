<?php


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

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {


        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/ecomhub-fi-public.css', array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__DIR__) . 'lib/Chart.min.js', array('jquery'), $this->version, false);
        wp_enqueue_script($this->plugin_name. 'a', plugin_dir_url(__FILE__) . 'js/ecomhub-fi-public.js', array('jquery'), $this->version, false);
        $title_nonce = wp_create_nonce('ecombhub_fi_public');
        wp_localize_script('ecomhub-fi', 'ecombhub_fi_chart_ajax_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'action' => 'ecombhub_fi_public',
            'nonce' => $title_nonce,
        ));

    }

    //JSON
    function send_survey_ajax_handler()
    {

        check_ajax_referer('ecombhub_fi_public');

        wp_send_json(['is_valid' => true, 'message' => "hi", 'test' => $_POST['test']]);
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