<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

global $atom_appointment_management_admin;

$category_id = intval($_GET['override-category-settings']);

if (isset($_POST['submit'])) {
	$options = get_option('atom_aam_settings');

	$post_validated = $atom_appointment_management_admin->validate_input($_POST);

	$options['categories'][$category_id]['override_settings'] = array(
		'notif_mail'			=> sanitize_text_field($post_validated['notif_mail']),
		'override_rulebased' 	=> isset($post_validated['override_rulebased']),
		'exchange_activated'	=> isset($post_validated['exchange_activated']),
		'exchange_server'		=> sanitize_text_field($post_validated['exchange_server']),
		'exchange_username'		=> sanitize_text_field($post_validated['exchange_username']),
		'exchange_password'		=> sanitize_text_field($post_validated['exchange_password'])
	);

	if (isset($post_validated['override_rulebased'])) {
		$options['categories'][$category_id]['override_settings']['workdays'] = $post_validated['workdays'];
		$options['categories'][$category_id]['override_settings']['event_duration'] = $post_validated['event_duration'];
		$options['categories'][$category_id]['override_settings']['event_gap'] = $post_validated['event_gap'];
		$options['categories'][$category_id]['override_settings']['bookings_per_slot_rulebased'] = $post_validated['bookings_per_slot_rulebased'];
		$options['categories'][$category_id]['override_settings']['rulebased_title'] = $post_validated['rulebased_title'];
		$options['categories'][$category_id]['override_settings']['rulebased_moreinfo'] = $post_validated['rulebased_moreinfo'];
	}


	update_option('atom_aam_settings', $options);
	$atom_appointment_management_admin->aam->update_options();
}

$options = get_option('atom_aam_settings');
$category_options = $options['categories'][$category_id];

?>

