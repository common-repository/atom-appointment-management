<?php

/*
Plugin Name: ATOM Appointment Management
Description: Easily manage and schedule your customer appointments.
Version: 4.1.1
Plugin URI: https://www.atomplugins.com
Author: atomproductions
Author URI: https://www.atomproductions.at
Text Domain: atom-appointment-management
Domain Path: /lang/
*/

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

if (
	!class_exists('Atom_Appointment_Management') &&
	!class_exists('Atom_Appointment_Management_Pro')
) {

	global $wpdb;

	////////////////////////////////////////////////////////////////
	//		Define Constants
	////////////////////////////////////////////////////////////////

	define('ATOM_AAM_PLUGIN_VERSION', '4.1.1');
	define('ATOM_AAM_PLUGIN_SLUG', 'atom-appointment-management');
	define('ATOM_AAM_TABLE_ENTRIES',  $wpdb->prefix . 'aam_entries');
	define('ATOM_AAM_TABLE_EXCEPTIONS',  $wpdb->prefix . 'aam_exceptions');
	define('ATOM_AAM_PLUGIN_PATH', plugin_dir_path(__FILE__));
	define('ATOM_AAM_PLUGIN_INFOPAGE', 'https://www.atomplugins.com/plugins/appointment-management-pro/');

	define('ATOM_AAM_DEBUG', false);

	////////////////////////////////////////////////////////////////
	//		Create plugin class instances
	////////////////////////////////////////////////////////////////

	require_once('vendor/autoload.php');
	require_once('classes/aam.class.php');
	$atom_appointment_management = new Atom_Appointment_Management();

	register_activation_hook(__FILE__, array($atom_appointment_management, 'setup_plugin'));

	if ( is_admin() ) {
		__('Easily manage and schedule your customer appointments.', 'atom-appointment-management');
		require_once("classes/aam-admin.class.php");
		$atom_appointment_management_admin = new Atom_Appointment_Management_Admin($atom_appointment_management);
	}

}
