<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if (isset($_GET['override-category-settings'])) {
	require_once(ATOM_AAM_PLUGIN_PATH . "views/admin/tab-category-override.php");
	return;
}
?>

<div class="installation-guide">
	<img src="<?php echo plugins_url(ATOM_AAM_PLUGIN_SLUG) . "/img/aam-icon.jpg"; ?>" alt="ATOM Appointment Management">
	<span><?php _e('Add the calendar widget to any page by pasting this shortcode into the editor.', 'atom-appointment-management'); ?></span>
	<table>
		<tr>
			<td><?php _e('All appointments', 'atom-appointment-management'); ?>:</td>
			<td><input class="atom_shortcode_copy" type="text" value='[atom-appointment]' readonly /></td>
		</tr>
	</table>
	<span><?php _e('You want even more features like single or repeating appointment slots, ICS calendar subscription, categories and much more?', 'atom-appointment-management'); ?></span>
	<a href="<?php echo ATOM_AAM_PLUGIN_INFOPAGE; ?>" target="_blank" class="button"><?php _e('Get AAM PRO', 'atom-appointment-management'); ?></a>
</div>

<form method="post" action="options.php" enctype="multipart/form-data" class="atom-settings" autocomplete="off">
	<?php
	wp_enqueue_media();

	settings_fields("atom_booking_settings");
	do_settings_sections("atom_booking_settings");
	submit_button();
	?>
</form>
