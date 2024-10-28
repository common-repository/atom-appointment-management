<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if (!class_exists('Atom_Appointment_Management_Email')):
class Atom_Appointment_Management_Email {

	private $aam;

	public function __construct($aam) {
		$this->aam = $aam;
	}

	////////////////////////////////////////////////////////////////
	//		Functions
	////////////////////////////////////////////////////////////////

	public function send($to, $subject, $message) {

		$body  = <<<EOT

		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
				<title>$subject</title>
				<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

				<style>
					body, table.container {
						text-align: center;
						font-family: Helvetica, Arial, sans-serif;
						font-size: 16px;
						word-wrap: break-word;
					}
					table.inner {
						box-shadow: 0px 0px 4px #bbbbbb;
						margin-top: 30px;
						margin-bottom: 60px;
						max-width: 100%;
					}
					a {
						color: #000000;
					}
					.date {
						font-size: 2em;
						font-weight: 300;
						margin: 1em 0 .5em 0;
						border-top: 1px solid #dddddd;
						padding-top: 1em;
					}
				</style>

			</head>
			<body style="margin: 0; padding: 0;" bgcolor="#f6f6f6">
				<table class="container" border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#f6f6f6">
					<tr><td>
						<table class="inner" align="center" border="0" cellpadding="30" cellspacing="0" width="600" style="border-collapse: collapse;" bgcolor="#ffffff">
							<tr><td>
							<h1>$subject</h1>
							$message
							</td></tr>
						</table>
					</td></tr>
				</table>
			</body>
		</html>

EOT;

		$headers = array(
			'Content-type: text/html; charset=utf-8'
		);

		add_filter('wp_mail_from', array($this, 'filter_email'));
		add_filter('wp_mail_from_name', array($this, 'filter_name'));

		wp_mail($to, $subject, $body, $headers);

		remove_filter('wp_mail_from', array($this, 'filter_name'));
		remove_filter('wp_mail_from_name', array($this, 'filter_email'));

	}

	public function filter_email($from) {
		$new_from = trim($this->aam->get_option('sender_email'));
		return (($new_from != '') ? $new_from : $from);
	}

	public function filter_name($name) {
		$new_name = trim($this->aam->get_option('sender_name'));
		return (($new_name != '') ? $new_name : $name);
	}

	public function admin_notification_new_appointment($to, $subject, $email_args) {

		$message = '<p>' . sprintf(__('You received a new request for an appointment on %s.', 'atom-appointment-management'), get_bloginfo('name')) . '</p>';

		$message .= '<p class="date">
						' . ((isset($email_args['title']) && !empty($email_args['title'])) ? $email_args['title'] . '<br />' : '') . '
						' . $email_args['date'] . '<br />
						' . $email_args['time'] .
					'</p>';

		if (isset($email_args['category']) && !empty($email_args['category'])) {
			$message .= '<p class="category" style="line-height: 24px;">
							' . __('Category', 'atom-appointment-management') . ': ' . $email_args['category'] .
						'</p>';
		}

		$message .= '<p class="user-info" style="line-height: 24px;">
						' . $email_args['user_info'] .
					'</p>';

		$message .= '<p>' . __('Click confirm or cancel to let the user know if the appointment will take place.', 'atom-appointment-management') . '</p>';

		$message .= '<table border="0" cellspacing="20" cellpadding="0" width="100%">
						<tr>
							<td align="center" style="border-radius: 3px;" bgcolor="#009900">
								<a href="' . $email_args['accept_url'] . '" style="color: #ffffff; text-decoration: none; border: 1px solid #009900; padding: 10px 15px; display: inline-block;" bgcolor="#009900">
									' . __('Confirm Appointment', 'atom-appointment-management') . '
								</a>
							</td>
							<td align="center" style="border-radius: 3px;" bgcolor="#aa1818">
								<a href="' . $email_args['delete_url'] . '" style="color: #ffffff; text-decoration: none; border: 1px solid #aa1818; padding: 10px 15px; display: inline-block;" bgcolor="#aa1818">
									' . __('Cancel Appointment', 'atom-appointment-management') . '
								</a>
							</td>
						</tr>
					</table>';

		$this->send($to, $subject, $message);

	}

