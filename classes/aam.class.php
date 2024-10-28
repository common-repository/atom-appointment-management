<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once(ATOM_AAM_PLUGIN_PATH . "classes/aam-email.class.php");

if (!class_exists('Atom_Appointment_Management')):
class Atom_Appointment_Management {

	private $options = array();
	private $default_options;
	protected $disabled_options;
	private $view;
	public $email;

	public function __construct() {

		$this->email = new Atom_Appointment_Management_Email($this);

		add_action('init', array($this, 'plugin_init'));
		add_action('template_include', array($this, 'maybe_load_quickreply_template'));

		add_action('wp_enqueue_scripts', array($this, 'register_scripts_and_styles'));
		add_action('plugins_loaded', array($this, 'load_textdomain'));
		add_action('setup_theme', array($this, 'check_version_number'));

		add_action('wp_ajax_atom_appointment_send_user_mail', array($this, 'ajax_send_user_mail'));

		add_action('wp_ajax_atom_appointment_formsubmit', array($this, 'ajax_submit_form'));
		add_action('wp_ajax_nopriv_atom_appointment_formsubmit', array($this, 'ajax_submit_form'));

		add_action('wp_ajax_atom_appointment_fetchevents', array($this, 'ajax_fetch_events'));
		add_action('wp_ajax_nopriv_atom_appointment_fetchevents', array($this, 'ajax_fetch_events'));

		add_action('wp_ajax_atom_appointment_get_frontend_view', array($this, 'get_frontend_view'));
		add_action('wp_ajax_nopriv_atom_appointment_get_frontend_view', array($this, 'get_frontend_view'));

		add_action('wp_ajax_atom_appointment_add_exception', array($this, 'ajax_add_exception'));

		add_action('wp_ajax_atom_appointment_remove_slot', array($this, 'ajax_remove_slot'));

		add_filter('load_textdomain_mofile', array($this, 'filter_formal_textdomain'), 10, 2);

		add_action('wp', array($this, 'cronjob_activation'));
		add_action('aam_daily_cronjob', array($this, 'do_daily_cronjob'));

		add_action('aam_async_after_booking_tasks',  array($this, 'async_after_booking_tasks'));

	}

	////////////////////////////////////////////////////////////////
	//		Functions
	////////////////////////////////////////////////////////////////

	public function get_option($option) {

		if (in_array($option, $this->disabled_options)) {
			return ((isset($this->default_options[$option])) ? $this->default_options[$option] : NULL );
		}

		return (isset($this->options[$option])) ? $this->options[$option] : ( (isset($this->default_options[$option])) ? $this->default_options[$option] : NULL );

	}

	public function plugin_init() {

		$this->update_options();
		require_once(ATOM_AAM_PLUGIN_PATH . "includes/default-options.php");
		$this->default_options = $default_options;
		$this->disabled_options = array('formfields', 'categories');

	    add_shortcode('atom-appointment', array($this, 'generate_widget'));

	}

	public function update_options() {
		$this->options = array_merge(
			$this->return_option_or_empty_array('atom_aam_settings'),
			$this->return_option_or_empty_array('atom_aam_settings_rulebased'),
			$this->return_option_or_empty_array('atom_aam_settings_email')
		);
	}

	public function generate_widget($atts, $content = null) {
		wp_enqueue_script('jquery');
		wp_enqueue_style('aam-fullcalendar-css');
		wp_enqueue_style('aam-appointment-css');
		wp_enqueue_script('aam-dependencies-js');
		wp_enqueue_script('aam-js');

		$admin = (current_user_can('administrator')) ? ' data-admin="true"' : '';

		$output = '
			<!-- ATOM Appointment Management v' . ATOM_AAM_PLUGIN_VERSION . ' by atomproductions, www.atomplugins.com -->
			<div id="atom-appointment-management"' . $admin . '>
				<div class="atom-aam-loadingindicator">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M495.9 166.6C499.2 175.2 496.4 184.9 489.6 191.2L446.3 230.6C447.4 238.9 448 247.4 448 256C448 264.6 447.4 273.1 446.3 281.4L489.6 320.8C496.4 327.1 499.2 336.8 495.9 345.4C491.5 357.3 486.2 368.8 480.2 379.7L475.5 387.8C468.9 398.8 461.5 409.2 453.4 419.1C447.4 426.2 437.7 428.7 428.9 425.9L373.2 408.1C359.8 418.4 344.1 427 329.2 433.6L316.7 490.7C314.7 499.7 307.7 506.1 298.5 508.5C284.7 510.8 270.5 512 255.1 512C241.5 512 227.3 510.8 213.5 508.5C204.3 506.1 197.3 499.7 195.3 490.7L182.8 433.6C167 427 152.2 418.4 138.8 408.1L83.14 425.9C74.3 428.7 64.55 426.2 58.63 419.1C50.52 409.2 43.12 398.8 36.52 387.8L31.84 379.7C25.77 368.8 20.49 357.3 16.06 345.4C12.82 336.8 15.55 327.1 22.41 320.8L65.67 281.4C64.57 273.1 64 264.6 64 256C64 247.4 64.57 238.9 65.67 230.6L22.41 191.2C15.55 184.9 12.82 175.3 16.06 166.6C20.49 154.7 25.78 143.2 31.84 132.3L36.51 124.2C43.12 113.2 50.52 102.8 58.63 92.95C64.55 85.8 74.3 83.32 83.14 86.14L138.8 103.9C152.2 93.56 167 84.96 182.8 78.43L195.3 21.33C197.3 12.25 204.3 5.04 213.5 3.51C227.3 1.201 241.5 0 256 0C270.5 0 284.7 1.201 298.5 3.51C307.7 5.04 314.7 12.25 316.7 21.33L329.2 78.43C344.1 84.96 359.8 93.56 373.2 103.9L428.9 86.14C437.7 83.32 447.4 85.8 453.4 92.95C461.5 102.8 468.9 113.2 475.5 124.2L480.2 132.3C486.2 143.2 491.5 154.7 495.9 166.6V166.6zM256 336C300.2 336 336 300.2 336 255.1C336 211.8 300.2 175.1 256 175.1C211.8 175.1 176 211.8 176 255.1C176 300.2 211.8 336 256 336z"/></svg>
				</div>
			</div>
		';
		return $output;
	}

