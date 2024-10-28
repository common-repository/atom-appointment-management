<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<p>
	<?php _e('Individual appointment slots are ideal for creating one-time events or events that recur during a limited time frame.', 'atom-appointment-management'); ?>
</p>

<hr />

<?php
global $atom_appointment_management_admin;
$atom_appointment_management_admin->display_pro_info();
?>

<form class="aam-pro-blur">
	<h2><?php _e('Add appointment slot', 'atom-appointment-management'); ?></h2>
	<table class="form-table">
		<tr>
			<th scope="row"><?php _e('Title', 'atom-appointment-management'); ?></th>
			<td><input type="text" name="atom_aam_pro" id="atom_slot_title" value="Title" /></td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Date', 'atom-appointment-management'); ?></th>
			<td>
				<input type="text" name="atom_aam_pro" id="atom_slot_day" />
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Start time', 'atom-appointment-management'); ?> (hh:mm)</th>
			<td><input type="time" name="atom_aam_pro" id="atom_slot_time_start" placeholder="00:00" value="" /></td>
		</tr>
		<tr>
			<th scope="row"><?php _e('End time', 'atom-appointment-management'); ?> (hh:mm)</th>
			<td><input type="time" name="atom_aam_pro" id="atom_slot_time_end" placeholder="00:00" value="" /></td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Maximum number of appointments per time slot', 'atom-appointment-management'); ?></th>
			<td><input type="number" name="atom_aam_pro" id="atom_slot_bookings_per_slot" value="1" min="1" value="" /></td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Price', 'atom-appointment-management'); ?></th>
			<td><input type="number" name="atom_aam_pro" id="atom_slot_price" placeholder="<?php _e('Optional', 'atom-appointment-management'); ?>" /></td>
		</tr>
		<tr>
			<th scope="row"><?php _e('More information link', 'atom-appointment-management'); ?></th>
			<td>
				<select name="atom_aam_pro" id="atom_slot_moreinfo">
					<option value="">─ <?php _e('None', 'atom-appointment-management'); ?> ─</option>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Category', 'atom-appointment-management'); ?></th>
			<td>
				<select name="atom_aam_pro">
					<option value="none"><?php _e('None', 'atom-appointment-management'); ?></option>
				</select>
			</td>
		</tr>

		<tr>
			<th scope="row"><?php _e('Recurring', 'atom-appointment-management'); ?></th>
			<td><input type="checkbox" name="atom_aam_pro" id="atom_slot_recurring"/></td>
		</tr>
		<tr class="atom_recurring">
			<th scope="row"><?php _e('Interval', 'atom-appointment-management'); ?></th>
			<td>
				<select name="atom_aam_pro">
					<option value="day"><?php _e('Every day', 'atom-appointment-management'); ?></option>
					<option value="week"><?php _e('Every week', 'atom-appointment-management'); ?></option>
					<option value="month"><?php _e('Every month', 'atom-appointment-management'); ?></option>
				</select>
			</td>
		</tr>
		<tr class="atom_recurring">
			<th scope="row"><?php _e('End of recurrence (leave empty to repeat indefinitely)', 'atom-appointment-management'); ?></th>
			<td>
				<input type="text" name="atom_aam_pro" id="atom_slot_repeat_until" />
			</td>
		</tr>
		<tr>
			<td colspan="2" class="atom_description">
			</td>
		</tr>
	</table>
	<p class="submit"><input type="submit" name="atom_aam_pro" id="submit" class="button" disabled value="<?php _e('Add appointment slot', 'atom-appointment-management'); ?>"  /></p>
</form>
