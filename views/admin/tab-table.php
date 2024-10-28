<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once(ATOM_AAM_PLUGIN_PATH . "classes/entrytable.class.php");
$formfields = $this->get_option('formfields');
$categories = $this->get_option('categories');
?>

<h2><?php _e('Current Appointments', 'atom-appointment-management') ?></h2>

<?php
$atom_booking_entrytable = new Atom_AAM_Entry_Table(false, $formfields, $categories);
?>

<h2><?php _e('Cancelled & Past Appointments', 'atom-appointment-management') ?></h2>

<?php
if (isset($_GET['action']) && $_GET['action'] == "show_archive") {
	$atom_booking_entrytable_archive = new Atom_AAM_Entry_Table(true, $formfields, $categories);
} else {
	echo "<a href='admin.php?page=" . ATOM_AAM_PLUGIN_SLUG . "&action=show_archive' class='button'>" . __('Show Archive', 'atom-appointment-management') . "</a>";
}