	public function get_frontend_view() {
		$view = (isset($_POST['view'])) ? sanitize_text_field($_POST['view']) : false;

		switch ($view) {
			case 'calendar':
				$this->view = 'calendar';
				$this->load_view('frontend-calendar');
				break;
		}

		die();
	}

	public function register_scripts_and_styles() {

		wp_register_style('aam-appointment-css', plugins_url(ATOM_AAM_PLUGIN_SLUG) . '/css/aam-styles.min.css', array(), ATOM_AAM_PLUGIN_VERSION);

		// JS Dependencies
		// - moment-with-locales.js 				2.29.1
		// - fullcalendar.min.js					5.8.0
		// - fullcalendar-moment.js					5.8.0
		// - de.js (fullcalendar language pack)		5.8.0
		wp_enqueue_script('aam-dependencies-js', plugins_url(ATOM_AAM_PLUGIN_SLUG) . '/js/dependencies.min.js', array('jquery'), ATOM_AAM_PLUGIN_VERSION);
	    wp_enqueue_script('aam-js', plugins_url(ATOM_AAM_PLUGIN_SLUG) . '/js/atom-appointment.js', array('aam-dependencies-js'), ATOM_AAM_PLUGIN_VERSION);
		wp_localize_script('aam-js', 'aamlocalizevars', array(
				'ajaxurl' 		=> admin_url( 'admin-ajax.php'),
				'pluginpath'	=> plugins_url(ATOM_AAM_PLUGIN_SLUG),
				'language'		=> explode('_', get_locale())[0],
				'min_time'		=> $this->get_min_max_time()[0],
				'max_time'		=> $this->get_min_max_time()[1],
				'week_limit'	=> $this->get_option('weeks_in_advance'),
				'debug'			=> ATOM_AAM_DEBUG,
				'analytics_disable_admins' => $this->get_option('analytics_disable_admins'),
				'strings'		=> array(
					'loading'			=> __('Sending...', 'atom-appointment-management'),
					'error_generic'		=> __('Error, please try again', 'atom-appointment-management'),
					'error_unavailable'	=> __('Unavailable, please choose a different date', 'atom-appointment-management'),
					'today'				=> __('Today', 'atom-appointment-management')
				)
			)
		);
	}

	public function check_version_number() {
		if (ATOM_AAM_PLUGIN_VERSION !== get_option('atom_aam_version')) {
			$this->setup_plugin();
		}
	}

	public function maybe_load_quickreply_template($template) {
		$quickreply_hash = isset($_GET['atom-appointment-quickreply']) ? sanitize_text_field($_GET['atom-appointment-quickreply']) : false;
		$action = isset($_GET['a']) ? sanitize_text_field($_GET['a']) : false;
		$appointment_id = isset($_GET['i']) ? intval($_GET['i']) : false;

		if ($quickreply_hash !== false && $appointment_id !== false && $action !== false) {
			$appointment = $this->get_appointment_array($appointment_id);

			if (
				$action == 'cnc'
				&& $appointment && password_verify($appointment['booking_date'] . '_user', $quickreply_hash)
			) { // user actions

				wp_enqueue_style('aam-appointment-css', plugins_url(ATOM_AAM_PLUGIN_SLUG) . '/css/styles-quickreply.min.css', array(), ATOM_AAM_PLUGIN_VERSION);
				$template = $this->load_view('quick-reply-user', true);
				return $template;

			} else if (
				($action == 'acc' || $action == 'del')
				&& $appointment && password_verify($appointment['booking_date'], $quickreply_hash)
			) { // admin action

				if (isset($_GET['dl'])) {
					$filename = 'appointment_' . date('Y-m-d-H-i', strtotime($appointment['start'])) . '.ics';
					header('Content-type: text/calendar; charset=utf-8');
					header('Content-Disposition: attachment; filename=' . $filename);
					echo "BEGIN:VCALENDAR\r\n";
					echo "VERSION:2.0\r\n";
					echo "PRODID:-//atomproductions//appointment management//EN\r\n";
					$this->output_ics_event($appointment);
					echo "END:VCALENDAR\r\n";
					die();
				}

				wp_enqueue_style('aam-appointment-css', plugins_url(ATOM_AAM_PLUGIN_SLUG) . '/css/styles-quickreply.min.css', array(), ATOM_AAM_PLUGIN_VERSION);
				$template = $this->load_view('quick-reply-admin', true);
				return $template;

			}

			header('HTTP/1.0 403 Forbidden');
			wp_redirect(get_site_url());
			exit();

		}

		return $template;
	}

	public function cronjob_activation() {
		$basetime = new DateTime('2000-01-01 03:00', new DateTimeZone('Europe/Vienna'));

		if (!wp_next_scheduled('aam_daily_cronjob')) {
			wp_schedule_event( $basetime->getTimestamp(), 'daily', 'aam_daily_cronjob');
		}
	}

