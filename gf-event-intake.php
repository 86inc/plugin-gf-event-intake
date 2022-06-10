<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              86inc.co
 * @since             1.0.0
 * @package           Gf_Event_Intake
 *
 * @wordpress-plugin
 * Plugin Name:       Gravity Forms Event Intake
 * Plugin URI:        newyorkdreamtickets.com
 * Description:       Create events and performance products for newyorkdreamtickets.com.
 * Version:           1.3
 * Author:            Jonathan Bouganim
 * Author URI:        86inc.co
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       gf-event-intake
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'GF_EVENT_INTAKE_VERSION', '1.0.0' );
define( 'GF_EVENT_INTAKE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-gf-event-intake-activator.php
 */
function activate_gf_event_intake() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-gf-event-intake-activator.php';
	Gf_Event_Intake_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-gf-event-intake-deactivator.php
 */
function deactivate_gf_event_intake() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-gf-event-intake-deactivator.php';
	Gf_Event_Intake_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_gf_event_intake' );
register_deactivation_hook( __FILE__, 'deactivate_gf_event_intake' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-gf-event-intake.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_gf_event_intake() {

	$plugin = new Gf_Event_Intake();
	$plugin->run();

}
run_gf_event_intake();

add_action('init','psp_add_role_caps',999);
function psp_add_role_caps() {

    // Add the roles you'd like to administer the custom post types
    $roles = array('customer');
    
    // Loop through each role and assign capabilities
    foreach($roles as $the_role) { 

        $role = get_role($the_role);
        $role->add_cap( 'read' );
        $role->add_cap( 'read_event');
        $role->add_cap( 'read_events');
        //$role->add_cap( 'read_private_events' );
        //$role->add_cap( 'edit_events' );
        //$role->add_cap( 'edit_events' );
        //$role->add_cap( 'edit_others_events' );
        $role->add_cap( 'edit_events' );
        $role->add_cap( 'edit_event' );
        //$role->add_cap( 'publish_events' );
        //$role->add_cap( 'delete_others_events' );
        //$role->add_cap( 'delete_private_events' );
        //$role->add_cap( 'delete_published_events' );
    
    }

    // Add the roles you'd like to administer the custom post types
    $roles = array('administrator', 'editor');
    
    // Loop through each role and assign capabilities
    foreach($roles as $the_role) { 

        $role = get_role($the_role);
        //$role->add_cap( 'read' );
        $role->add_cap( 'read_events');
        $role->add_cap( 'read_event');

        $role->add_cap( 'read_private_event' );
        $role->add_cap( 'read_private_events' );

        $role->add_cap( 'edit_events' );
        $role->add_cap( 'edit_event' );

        $role->add_cap( 'edit_others_events' );
        $role->add_cap( 'edit_others_event' );

        $role->add_cap( 'publish_events' );
        $role->add_cap( 'publish_event' );

        $role->add_cap( 'delete_events' );
        $role->add_cap( 'delete_others_events' );
        $role->add_cap( 'delete_private_events' );
        $role->add_cap( 'delete_published_events' );

        $role->add_cap( 'delete_event' );
        $role->add_cap( 'delete_others_event' );
        $role->add_cap( 'delete_private_event' );
        $role->add_cap( 'delete_published_event' );
    
    }
}