	public function admin_notification_appointment_cancelled($appointment) {
		if (!is_array($appointment)) {
			$appointment = $this->aam->get_appointment_array($appointment);
		}

		$to =  $this->aam->get_option('notif_mail');

		$user_info = $this->aam->generate_fields_string($appointment['fields']);

		$subject = __('Appointment has been cancelled', 'atom-appointment-management');

		$message = '<p>' . sprintf(__('An appointment on %s has been cancelled by the customer.', 'atom-appointment-management'), get_bloginfo('name')) . '</p>';

		$message .= '<p class="date">
						' . ((isset($appointment['title']) && !empty($appointment['title'])) ? $appointment['title'] . '<br />' : '') . '
						' . $appointment['date'] . '<br />
						' . $appointment['time'] .
					'</p>';

		if ($category = $this->aam->get_category_name($appointment['category'])) {
			$message .= '<p class="category" style="line-height: 24px;">
							' . __('Category', 'atom-appointment-management') . ': ' . $category .
						'</p>';
		}

		$message .= '<p class="user-info" style="line-height: 24px;">
						' . $user_info .
					'</p>';

		$this->send($to, $subject, $message);

	}

	public function user_notification_appointment_received($appointment) {
		if (!$this->aam->get_option('received_enabled')) {
			return;
		}

		if (!is_array($appointment)) {
			$appointment = $this->aam->get_appointment_array($appointment);
		}

		$subject = $this->aam->get_option('received_subject');
		$subject = $this->aam->filter_fields_placeholders($subject, $appointment);

		$text = $this->aam->get_option('received_text');
		$text = $this->aam->filter_fields_placeholders($text, $appointment);

		$message = '<p>' . nl2br($text) . '</p>';

		$message .= '<p class="date">
						' . ((isset($appointment['title']) && !empty($appointment['title'])) ? $appointment['title'] . '<br />' : '') . '
						' . $appointment['date'] . '<br />
						' . $appointment['time'] .
					'</p>';

		if ($category = $this->aam->get_category_name($appointment['category'])) {
			$message .= '<p class="category" style="line-height: 24px;">
							' . __('Category', 'atom-appointment-management') . ': ' . $category .
						'</p>';
		}

		if ($user_cancel_url = $this->aam->generate_quickreply_url('cnc', $appointment['booking_date'] . '_user', $appointment['id'])) {
			$message .= '<p>' . nl2br(sprintf($this->aam->get_option('cancel_info_text'), $user_cancel_url)) . '</p>';
		}

		$message .= '<table border="0" cellspacing="20" cellpadding="0" width="100%">
						<tr>
							<td align="center" style="border-radius: 3px;" bgcolor="' . $this->aam->get_option('maincolor') . '">
								<a href="' . get_bloginfo('url') . '" style="color: #ffffff; text-decoration: none; border: 1px solid ' . $this->aam->get_option('maincolor') . '; padding: 10px 15px; display: inline-block;" bgcolor="' . $this->aam->get_option('maincolor') . '">
									' . __('Visit our website', 'atom-appointment-management') . '
								</a>
							</td>
						</tr>
					</table>';

		$this->send($appointment['fields']['field_email'], $subject, $message);
	}

	public function user_notification_appointment_confirmed($appointment) {
		if (!is_array($appointment)) {
			$appointment = $this->aam->get_appointment_array($appointment);
		}

		$subject = $this->aam->get_option('confirm_subject');
		$subject = $this->aam->filter_fields_placeholders($subject, $appointment);

		$text = $this->aam->get_option('confirm_text');
		$text = $this->aam->filter_fields_placeholders($text, $appointment);

		$message = '<p>' . nl2br($text) . '</p>';

		$message .= '<p class="date">
						' . ((isset($appointment['title']) && !empty($appointment['title'])) ? $appointment['title'] . '<br />' : '') . '
						' . $appointment['date'] . '<br />
						' . $appointment['time'] .
					'</p>';

		if ($category = $this->aam->get_category_name($appointment['category'])) {
			$message .= '<p class="category" style="line-height: 24px;">
							' . __('Category', 'atom-appointment-management') . ': ' . $category .
						'</p>';
		}

		if ($user_cancel_url = $this->aam->generate_quickreply_url('cnc', $appointment['booking_date'] . '_user', $appointment['id'])) {
			$message .= '<p>' . nl2br(sprintf($this->aam->get_option('cancel_info_text'), $user_cancel_url)) . '</p>';
		}

		$message .= '<table border="0" cellspacing="20" cellpadding="0" width="100%">
						<tr>
							<td align="center" style="border-radius: 3px;" bgcolor="' . $this->aam->get_option('maincolor') . '">
								<a href="' . get_bloginfo('url') . '" style="color: #ffffff; text-decoration: none; border: 1px solid ' . $this->aam->get_option('maincolor') . '; padding: 10px 15px; display: inline-block;" bgcolor="' . $this->aam->get_option('maincolor') . '">
									' . __('Visit our website', 'atom-appointment-management') . '
								</a>
							</td>
						</tr>
					</table>';

		$this->send($appointment['fields']['field_email'], $subject, $message);
	}

