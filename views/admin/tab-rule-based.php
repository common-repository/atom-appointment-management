<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<p>
	<?php _e('Rule-based appointment slots are defined by fixed parameters like your office hours. Use this for appointments that are available every week during fixed office hours.', 'atom-appointment-management'); ?>
</p>

<hr />

<form method="post" action="options.php" enctype="multipart/form-data" class="atom-settings" autocomplete="off">
	<?php
	settings_fields("atom_booking_rulebased");
	do_settings_sections("atom_booking_rulebased");
	submit_button();
	?>
</form>

<hr />

<form method="post" action="admin.php?page=<?php echo ATOM_AAM_PLUGIN_SLUG; ?>-rule-based" autocomplete="off">
	<h2><?php _e('Add exception', 'atom-appointment-management'); ?></h2>
	<table class="form-table">
		<tr>
			<th scope="row"><?php _e('Full day', 'atom-appointment-management'); ?></th>
			<td><input type="checkbox" name="atom_exception_fullday" id="atom_exception_fullday" checked/></td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Start', 'atom-appointment-management'); ?></th>
			<td>
				<input type="text" name="atom_exception_begin_readable" data-target="atom_exception_begin" class="atom_datepicker" />
				<input type="text" name="atom_exception_begin" id="atom_exception_begin" class="atom_datepicker_value" />
				<input type="time" name="atom_exception_begin_time" class="excpt_hide" placeholder="00:00" />
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('End', 'atom-appointment-management'); ?></th>
			<td>
				<input type="text" name="atom_exception_end_readable" data-target="atom_exception_end" class="atom_datepicker" />
				<input type="text" name="atom_exception_end" id="atom_exception_end" class="atom_datepicker_value" />
				<input type="time" name="atom_exception_end_time" class="excpt_hide" placeholder="00:00" />
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Description', 'atom-appointment-management'); ?></th>
			<td><input type="text" name="atom_exception_description" id="atom_exception_description" /></td>
		</tr>
		<tr>
			<th scope="row"><?php _e('For category', 'atom-appointment-management'); ?></th>
			<td>
				<div class="aam-pro-blur">
					<select name="atom_exception_category" id="atom_exception_category">
						<option value="-1">─── <?php _e('All', 'atom-appointment-management'); ?> ───</option>
					</select>
				</div>
			</td>
		</tr>
		<tr>
			<td colspan="2" class="atom_description">
			</td>
		</tr>
	</table>
	<p class="submit"><input type="submit" name="atom_submit_exception" id="submit" class="button" value="<?php _e('Add exception', 'atom-appointment-management'); ?>"  /></p>
</form>

<hr />

<h2><?php _e('Import holidays', 'atom-appointment-management'); ?></h2>

<div class="atom-settings">
	<table class="form-table">
		<tr>
			<td>
				<form class="aam-pro-blur">
					<select name="atom_aam_pro">
						<option value="austria"><?php _e('Austria', 'atom-appointment-management'); ?></option>
					</select>
					<input type="submit" name="atom_aam_pro" value="<?php _e('Import holidays', 'atom-appointment-management'); ?>" class="button" disabled />
				</form>
			</td>
		</tr>
		<tr>
			<td colspan="2" class="atom_description">
				<p><?php _e('Statutory holidays for the next three years will be added to the list of exceptions.', 'atom-appointment-management'); ?></p>
				<?php
				global $atom_appointment_management_admin;
				$atom_appointment_management_admin->display_pro_info();
				?>
			</td>
		</tr>
	</table>
</div>

<hr />

<h2><?php _e('Exceptions', 'atom-appointment-management'); ?></h2>
<?php
$categories = $this->get_option('categories');
require_once(ATOM_AAM_PLUGIN_PATH . "classes/excpttable.class.php");
$atom_booking_excpttable = new Atom_AAM_Exception_Table($categories);
?>
