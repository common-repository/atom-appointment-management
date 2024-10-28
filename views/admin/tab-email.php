<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<p>
	<?php _e('E-Mail text templates allow you to save time by predefining answers to your customer\'s appointment requests.', 'atom-appointment-management'); ?>
</p>

<hr />

<form method="post" action="options.php" enctype="multipart/form-data" class="atom-email atom-settings" autocomplete="off">
	<h2><?php _e('Placeholder for customer data', 'atom-appointment-management'); ?></h2>
	<p class="atom_description">
		<?php _e('Use these placeholders in your template text and they will be dynamically replaced with the appropriate data for each appointment.', 'atom-appointment-management'); ?>
	</p>
	<table>
		<tbody>
			<?php
			foreach ($this->get_option('formfields') as $field) {
				$field_id = $field['id'];
				echo '
				<tr>
					<td>((' . $field['id'] . '))</td>
					<td>' . $field['label'] . '</td>
				</tr>
				';
			}
			?>
			<tr>
				<td>((title))</td>
				<td><?php _e('Appointment title', 'atom-appointment-management'); ?></td>
			</tr>
			<tr>
				<td>((date))</td>
				<td><?php _e('Date of the appointment', 'atom-appointment-management'); ?></td>
			</tr>
			<tr>
				<td>((time))</td>
				<td><?php _e('Time slot of the appointment (e.g. 10:00 - 11:00)', 'atom-appointment-management'); ?></td>
			</tr>
		</tbody>


	</table>

	<?php
	settings_fields("atom_booking_email");
	do_settings_sections("atom_booking_email");
	submit_button();
	?>
</form>