	public function user_notification_appointment_canceled($appointment) {
		if (!is_array($appointment)) {
			$appointment = $this->aam->get_appointment_array($appointment);
		}

		$subject = $this->aam->get_option('cancel_subject');
		$subject = $this->aam->filter_fields_placeholders($subject, $appointment);

		$text = $this->aam->get_option('cancel_text');
		$text = $this->aam->filter_fields_placeholders($text, $appointment);

		$message = '<p>' . nl2br($text) . '</p>';

		$message .= '<p class="date">
						' . ((isset($appointment['title']) && !empty($appointment['title'])) ? $appointment['title'] . '<br />' : '') . '
						' . $appointment['date'] . '<br />
						' . $appointment['time'] .
					'</p>';

		if ($category = $this->aam->get_category_name($appointment['category'])) {
			$message .= '<p class="category" style="line-height: 24px;">
							' . __('Category', 'atom-appointment-management') . ': ' . $category .
						'</p>';
		}

		$message .= '<table border="0" cellspacing="20" cellpadding="0" width="100%">
						<tr>
							<td align="center" style="border-radius: 3px;" bgcolor="' . $this->aam->get_option('maincolor') . '">
								<a href="' . get_bloginfo('url') . '" style="color: #ffffff; text-decoration: none; border: 1px solid ' . $this->aam->get_option('maincolor') . '; padding: 10px 15px; display: inline-block;" bgcolor="' . $this->aam->get_option('maincolor') . '">
									' . __('Visit our website', 'atom-appointment-management') . '
								</a>
							</td>
						</tr>
					</table>';

		$this->send($appointment['fields']['field_email'], $subject, $message);
	}

	public function scheduled_reminder($appointment) {
		if (!is_array($appointment)) {
			$appointment = $this->aam->get_appointment_array($appointment);
		}

		$subject = $this->aam->get_option('reminder_subject');
		$subject = $this->aam->filter_fields_placeholders($subject, $appointment);

		$text = $this->aam->get_option('reminder_text');
		$text = $this->aam->filter_fields_placeholders($text, $appointment);
		$message = '<p>' . nl2br($text) . '</p>';

		$message .= '<p class="date">
						' . ((isset($appointment['title']) && !empty($appointment['title'])) ? $appointment['title'] . '<br />' : '') . '
						' . $appointment['date'] . '<br />
						' . $appointment['time'] .
					'</p>';

		if ($category = $this->aam->get_category_name($appointment['category'])) {
			$message .= '<p class="category" style="line-height: 24px;">
							' . __('Category', 'atom-appointment-management') . ': ' . $category .
						'</p>';
		}

		if ($user_cancel_url = $this->aam->generate_quickreply_url('cnc', $appointment['booking_date'] . '_user', $appointment['id'])) {
			$message .= '<p>' . nl2br(sprintf($this->aam->get_option('cancel_info_text'), $user_cancel_url)) . '</p>';
		}

		$message .= '<table border="0" cellspacing="20" cellpadding="0" width="100%">
						<tr>
							<td align="center" style="border-radius: 3px;" bgcolor="' . $this->aam->get_option('maincolor') . '">
								<a href="' . get_bloginfo('url') . '" style="color: #ffffff; text-decoration: none; border: 1px solid ' . $this->aam->get_option('maincolor') . '; padding: 10px 15px; display: inline-block;" bgcolor="' . $this->aam->get_option('maincolor') . '">
									' . __('Visit our website', 'atom-appointment-management') . '
								</a>
							</td>
						</tr>
					</table>';

		$this->send($appointment['fields']['field_email'], $subject, $message);

		$admin_email =  $this->aam->get_option('notif_mail');
		if ($admin_email) $this->send($admin_email, $subject, $message);
	}

}
endif;