<form method="post" action="<?php echo admin_url('admin.php?page=' . ATOM_AAM_PLUGIN_SLUG . '-settings&override-category-settings=' . $category_id); ?>" class="atom-settings" autocomplete="off">

	<h2><?php echo sprintf(__('Override settings for category "%s"', 'atom-appointment-management'), $category_options['name']); ?></h2>

	<table class="form-table">
		<tbody>

			<tr>
				<th scope="row"><?php _e('E-mail address for notifications', 'atom-appointment-management'); ?></th>
				<td>
					<input type="email" name="notif_mail" value="<?php echo (isset($category_options['override_settings']['notif_mail']) ? $category_options['override_settings']['notif_mail'] : ''); ?>" />
				</td>
			</tr>

			<tr>
				<td colspan="2" class="atom_description">
					<hr>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label><?php _e('ICS Calendar subscription', 'atom-appointment-management'); ?></label>
				</th>
				<td>
					<?php if ($atom_appointment_management_admin->get_option('ics_key') == '') {
						_e('Please enable calendar subscriptions in the general settings to use this feature.', 'atom-appointment-management');
					} else {
						$ics_link = plugins_url(ATOM_AAM_PLUGIN_SLUG) . '/ics/?key=' . $atom_appointment_management_admin->get_option('ics_key') . '$0' . $category_id;
						echo '<b>' . __('Your calendar link:', 'atom-appointment-management') . '</b> ';
						echo '<input class="atom_shortcode_copy" type="text" value="' . $ics_link . '" readonly size="120" /><br />';
						echo sprintf(__("This calendar will only show appointments made for this category (%s).", 'atom-appointment-management'), $category_options['name']) . '<br />';
					}
					?>
				</td>
			</tr>

			<tr>
				<td colspan="2" class="atom_description">
					<hr>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="override_rulebased"><?php _e('Enable rule-based slots for this category', 'atom-appointment-management'); ?></label>
				</th>
				<td>
					<input type="checkbox" name="override_rulebased" id="override_rulebased" <?php echo (($category_options['override_settings']['override_rulebased']) ? 'checked' : ''); ?> />
				</td>
			</tr>

			<tr>
				<td colspan="2" class="atom_description">
				</td>
			</tr>

		</tbody>
	</table>

	<div class="override-rulebased atom-settings">
		<table class="form-table">
			<tbody>

				<tr>
					<th scope="row"><?php _e('Edit work days and hours', 'atom-appointment-management'); ?></th>
					<td>
						<?php
						$opt = $atom_appointment_management_admin->get_category_override_setting($category_id, 'workdays');

						$day_names = array(
							__('Monday', 'atom-appointment-management'),
							__('Tuesday', 'atom-appointment-management'),
							__('Wednesday', 'atom-appointment-management'),
							__('Thursday', 'atom-appointment-management'),
							__('Friday', 'atom-appointment-management'),
							__('Saturday', 'atom-appointment-management'),
							__('Sunday', 'atom-appointment-management')
						)
						?>

						<table>
							<tr>
								<td></td>
								<td><?php _e('Start', 'atom-appointment-management'); ?></td>
								<td><?php _e('End', 'atom-appointment-management'); ?></td>
							</tr>

							<?php for ($i = 0; $i < 7; $i++) { ?>
								<tr>
									<td><input type="checkbox" data-target="<?php echo $i; ?>" class="atom_check_workdays" <?php $atom_appointment_management_admin->isInactiveWorkday($opt[$i], '', 'checked'); ?>> <?php echo $day_names[$i]; ?></td>
									<td><input type="time" name="workdays[<?php echo $i; ?>][start]" value="<?php echo $opt[$i]['start'] ?>" <?php $atom_appointment_management_admin->isInactiveWorkday($opt[$i], 'readonly'); ?> /></td>
									<td><input type="time" name="workdays[<?php echo $i; ?>][end]" value="<?php echo $opt[$i]['end'] ?>" <?php $atom_appointment_management_admin->isInactiveWorkday($opt[$i], 'readonly'); ?> /></td>
								</tr>
							<?php } ?>

						</table>
					</td>
				</tr>

				<tr>
					<td colspan="2" class="atom_description">
						<hr>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php echo __('Duration of appointments', 'atom-appointment-management') . ' (hh:mm)'; ?></th>
					<td>
						<input type="time" name="event_duration" id="atom_event_duration" value="<?php echo $atom_appointment_management_admin->get_category_override_setting($category_id, 'event_duration'); ?>" />
						</td></tr><tr><td colspan="2" class="atom_description">
						<p>
							<?php _e('The duration of a single appointment in hours and minutes', 'atom-appointment-management'); ?>
						</p>
						<hr />
					</td>
				</tr>

				<tr>
					<th scope="row"><?php echo __('Break between appointments', 'atom-appointment-management') . ' (hh:mm)'; ?></th>
					<td>
						<input type="time" name="event_gap" id="atom_event_gap" value="<?php echo $atom_appointment_management_admin->get_category_override_setting($category_id, 'event_gap'); ?>" />
						</td></tr><tr><td colspan="2" class="atom_description">
						<p>
							<?php _e('The duration between two appointments in hours and minutes.', 'atom-appointment-management'); ?>
						</p>
						<hr />
					</td>
				</tr>

				<tr>
					<th scope="row"><?php _e('Appointment title', 'atom-appointment-management'); ?></th>
					<td>
						<input type="text" name="rulebased_title" id="atom_rulebased_title" value="<?php echo $atom_appointment_management_admin->get_category_override_setting($category_id, 'rulebased_title'); ?>" />
						</td></tr><tr><td colspan="2" class="atom_description">
						<p>
							<?php _e('This title will be displayed in the calendar and when users book the appointment.', 'atom-appointment-management'); ?>
						</p>
						<hr />
					</td>
				</tr>

				<tr>
					<th scope="row"><?php _e('More information link', 'atom-appointment-management'); ?></th>
					<td>
						<?php
						$selected_option = $atom_appointment_management_admin->get_category_override_setting($category_id, 'rulebased_moreinfo');
						$selected_option = (is_array($selected_option)) ? $selected_option : array('select' => '', 'input' => '');
						?>
						<div class="atom_urlselector">
							<select name="rulebased_moreinfo[select]" id="atom_rulebased_moreinfo">
								<option value="">─ <?php _e('None', 'atom-appointment-management'); ?> ─</option>
								<option value="external" <?php echo selected( 'external', $selected_option['select']); ?>><?php _e('External URL', 'atom-appointment-management'); ?></option>
								<?php
								echo '<option disabled>─── ' . __('Pages', 'atom-appointment-management') . ' ───</option>';
								if( $pages = get_pages() ){
									foreach( $pages as $page ){
										echo '<option value="' . $page->ID . '" ' . selected( $page->ID, $selected_option['select'] ) . '>' . $page->post_title . '</option>';
									}
								}
								echo '<option disabled>─── ' . __('Posts', 'atom-appointment-management') . ' ───</option>';
								if( $pages = get_posts() ){
									foreach( $pages as $page ){
										echo '<option value="' . $page->ID . '" ' . selected( $page->ID, $selected_option['select'] ) . '>' . $page->post_title . '</option>';
									}
								}
								?>
							</select>
							<input type="url" name="rulebased_moreinfo[input]" value="<?php echo $selected_option['input']; ?>" placeholder="https://www.example.com" />
						</div>
						</td></tr><tr><td colspan="2" class="atom_description">
						<p>
							<?php _e('This title will be displayed in the calendar and when users book the appointment.', 'atom-appointment-management'); ?>
						</p>
						<hr />
					</td>
				</tr>

				<tr>
					<th scope="row"><?php _e('Maximum number of appointments per time slot', 'atom-appointment-management'); ?></th>
					<td>
						<input type="number" name="bookings_per_slot_rulebased" id="atom_bookings_per_slot_rulebased" value="<?php echo $atom_appointment_management_admin->get_category_override_setting($category_id, 'bookings_per_slot_rulebased'); ?>" min="1" />
						</td></tr><tr><td colspan="2" class="atom_description">
						<p>
							<?php _e('The number of appointments that can be booked for the same time slot.', 'atom-appointment-management'); ?>
						</p>
						<hr />
					</td>
				</tr>

			</tbody>
		</table>
	</div>

	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><?php _e('Override Microsoft Exchange Connection', 'atom-appointment-management'); ?></th>
				<td>
					<input type="checkbox" name="exchange_activated" id="exchange_activated" <?php echo (($category_options['override_settings']['exchange_activated']) ? 'checked' : ''); ?> />
				</td>
			</tr>

			<tr>
				<td colspan="2" class="atom_description">
					<p>
						<?php
						_e('Sync your appointments immediately with your Microsoft Exchange Calendar. Existing calendar entries will block the time slot for new appointments.', 'atom-appointment-management');
						echo ' ';
						_e('Exchange muss in den globalen Einstellungen aktiviert sein.', 'atom-appointment-management');
						?>
					</p>
					<hr>
				</td>
			</tr>
		</tbody>
	</table>
	<div class="aam-exchange-settings atom-settings">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php _e('Exchange Server', 'atom-appointment-management'); ?></th>
					<td>
						<input type="text" name="exchange_server" value="<?php echo (isset($category_options['override_settings']['exchange_server']) ? $category_options['override_settings']['exchange_server'] : ''); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e('Username', 'atom-appointment-management'); ?></th>
					<td>
						<input type="text" name="exchange_username" value="<?php echo (isset($category_options['override_settings']['exchange_username']) ? $category_options['override_settings']['exchange_username'] : ''); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e('Password', 'atom-appointment-management'); ?></th>
					<td>
						<input type="password" name="exchange_password" value="<?php echo (isset($category_options['override_settings']['exchange_password']) ? $category_options['override_settings']['exchange_password'] : ''); ?>" />
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<?php submit_button(); ?>

	<a href="<?php echo admin_url('admin.php?page=' . ATOM_AAM_PLUGIN_SLUG . '-settings'); ?>" class="button">
		<?php _e('Back to General Settings', 'atom-appointment-management'); ?>
	</a>

</form>
