<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

global $wpdb;

$appointment_id = intval($_GET['i']);
$action = sanitize_text_field($_GET['a']);

$appointment = $atom_appointment_management->get_appointment_array($appointment_id);

if (!$appointment || !password_verify($appointment['booking_date'] . '_user', $_GET['atom-appointment-quickreply'])) {
	header('HTTP/1.0 403 Forbidden');
	wp_redirect(get_site_url());
	exit();
}

$appointment_cancelled = ($appointment['confirmed'] === '-1');

$ccnc_url = get_site_url() . "/?atom-appointment-quickreply=" . urlencode(strip_tags($_GET['atom-appointment-quickreply'])) . "&i=" . $appointment_id . "&a=cnc&c=ccnc";

if (!$appointment_cancelled && isset($_GET['c']) && $_GET['c'] == 'ccnc') {

	$wpdb->update(
		ATOM_AAM_TABLE_ENTRIES,
		array( 				// data
			'confirmed'	=> -1
		),
		array( 				// where clause
			'id'	=> $appointment_id
		)
	);
	wp_schedule_single_event(time(), 'aam_async_appointment_cancelled_tasks', array($appointment_id));

	$atom_appointment_management->email->admin_notification_appointment_cancelled($appointment);

	$appointment_cancelled = true;

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

		<?php if (!$appointment_cancelled) { ?>

			<p><?php _e('Do you really want to cancel your appointment?', 'atom-appointment-management'); ?></p>

			<p class="date <?php if ($_GET['a'] == "del") echo 'canceled'; ?>">
				<?php echo ($appointment['title']) ? $appointment['title'] . '<br>' : ''; ?>
				<?php echo $appointment['date']; ?><br />
				<?php echo $appointment['time']; ?>
			</p>

			<a href="<?php echo $ccnc_url; ?>" class="button button-delete"><?php _e('Cancel appointment', 'atom-appointment-management'); ?></a>

		<?php
		} else {
			_e('Your appointment has been cancelled. Please contact us to find a different arrangement.', 'atom-appointment-management');
		}
		?>

	</div>
	<?php echo '<a class="backlink" href="' . get_bloginfo('url') .'">&larr; ' . __('Go to', 'atom-appointment-management') . ' ' . get_bloginfo('name') . '</a>'; ?>
</div>

<?php get_footer();
