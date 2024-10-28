<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

global $wpdb;

$appointment_id = intval($_GET['i']);
$action = sanitize_text_field($_GET['a']);

$appointment = $atom_appointment_management->get_appointment_array($appointment_id);

if (!$appointment || !password_verify($appointment['booking_date'], $_GET['atom-appointment-quickreply'])) {
	header('HTTP/1.0 403 Forbidden');
	wp_redirect(get_site_url());
	exit();
}

$automated_emails_enabled = $atom_appointment_management->get_option('automated_emails_enabled');

if ($appointment['confirmed'] === '0') {

	if ($_GET['a'] == "acc") {

		$wpdb->update(
			ATOM_AAM_TABLE_ENTRIES,
			array( 				// data
				'confirmed'	=> 1
			),
			array( 				// where clause
				'id'	=> $appointment_id
			)
		);
		$action = __('confirmed', 'atom-appointment-management');

		if ($automated_emails_enabled) {
			$atom_appointment_management->email->user_notification_appointment_confirmed($appointment);
		} else {
			$subject = $atom_appointment_management->get_option('confirm_subject');
			$body = $atom_appointment_management->get_option('confirm_text');
		}

		wp_schedule_single_event(time(), 'aam_async_appointment_confirmed_tasks', array($appointment_id));

	} else if ($_GET['a'] == "del") {

		$wpdb->update(
			ATOM_AAM_TABLE_ENTRIES,
			array( 				// data
				'confirmed'	=> -1
			),
			array( 				// where clause
				'id'	=> $appointment_id
			)
		);
		$action = __('cancelled', 'atom-appointment-management');

		if ($automated_emails_enabled) {
			$atom_appointment_management->email->user_notification_appointment_canceled($appointment);
		} else {
			$subject = $atom_appointment_management->get_option('cancel_subject');
			$body = $atom_appointment_management->get_option('cancel_text');
		}

		wp_schedule_single_event(time(), 'aam_async_appointment_cancelled_tasks', array($id));

	}

	if (!$automated_emails_enabled) {
		// create email content
		$subject = $atom_appointment_management->filter_fields_placeholders($subject, $appointment);
		$body = $atom_appointment_management->filter_fields_placeholders($body, $appointment);

		$href = "mailto:" . $appointment['fields']['field_email'] . "?subject=" . rawurlencode($subject) . "&body=" . rawurlencode($body);
		$maillink = '<a id="maillink" href="' . $href . '">' . __('here', 'atom-appointment-management') . '</a>';
	}

	$appointment_aleady_processed = false;

} else {

	$appointment_aleady_processed = true;
	$action = ($appointment['confirmed'] == '1') ? __('confirmed', 'atom-appointment-management') : __('cancelled', 'atom-appointment-management');

}

get_header();

?>

<style>
#atom-aam-quick-reply {
	--aam-font: Helvetica, Arial, sans-serif;
	--aam-color-main: <?php echo $atom_appointment_management->get_option('maincolor'); ?>;
	--aam-color-main-transparent: <?php echo $atom_appointment_management->hexToRgb($atom_appointment_management->get_option('maincolor'), 0.05); ?>;
	--aam-color-grey-dark: #707070;
	--aam-color-grey-medium: #B5B5B5;
	--aam-color-grey-light: #DEDEDE;
	--aam-color-bg: <?php echo $atom_appointment_management->get_option('color_bg'); ?>;
	--aam-color-bg-transparent: <?php echo $atom_appointment_management->hexToRgb($atom_appointment_management->get_option('color_bg'), 0.8); ?>;
	--aam-color-text: <?php echo $atom_appointment_management->get_option('color_text'); ?>;
	--aam-color-border: <?php echo $atom_appointment_management->get_option('color_border'); ?>;
}
</style>

<div id="atom-aam-quick-reply">
	<div class="container">
		<h1><?php _e('Appointment Management', 'atom-appointment-management'); ?></h1>

		<?php if ($appointment_aleady_processed) { ?>

			<p><?php echo sprintf(__('The appointment has already been %s.', 'atom-appointment-management'), $action); ?></p>

		<?php } else { ?>

			<p><?php echo sprintf(__('The appointment has been %s.', 'atom-appointment-management'), $action); ?></p>

			<p>
				<?php
				if ($automated_emails_enabled) {
					_e('Your customer has received an e-mail confirmation', 'atom-appointment-management');
				} else {
					echo sprintf(__('Click %s if your email window does not open automatically.', 'atom-appointment-management'), $maillink);
					?>
					<script>
						window.onload = function(e) {
							document.getElementById('maillink').click();
						}
					</script>
					<?php
				}
				?>
			</p>

			<p class="date <?php if ($_GET['a'] == "del") echo 'canceled'; ?>">
				<?php echo ($appointment['title']) ? $appointment['title'] . '<br>' : ''; ?>
				<?php echo $appointment['date']; ?><br />
				<?php echo $appointment['time']; ?>
			</p>
			<p class="user-info">
				<?php
				echo $atom_appointment_management->generate_fields_string($appointment['fields']);
				?>
			</p>

			<?php if ($_GET['a'] == "acc") { ?>
				<form>
					<input type="hidden" name="atom-appointment-quickreply" value="<?php echo esc_attr($_GET['atom-appointment-quickreply']); ?>" />
					<input type="hidden" name="i" value="<?php echo esc_attr($appointment_id); ?>" />
					<input type="hidden" name="a" value="<?php echo esc_attr($_GET['a']); ?>" />
					<input type="hidden" name="dl" value="true" />
					<input type="submit" class="button button-accept" value="<?php _e('Download Calendar (ics) File', 'atom-appointment-management'); ?>">
				</form>
			<?php } ?>

		<?php } ?>

	</div>
	<?php echo '<a class="backlink" href="' . get_bloginfo('url') .'">&larr; ' . __('Go to', 'atom-appointment-management') . ' ' . get_bloginfo('name') . '</a>'; ?>
</div>

<?php get_footer();
