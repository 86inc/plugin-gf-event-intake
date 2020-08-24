<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       86inc.co
 * @since      1.0.0
 *
 * @package    Gf_Event_Intake
 * @subpackage Gf_Event_Intake/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Gf_Event_Intake
 * @subpackage Gf_Event_Intake/admin
 * @author     Jonathan Bouganim <jb@86inc.co>
 */
class Gf_Event_Intake_Admin {

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
	private $acf_folder;

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
		$this->acf_folder = dirname(__FILE__) . "/acf-json";
		add_action( 'acf/init', [$this,'acf_op_init']);
		add_filter( 'acf/settings/save_json', [$this, 'acf_json_save_point'] );
		add_filter( 'acf/settings/load_json', [$this,'acf_json_load_point'] );
	}

	public function admin_init() {
	}

	public function acf_init() {
		if (function_exists('acf_update_setting') && defined('GOOGLE_MAPS_API_KEY')) {
			acf_update_setting('google_api_key', GOOGLE_MAPS_API_KEY);
		}
	}

	public function acf_op_init() {

        // Check function exists.
        if( function_exists('acf_add_options_sub_page') ) {

            // Add sub page.
            $child = acf_add_options_sub_page(array(
                'page_title'  => __('Site Settings'),
                'menu_title'  => __('Site Settings'),
                'parent_slug' => 'options-general.php',
            ));
        }
    }
	
	public function acf_json_load_point( $paths ) {
		if (!empty($this->acf_folder)) {
			$paths[] = $this->acf_folder;
		}
		return $paths;
	}
	
	public function acf_json_save_point( $path ) {
		return !empty($this->acf_folder) ? $this->acf_folder : $path;  
	}

	/**
	 * Helpers function to pull ACF fields or default.
	 * @param  string  $key     
	 * @param  boolean $default 
	 * @return            
	 */
	public static function get_acf_option( $key = '', $default = false ) {
		if ( !function_exists('get_field') )
			return $default;

		$val = get_field( $key , 'options');
		return !empty( $val ) ? $val : $default;
	}

	/**
	 * Helpers function to pull ACF fields or default from a specific post.
	 * @param  string  $key     
	 * @param  boolean $default 
	 * @return            
	 */
	public static function get_acf_post_option( $key = '', $post_id = false, $default = false ) {
		if ( !function_exists('get_field') )
			return $default;

		$val = get_field( $key , $post_id );
		return !empty( $val ) ? $val : $default;
	}

	/**
	 * Helpers function to pull ACF fields or default from a specific user.
	 * @param  string  $key     
	 * @param  boolean $default 
	 * @return            
	 */
	public static function get_acf_user_option( $key = '', $user_id = false, $default = false ) {
		if ( !function_exists('get_field') )
			return $default;

		$val = get_field( $key , "user_{$user_id}" );
		return !empty( $val ) ? $val : $default;
	}

	/**
	 * Helpers function to pull ACF fields or default from a term meta.
	 * @param  string  $key     
	 * @param  boolean $default 
	 * @return            
	 */
	public static function get_acf_term_option( $key = '', $term_id = false, $default = false ) {
		if ( !function_exists('get_field') )
			return $default;

		$val = get_field( $key , "term_{$term_id}" );
		return !empty( $val ) ? $val : $default;
	}

	/**
	 * Helper function to source array_value or default.	
	 * @param  array   $myarray 
	 * @param  string  $key     
	 * @param  boolean $default 
	 * @return            
	 */
	public static function get_array_value( $myarray = array(), $key = '', $default = false ) {
		return !empty( $myarray[$key] ) ? $myarray[$key] : $default;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Gf_Event_Intake_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Gf_Event_Intake_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/gf-event-intake-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Gf_Event_Intake_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Gf_Event_Intake_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/gf-event-intake-admin.js', array( 'jquery' ), $this->version, false );

	}

}