	public function do_daily_cronjob() {
		global $wpdb;

		$autodelete_option = intval($this->get_option('privacy_autodelete'));
		if ($autodelete_option > 0) {
			$wpdb->query(
				'DELETE FROM ' . ATOM_AAM_TABLE_ENTRIES . '
				 WHERE start < DATE_SUB(CURDATE(),INTERVAL ' . $autodelete_option . ' MONTH)'
			);
		}

		$scheduled_reminder_1 = intval($this->get_option('reminder_schedule_1'));
		$scheduled_reminder_2 = intval($this->get_option('reminder_schedule_2'));
		if ($scheduled_reminder_1 > 0) {

			$schedule_time = new Carbon\Carbon();
			$schedule_time->addDays($scheduled_reminder_1);
			$events_for_schedule = $wpdb->get_results("SELECT id FROM " . ATOM_AAM_TABLE_ENTRIES . " WHERE DATE(start) = '" . $schedule_time->format('Y-m-d') . "' AND confirmed = 1", "ARRAY_A");

			foreach ($events_for_schedule as $event) {
				$this->email->scheduled_reminder($event['id']);
			}

		}
		if ($scheduled_reminder_2 > 0) {

			$schedule_time = new Carbon\Carbon();
			$schedule_time->addDays($scheduled_reminder_2);
			$events_for_schedule = $wpdb->get_results("SELECT id FROM " . ATOM_AAM_TABLE_ENTRIES . " WHERE DATE(start) = '" . $schedule_time->format('Y-m-d') . "' AND confirmed = 1", "ARRAY_A");

			foreach ($events_for_schedule as $event) {
				$this->email->scheduled_reminder($event['id']);
			}

		}

	}

	private function return_option_or_empty_array($option) {
		$option = get_option($option);
		return (is_array($option)) ? $option : array();
	}

	private function get_min_max_time() {

		if (!ATOM_AAM_DEBUG && ($minmaxtime = get_transient('atom_appointment_transient_minmaxtime'))) {
			return $minmaxtime;
		}

		global $wpdb;

		$min = '08:00';
		$max = '19:00';

		foreach ($this->get_option('workdays') as $entry) {
			if ( !($entry['start'] == '00:00' && $entry['end'] == '00:00') ) {
				if ($entry['start'] < $min) $min = $entry['start'];
				if ($entry['end'] > $max) $max = $entry['end'];
			}
		}

		$categories = $this->get_option('categories');
		foreach ($categories as $category_id => $category) {
			if (isset($category['override_settings']) && $category['override_settings']['override_rulebased']) {

				foreach ($this->get_category_override_setting($category_id, 'workdays') as $entry) {
					if ( !($entry['start'] == '00:00' && $entry['end'] == '00:00') ) {
						if ($entry['start'] < $min) $min = $entry['start'];
						if ($entry['end'] > $max) $max = $entry['end'];
					}
				}

			}
		}

		if (defined('ATOM_AAM_TABLE_SLOTS')) {
			$from = new Carbon\Carbon();
			$to = $from->copy()->addWeeks($this->get_option('weeks_in_advance'))->endOfWeek();
			$individual_slots = $wpdb->get_results("SELECT slot_start, slot_end FROM " . ATOM_AAM_TABLE_SLOTS . " WHERE (slot_start >= '" . $from . "' AND slot_end <= '" . $to . "') OR (slot_repeat IS NOT NULL AND slot_repeat_until IS NULL OR slot_repeat_until >= '" . $from . "') ORDER BY slot_start ASC;");
			foreach ($individual_slots as $entry) {
				$slot_start = (new Carbon\Carbon($entry->slot_start))->format('H:i');
				$slot_end = (new Carbon\Carbon($entry->slot_end))->format('H:i');
				if ($slot_start < $min) $min = $slot_start;
				if ($slot_end > $max) $max = $slot_end;
			}
		}

		$minmaxtime = array($min, $max);
		set_transient('atom_appointment_transient_minmaxtime', $minmaxtime, 24*HOUR_IN_SECONDS);
		return $minmaxtime;

	}

