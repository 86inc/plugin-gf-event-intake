<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       86inc.co
 * @since      1.0.0
 *
 * @package    Gf_Event_Intake
 * @subpackage Gf_Event_Intake/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Gf_Event_Intake
 * @subpackage Gf_Event_Intake/includes
 * @author     Jonathan Bouganim <jb@86inc.co>
 */
class Gf_Event_Intake_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'gf-event-intake',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