	public function hexToRgb($hex, $alpha = 1) {
		$hex      = str_replace('#', '', $hex);
		$length   = strlen($hex);
		$rgb['r'] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
		$rgb['g'] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
		$rgb['b'] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));

		$rgb['a'] = $alpha;

		return 'rgba('.$rgb['r'].','.$rgb['g'].','.$rgb['b'].','.$rgb['a'].')';
	}

	public function date_format($date) {
		return date_i18n( get_option('date_format'), strtotime($date) );
	}

	public function time_format($time) {
		return date_i18n( get_option('time_format'), strtotime($time) );
	}

	function load_textdomain() {
		load_plugin_textdomain('atom-appointment-management', false, ATOM_AAM_PLUGIN_SLUG . '/lang/');
	}

	public function filter_formal_textdomain($mofile, $domain) {
	    if ($domain == 'atom-appointment-management' && get_option('atom_aam_settings')['formal_language'] && $this->is_available_formal_language((get_locale()))) {
	        extract(pathinfo($mofile));
            $mofile = $dirname . '/' . $filename . '_formal.' . $extension;
	    }

	    return $mofile;
	}

	public function is_available_formal_language($domain) {
		return in_array($domain, array(
			'de_DE'
		));
	}

	public function setup_plugin() {
		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$charset_collate = $wpdb->get_charset_collate();

		$table_name = ATOM_AAM_TABLE_ENTRIES;
		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			start datetime NOT NULL,
			end datetime NOT NULL,
			fields text,
			slot_id int(11) DEFAULT -1 NOT NULL,
			category int(11) DEFAULT -1 NOT NULL,
			title varchar(100),
			confirmed tinyint(1) DEFAULT 0 NOT NULL,
			booking_date timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
			external_id text,
			UNIQUE KEY id (id),
			INDEX idx_start_end (start, end)
		) $charset_collate;";
		dbDelta($sql);

		$table_name = ATOM_AAM_TABLE_EXCEPTIONS;
		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			excpt_begin datetime NOT NULL,
			excpt_end datetime NOT NULL,
			excpt_description varchar(100),
			excpt_category int(11) DEFAULT -1 NOT NULL,
			excpt_type varchar(4) DEFAULT 'del' NOT NULL,
			UNIQUE KEY id (id),
			INDEX idx_begin_end (excpt_begin, excpt_end)
		) $charset_collate;";
		dbDelta($sql);

		require_once(ATOM_AAM_PLUGIN_PATH . 'includes/compatibility.php');

		update_option('atom_aam_version', ATOM_AAM_PLUGIN_VERSION);
	}

	public function category_id_by_name($category_name) {
		if (isset($category_name)) {
			$category_id = $category_name;
			if (!is_numeric($category_id)) {
				$category_id = 'all';
				foreach ($this->get_option('categories') as $k => $v) {
					if ($v['name'] == $category_name) {
						$category_id = $k;
						break;
					}
				}
			}
			return $category_id;
		} else {
			return false;
		}
	}

	public function validate_date($date, $format = 'Y-m-d H:i') {
		$d = DateTime::createFromFormat($format, $date);
		if ($d && $d->format($format) === $date) {
			return $date;
		} else {
			return false;
		}
	}

	public function get_category_override_setting($category, $setting) {
		$categories = $this->get_option('categories');
		if (isset($categories[$category]) && isset($categories[$category]['override_settings']) && isset($categories[$category]['override_settings'][$setting])) {
			return $categories[$category]['override_settings'][$setting];
		} else {
			return $this->get_option($setting);
		}
	}

	public function category_name_by_id($id) {
		$categories = $this->get_option('categories');
		return (isset($categories[$id])) ? $categories[$id]['name'] : __('None', 'atom-appointment-management');
	}

	public function generate_fields_string($fields, $delimiter = '<br />') {
		$string = "";

		foreach ($this->get_option('formfields') as $field) {
			$field_id = $field['id'];
			$value = (sanitize_text_field($fields[$field_id])) ? sanitize_text_field($fields[$field_id]) : '-';
			$string .= $field['label'] . ': ' . $value . $delimiter;
		}

		return $string;
	}

	public function get_appointment_array($appointment_id) {
		global $wpdb;

		$appointment = $wpdb->get_results("SELECT * FROM " . ATOM_AAM_TABLE_ENTRIES . " WHERE id = " . $appointment_id, "ARRAY_A");

		if (!isset($appointment[0])) return false;

		$appointment = $appointment[0];

		$fields = unserialize($appointment['fields']);

		$appointment['fields'] = (is_array($fields)) ? $fields : array();
		$appointment['date'] = $this->date_format($appointment['start']);
		$appointment['time'] = $this->time_format($appointment['start']) . " - " . $this->time_format($appointment['end']);
		$appointment['title'] = $appointment['title'];

		return $appointment;
	}

	public function filter_fields_placeholders($text, $appointment) {
		if (!is_array($appointment)) {
			$appointment = $this->get_appointment_array($appointment);
		}

		$appointment['fields']['date'] = $appointment['date'];
		$appointment['fields']['time'] = $appointment['time'];
		$appointment['fields']['title'] = $appointment['title'];

		foreach ($appointment['fields'] as $key => $field) {
			$filter = '((' . $key . '))';
			$replacement = sanitize_text_field($field);
			$text = str_replace($filter, $replacement, $text);
		}
		$text = preg_replace('%\(\(.+\)\)%', '', $text);

		return $text;
	}

	public function get_timezone() {
		$tz = get_option('timezone_string');
		return ($tz == '') ? 'UTC' : $tz;
	}

	public function generate_quickreply_url($action, $booking_date, $id) {
		$timehash = password_hash($booking_date, PASSWORD_DEFAULT);
		return get_site_url() . "/?atom-appointment-quickreply=" . $timehash . "&i=" . $id . "&a=" . $action;
	}

	public function ajax_send_user_mail() {
		$id = intval($_POST['id']);
		$type = sanitize_text_field($_POST['type']);

		if ($this->get_option('automated_emails_enabled')) {

			if ($type == 'confirm') {
				$this->email->user_notification_appointment_confirmed($id);
			} else if ($type == 'cancel') {
				$this->email->user_notification_appointment_canceled($id);
			}

			echo json_encode(array(
				'automated_emails_enabled' => true
			));
			die();

		} else {

			$subject = $this->get_option($type . '_subject');
			$subject = $this->filter_fields_placeholders($subject, $id);

			$text = $this->get_option($type . '_text');
			$text = $this->filter_fields_placeholders($text, $id);

			echo json_encode(array(
				'automated_emails_enabled' => false,
				'subject' => $subject,
				'text' => $text
			));
			die();

		}
	}

	public function output_ics_event($appointment) {
		if (!is_array($appointment)) {
			$appointment = $this->get_appointment_array($appointment);
		}

		$timezone_string = get_option('timezone_string');
		$dtstamp = date("Ymd\THis");
		$dtstamp_readable = $this->date_format('now') . ' ' . $this->time_format('now');

		$timestamp = date('ymdHi', strtotime($appointment['start']));
		$uid = md5($timestamp . date('ymdHi')) . "@aam";
		$dtstamp = date("Ymd\THis");
		$dtstart = date("Ymd\THis", strtotime($appointment['start']));
		$dtend = date("Ymd\THis", strtotime($appointment['end']));

		$summary = (!empty($appointment['title'])) ? $appointment['title'] : __('Appointment', 'atom-appointment-management');
		if ($appointment['confirmed'] == 1) $summary .= ' [' . __('Confirmed', 'atom-appointment-management') . ']';

		$accept_url = $this->generate_quickreply_url('acc', $appointment['booking_date'], $appointment['id']);
		$delete_url = $this->generate_quickreply_url('del', $appointment['booking_date'], $appointment['id']);

		$description = $this->generate_fields_string($appointment['fields'], '\\n');
		if ($appointment['confirmed'] != 1) $description .= "\\n\\n" . __('Confirm Appointment', 'atom-appointment-management') . ':\\n' . $accept_url;
		$description .= "\\n\\n" . __('Cancel Appointment', 'atom-appointment-management') . ':\\n' . $delete_url;
		$description .= "\\n\\n" . __('Last Update', 'atom-appointment-management') . ': ' . $dtstamp_readable;


		echo "BEGIN:VEVENT\r\n";
		echo "UID:" . $uid . "\r\n";
		echo "DTSTAMP:" . $dtstamp. "\r\n";
		echo "DTSTART;TZID=" . $timezone_string . ":" . $dtstart. "\r\n";
		echo "DTEND;TZID=" . $timezone_string . ":" . $dtend. "\r\n";
		echo "SUMMARY:" . $summary. "\r\n";
		echo "DESCRIPTION:" . $description. "\r\n";
		echo "END:VEVENT\r\n";
	}

	function ajax_submit_form() {
		global $wpdb;
		$status = array();
		$fields = array();

		$post_category = (isset($_POST['category'])) ? intval($_POST['category']) : -1;
		$post_startdate = (isset($_POST['startdate'])) ? $this->validate_date($_POST['startdate']) : false;
		$post_enddate = (isset($_POST['enddate'])) ? $this->validate_date($_POST['enddate']) : false;
		$slot_id = (isset($_POST['slot_id'])) ? intval($_POST['slot_id']) : -1;
		$appointment_title = (isset($_POST['title'])) ? sanitize_text_field($_POST['title']) : '';

		if (!$post_startdate || !$post_enddate) {
			$status['success'] = false;
			$status['error'] = 'invalid_arguments';
			echo json_encode($status);
			die();
		}

		// validate slot still available
		$available_events = $this->ajax_fetch_events($post_startdate, $post_enddate, 'array', $post_category, false, true, true)['events'];

		if (empty($available_events)) {
			$status['success'] = false;
			$status['error'] = 'slot_unavailable';
			echo json_encode($status);
			die();
		}

		foreach ($_POST['values'] as $field) {
			$fields[sanitize_text_field($field['key'])] = sanitize_text_field($field['value']);
		}

		$result = $wpdb->insert(
			ATOM_AAM_TABLE_ENTRIES, 		// table name
			array( 							// data
				'start'		=> $post_startdate,
				'end'		=> $post_enddate,
				'category'	=> $post_category,
				'fields' 	=> serialize($fields),
				'slot_id' 	=> $slot_id,
				'title' 	=> $appointment_title
			)
		);

		if ($result > 0) {

			$status["success"] = true;
			wp_schedule_single_event(time(), 'aam_async_after_booking_tasks', array($wpdb->insert_id));

		} else {

			$status['success'] = false;
			$status['error'] = 'db_query_failed';

		}

		$redirect_link = $this->parse_link_option($this->get_option('cta_redirect_url'));
		if (!empty($redirect_link) && $redirect_link != '#') {
			$status['redirect'] = $redirect_link;
		} else {
			$status['redirect'] = false;
		}

		$this->clear_transients();
		echo json_encode($status);
		die();
	}

	public function async_after_booking_tasks($appointment_id) {

		$appointment = $this->get_appointment_array($appointment_id);
		$appointment_category = $appointment['category'];

		// send mail to admin
		$categories = $this->get_option('categories');
		if (isset($categories[$appointment_category]['override_settings']['notif_mail']) && $categories[$appointment_category]['override_settings']['notif_mail'] != '') {
			$to = $categories[$appointment_category]['override_settings']['notif_mail'];
		} else {
			$to = $this->get_option('notif_mail');
		}

		$category_name = $this->get_category_name($appointment_category);

		if ($to) {
			$subject = __('New Appointment Request', 'atom-appointment-management');
			$readabledate = new Carbon\Carbon($appointment['start']);

			$accept_url = $this->generate_quickreply_url('acc', $appointment['booking_date'], $appointment_id);
			$delete_url = $this->generate_quickreply_url('del', $appointment['booking_date'], $appointment_id);

			$user_info = $this->generate_fields_string($appointment['fields']);

			$email_args = array(
				'user_info'		=> $user_info,
				'title'			=> $appointment['title'],
				'date'			=> $appointment['date'],
				'time'			=> $appointment['time'],
				'category'		=> $category_name,
				'accept_url'	=> $accept_url,
				'delete_url'	=> $delete_url,
			);

			$this->email->admin_notification_new_appointment($to, $subject, $email_args);
		}

		// send mail to user
		$this->email->user_notification_appointment_received($appointment_id);

		do_action('aam_after_appointment_booked', $appointment_id);

	}

	public function ajax_fetch_events($from_param = false, $to_param = false, $output_format = 'json', $category_param = false, $type_param = false, $no_cache = false, $show_full_events = true) {
		global $wpdb;

		$debug = array();
		$events = array();

		$from_param = ($from_param) ? $from_param : sanitize_text_field($_POST['from']);
		$to_param = ($to_param) ? $to_param : sanitize_text_field($_POST['to']);
		$type_param = ($type_param) ? $type_param : sanitize_text_field($_POST['type']);
		$category_param = ($category_param) ? $category_param : sanitize_text_field($_POST['category']);

		if ($this->get_option('show_full_events')) {
			$show_full_events = (isset($_POST['show_full_events'])) ? (boolean)$_POST['show_full_events'] : $show_full_events;
		} else {
			$show_full_events = false;
		}

		$from = new Carbon\Carbon($from_param, 'UTC');
		$to = new Carbon\Carbon($to_param, 'UTC');

		// type sanitation
		$type = (in_array($type_param, array(
			'individual',
			'rule-based'
		))) ? $type_param : 'all';

		// category sanitation
		if (!$category_param || $category_param == 'none') {
			$categories = array();
		} else {
			$categories = $this->get_option('categories');

			if ($category_param != 'all') {
				$selected_categories = explode(',', $category_param);
				if (is_array($selected_categories)) {
					foreach ($categories as $key => $value) {
						if (!in_array($key, $selected_categories)) {
							unset($categories[$key]);
						}
					}
				}
			}
		}

		$transient_key = 'aam_events_' . md5($from_param . '|' . $to_param . '|' . $type . '|' . implode('-', array_keys($categories)));

		if (!ATOM_AAM_DEBUG && !$no_cache && ($data = get_transient($transient_key))) {
			if ($output_format == 'json') {
				$data = json_encode($data);
				echo $data;
				die();
			} else {
				return $data;
			}
		}

		$default_color = $this->get_option('maincolor');

		$default_rules = array(
			'event_duration'		=> new Carbon\Carbon($this->get_option('event_duration')),
			'event_gap'				=> new Carbon\Carbon($this->get_option('event_gap')),
			'workdays'				=> $this->get_option('workdays'),
			'color'					=> $default_color,
			'category'				=> -1,
			'bookings_per_slot'		=> $this->get_option('bookings_per_slot_rulebased'),
			'rulebased_title'		=> $this->get_option('rulebased_title'),
			'rulebased_moreinfo' 	=> $this->parse_link_option($this->get_option('rulebased_moreinfo'))
		);

		$now = Carbon\Carbon::now('UTC');
		$now->second(0);
		// $offset = timezone_offset_get(timezone_open($this->get_timezone()), new DateTime("now", new DateTimeZone("UTC")) );
		// $now->addSeconds($offset);

		$firstbooking = $this->get_option('first_possible_booking');
		$firstbooking_only_count_open = $this->get_option('first_possible_booking_count_open_hours');

		if ($firstbooking > 0) {
			if ($firstbooking_only_count_open) {
				$now = $this->calculate_firstbooking_only_count_open($now, $from, $to);
			} else {
				$now->addHours($firstbooking);
			}
		}

		$debug['now'] = $now;
		$debug['categories'] = $categories;
		$debug['type'] = $type;
		$debug['transient_key'] = $transient_key;
		$debug['show_full_events'] = $show_full_events;
		$debug['post'] = $_POST;

		if (($type == 'all' || $type == 'rule-based')) {

			$rules_array = array();

			if (($type) == 'all') {
				$rules_array[] = $default_rules;
			}

			foreach ($categories as $category_id => $category) {
				if (isset($category['override_settings']) && $category['override_settings']['override_rulebased']) {
					$rules_array[] = array(
						'event_duration'		=> new Carbon\Carbon($this->get_category_override_setting($category_id, 'event_duration')),
						'event_gap'				=> new Carbon\Carbon($this->get_category_override_setting($category_id, 'event_gap')),
						'workdays'				=> $this->get_category_override_setting($category_id, 'workdays'),
						'color'					=> $category['color'],
						'category'				=> $category_id,
						'bookings_per_slot'		=> $this->get_category_override_setting($category_id, 'bookings_per_slot_rulebased'),
						'rulebased_title'		=> $this->get_category_override_setting($category_id, 'rulebased_title'),
						'rulebased_moreinfo' 	=> $this->parse_link_option($this->get_category_override_setting($category_id, 'rulebased_moreinfo'))
					);
				}
			}

			$debug['rules_array'] = $rules_array;

			foreach ($rules_array as $rules) {
				$this->generate_rulebased_events($rules, $events, $now, $from, $to, $show_full_events, $debug);
			}

			$events = apply_filters('aam_add_rule-based_events', $events, $now, $from, $to, $show_full_events);

		}

		if ($type == 'all' || $type == 'individual') {

			$events = apply_filters('aam_add_individual_events', $events, $categories, $now, $from, $to, $show_full_events);

		}

		$events = apply_filters('aam_filter_available_events', $events, $categories, $now, $from, $to, $show_full_events);

		usort($events, function($a, $b) {
			return $b['start'] < $a['start'];
		});

		$output = array("events" => $events, "debug" => $debug);
		set_transient($transient_key, $output, 60 * 2);

		if ($output_format == 'json') {
			$output = json_encode($output);
			echo $output;
			die();
		} else {
			return $output;
		}
	}

	private function generate_rulebased_events($rules, &$events, $now, $from, $to, $show_full_events, &$debug) {
		global $wpdb;

		for ($i = 0; $i < sizeof($rules['workdays']); $i++) {
			$rules['workdays'][$i]['start'] = new Carbon\Carbon($rules['workdays'][$i]['start']);
			$rules['workdays'][$i]['end'] = new Carbon\Carbon($rules['workdays'][$i]['end']);
		}

		$weeklimit = Carbon\Carbon::now($this->get_timezone())->addWeeks($this->get_option('weeks_in_advance'))->endOfWeek();

		// GET BOOKED EVENTS
		$booked_events = $wpdb->get_results("SELECT start, end FROM " . ATOM_AAM_TABLE_ENTRIES . " WHERE (DATE(start) BETWEEN '" . $from . "' AND '" . $to . "') AND confirmed != -1 AND slot_id = -1 AND category = '" . $rules['category'] . "' ORDER BY start ASC", "ARRAY_A");

		// GET EXCEPTIONS
		$exceptions = $wpdb->get_results(
			"SELECT excpt_begin, excpt_end FROM " . ATOM_AAM_TABLE_EXCEPTIONS . " WHERE
			((excpt_begin BETWEEN '" . $from . "' AND '" . $to . "') OR (excpt_end BETWEEN '" . $from . "' AND '" . $to . "') OR (excpt_end >= '" . $to . "' AND excpt_begin <'" . $from . "'))
			AND (excpt_category = '" . $rules['category'] . "' OR excpt_category = '-1') AND excpt_type = 'del'
			ORDER BY excpt_begin ASC", "ARRAY_A"
		);

		$full_exceptions = $wpdb->get_results(
			"SELECT excpt_begin, excpt_end FROM " . ATOM_AAM_TABLE_EXCEPTIONS . " WHERE
			((excpt_begin BETWEEN '" . $from . "' AND '" . $to . "') OR (excpt_end BETWEEN '" . $from . "' AND '" . $to . "') OR (excpt_end >= '" . $to . "' AND excpt_begin <'" . $from . "'))
			AND (excpt_category = '" . $rules['category'] . "' OR excpt_category = '-1') AND excpt_type = 'full'
			ORDER BY excpt_begin ASC", "ARRAY_A"
		);

		// SETUP FOR LOOP
		if ($now->lt($from)) { // not current week
			$i = $from->copy();
			$dow = ($i->dayOfWeek + 6) % 7;
			$i->hour($rules['workdays'][$dow]['start']->hour)->minute($rules['workdays'][$dow]['start']->minute);
		} else { // current week
			$i = $now->copy();
			$dow = ($i->dayOfWeek + 6) % 7;
			$i->hour($rules['workdays'][$dow]['start']->hour)->minute($rules['workdays'][$dow]['start']->minute);

			while ($i->lt($now)) {
				$i->addHours($rules['event_duration']->hour)->addMinutes($rules['event_duration']->minute);
				$i->addHours($rules['event_gap']->hour)->addMinutes($rules['event_gap']->minute);
			}
		}

		// RULE-BASED APPOINTMENT SLOTS
		while ($i->lte($to) && $i->lt($weeklimit)) {
			$dow = ($i->dayOfWeek + 6) % 7; // day of week
			$day_start = $rules['workdays'][$dow]['start'];
			$day_end = $rules['workdays'][$dow]['end'];
			$next_day_start = $rules['workdays'][($i->dayOfWeek + 7) % 7]['start'];

			if ($i->format('H:i') < $day_start->format('H:i')) $i->hour($day_start->hour)->minute($day_start->minute);

			if ($day_start->format('H:i') != "00:00" || $day_end->format('H:i') != "00:00") { // check if dayofweek is a work day

				$relevant_exceptions = array();
				foreach ($exceptions as $e) {
					$e_begin = new Carbon\Carbon($e['excpt_begin'], 'UTC');
					$e_end = new Carbon\Carbon($e['excpt_end'], 'UTC');
					if ($i->isSameDay($e_begin) || $i->between($e_begin, $e_end) || $i->isSameDay($e_end)) {
						if ($e_begin->format('H:i') == '00:00' && $e_end->format('H:i') == '00:00') {
							$relevant_exceptions = "fullday";
							break;
						} else {
							array_push($relevant_exceptions, array(
								'excpt_begin' => $e_begin,
								'excpt_end' => $e_end
							));
						}
					}
				}

				$relevant_full_exceptions = array();
				if ($show_full_events) {
					foreach ($full_exceptions as $e) {
						$e_begin = new Carbon\Carbon($e['excpt_begin'], 'UTC');
						$e_end = new Carbon\Carbon($e['excpt_end'], 'UTC');
						if ($i->isSameDay($e_begin) || $i->between($e_begin, $e_end) || $i->isSameDay($e_end)) {
							if ($e_begin->format('H:i') == '00:00' && $e_end->format('H:i') == '00:00') {
								$relevant_full_exceptions = "fullday";
								break;
							} else {
								array_push($relevant_full_exceptions, array(
									'excpt_begin' => $e_begin,
									'excpt_end' => $e_end
								));
							}
						}
					}
				}

				if ($relevant_exceptions != 'fullday') {
					$current_day = $i->copy();

					while (true) {
						foreach ($relevant_exceptions as $relevant_exception) {
							while ($i->between($relevant_exception['excpt_begin'], $relevant_exception['excpt_end']) && $i != $relevant_exception['excpt_end']) {
								if ($i->format('H:i') >= $day_end->format('H:i')) break;

								$i->addHours($rules['event_duration']->hour)->addMinutes($rules['event_duration']->minute);
								$i->addHours($rules['event_gap']->hour)->addMinutes($rules['event_gap']->minute);
							}
						}

						$end_date = $i->copy()->addHours($rules['event_duration']->hour)->addMinutes($rules['event_duration']->minute);
						if ( $end_date->format('H:i') <= $day_end->format('H:i') && $i->isSameDay($current_day) ) {

							if ($i->gte($from) && $end_date->lte($to)) {

								if ($relevant_full_exceptions == 'fullday') {
									$full = true;
								} else {

									$full = false;
									$be = 0;
									foreach ($booked_events as $b) {
										if ($i->between(new Carbon\Carbon($b['start'], 'UTC'), (new Carbon\Carbon($b['end'], 'UTC'))->subMinute())) {
											$be++;
											if ($be >= $rules['bookings_per_slot']) {
												$full = true;
												break;
											}
										}
									}

									if (!$full && is_array($relevant_full_exceptions)) {
										foreach ($relevant_full_exceptions as $b) {
											if ($i->between(new Carbon\Carbon($b['excpt_begin'], 'UTC'), (new Carbon\Carbon($b['excpt_end'], 'UTC'))->subMinute())) {
												$full = true;
												break;
											}
										}
									}

								}

								$event = array(
									'start' 		=> $i->toW3cString(),
									'end' 			=> $end_date->toW3cString(),
									'title' 		=> $rules['rulebased_title'],
									'moreinfo' 		=> $rules['rulebased_moreinfo'],
									'type' 			=> 'rule',
									'category' 		=> $rules['category'],
									'db_id' 		=> -1,
									'color' 		=> $rules['color']
								);

								if (!$full) {
									array_push($events, $event);
								} else if ($show_full_events) {
									$event['className'] = 'aam-full';
									$event['title'] = __('Booked', 'atom-appointment-management');
									array_push($events, $event);
								}

							}

							$i = $end_date;
							$i->addHours($rules['event_gap']->hour)->addMinutes($rules['event_gap']->minute);

						} else {
							break;
						}
					}

					// to not skip a day if an exception lasted longer than the day
					if (!$i->isSameDay($current_day)) {
						$i->subDay();
					}

				}

			}

			$i->addDay()->hour($next_day_start->hour)->minute($next_day_start->minute);

		}
	}

	private function calculate_firstbooking_only_count_open($now, $from, $to) {
		global $wpdb;

		$workdays = $this->get_option('workdays');
		for ($i = 0; $i < sizeof($workdays); $i++) {
			$workdays[$i]['start'] = new Carbon\Carbon($workdays[$i]['start']);
			$workdays[$i]['end'] = new Carbon\Carbon($workdays[$i]['end']);
		}

		$firstbooking_count_left_minutes = $this->get_option('first_possible_booking') * 60;

		// GET EXCEPTIONS
		$exceptions = $wpdb->get_results(
			"SELECT excpt_begin, excpt_end FROM " . ATOM_AAM_TABLE_EXCEPTIONS . " WHERE
			((excpt_begin BETWEEN '" . $from . "' AND '" . $to . "') OR (excpt_end BETWEEN '" . $from . "' AND '" . $to . "'))
			AND excpt_category = '-1' AND excpt_type = 'del'
			ORDER BY excpt_begin ASC", "ARRAY_A"
		);

		while ($now->lt($to) && $firstbooking_count_left_minutes > 0) {
			$dow = ($now->dayOfWeek + 6) % 7; // day of week
			$day_start = $workdays[$dow]['start'];
			$day_end = $workdays[$dow]['end'];
			$next_day_start = $workdays[($now->dayOfWeek + 7) % 7]['start'];

			if ($now->format('H:i') < $day_start->format('H:i')) $now->hour($day_start->hour)->minute($day_start->minute);

			// check if day is full day exception
			$excpt = false;
			foreach ($exceptions as $e) {
				$e_begin = new Carbon\Carbon($e['excpt_begin'], 'UTC');
				$e_end = new Carbon\Carbon($e['excpt_end'], 'UTC');
				if ($now->isSameDay($e_begin) || $now->between($e_begin, $e_end) || $now->isSameDay($e_end)) {
					if ($e_begin->format('H:i') == '00:00' && $e_end->format('H:i') == '00:00') {
						$excpt = true;
						break;
					}
				}
			}

			// if is work day
			if (!$excpt && ($day_start->format('H:i') != "00:00" || $day_end->format('H:i') != "00:00")) {

				if ($now->copy()->hour($day_end->hour)->minute($day_end->minute) < $now->copy()->addMinutes($firstbooking_count_left_minutes)) {
					$firstbooking_count_left_minutes -= $day_end->hour*60 - $now->hour*60 + $day_end->minute - $now->minute;
					$now->addDay()->hour($next_day_start->hour)->minute($next_day_start->minute);
				} else {
					$now->addMinutes($firstbooking_count_left_minutes);
					$firstbooking_count_left_minutes = 0;
				}

			} else {
				$now->addDay()->hour($next_day_start->hour)->minute($next_day_start->minute);
			}

		}

		return $now;

	}

	function ajax_add_exception() {
		if (current_user_can('administrator')) {
			global $wpdb;

			$begin = new Carbon\Carbon($_POST['begin']);
			$end = new Carbon\Carbon($_POST['end']);
			$category = (isset($_POST['category'])) ? intval($_POST['category']) : -1;
			$type = (isset($_POST['type']) && $_POST['type'] == 'full') ? 'full' : 'del';

			$wpdb->insert(
				ATOM_AAM_TABLE_EXCEPTIONS, 	// table name
				array( 				// data
					'excpt_begin'		=> $begin->format('Y-m-d H:i:s'),
					'excpt_end'			=> $end->format('Y-m-d H:i:s'),
					'excpt_category'	=> $category,
					'excpt_description' => 'aam_frontend_exception',
					'excpt_type'		=> $type
				)
			);

			$this->clear_transients();
		}
		die();
	}

	function ajax_remove_slot() {
		if (current_user_can('administrator')) {
			global $wpdb;

			$id = intval($_POST['id']);

			$wpdb->delete(
				ATOM_AAM_TABLE_SLOTS, 	// table name
				array( 				// where clause
					'id'	=> $id
				)
			);

			$this->clear_transients();
		}
		die();
	}

	function get_moreinfo_url($id) {
		if ($id == "") {
			return "-1";
		} else {
			return get_permalink($id);
		}
	}

	public function parse_link_option($option) {
		if (is_array($option)) {
			if ($option['select'] == 'external') {
				return $option['input'];
			} else {
				$option = $option['select'];
			}
		}

		if (!$option || !is_numeric($option)) {
			return '#';
		} else {
			return get_permalink($option);
		}
	}

	public function get_category_name($category_id) {
		$categories = $this->get_option('categories');
		if (isset($categories[$category_id]) && $categories[$category_id]['name'] != '') {
			return $categories[$category_id]['name'];
		} else {
			return false;
		}
	}

	public function load_view($path, $return = false) {
		$template_path = ATOM_AAM_PLUGIN_PATH . 'views/' . $path . '.php';

		if (!file_exists($template_path)) {
			$template_path = false;
		}

		if ($return || !$template_path) {
			return $template_path;
		} else {
			require_once($template_path);
		}
	}

	public function clear_transients() {
		global $wpdb;
	    $sql = "SELECT `option_name` AS `name`, `option_value` AS `value`
	            FROM  $wpdb->options
	            WHERE `option_name` LIKE '%transient_aam_events_%'
	            ORDER BY `option_name`";

	    $results = $wpdb->get_results( $sql );

		foreach ($results as $t) {
			delete_transient(substr($t->name, 11));
		}
		delete_transient('aam_admin_notification_badge');
		delete_transient('atom_appointment_transient_minmaxtime');
	}

}
endif;
