<?php

$default_options = array(

	'weeks_in_advance' 				=> 6,
	'notif_mail' 					=> false,
	'event_duration' 				=> '01:00',
	'event_gap' 					=> '01:00',
	'first_possible_booking'		=> 6,
	'first_possible_booking_count_open_hours' => false,
	'bookings_per_slot_rulebased'	=> 1,
	'workdays'						=> array(
		array(
			'start' => '09:00',
			'end' 	=> '18:00'
		),
		array(
			'start' => '09:00',
			'end' 	=> '18:00'
		),
		array(
			'start' => '09:00',
			'end' 	=> '18:00'
		),
		array(
			'start' => '09:00',
			'end' 	=> '18:00'
		),
		array(
			'start' => '09:00',
			'end' 	=> '18:00'
		),
		array(
			'start' => '00:00',
			'end' 	=> '00:00'
		),
		array(
			'start' => '00:00',
			'end' 	=> '00:00'
		)
	),
	'rulebased_title'				=> '',
	'maincolor'						=> '#007acc',
	'color_bg'						=> '#ffffff',
	'color_border'					=> '#dddddd',
	'color_text'					=> '#000000',
	'color_entrylabel_text'			=> '#ffffff',
	'privacy_mode'					=> false,
	'privacy_link'					=> '',
	'formal_language'				=> false,
	'categories'					=> array(),
	'privacy_text'					=> sprintf(__('Yes, I agree that the entered information will be processed and stored by %s to manage this appointment.', 'atom-appointment-management'), get_bloginfo('name')),
	'analytics_disable_admins'		=> false,
	'modal_inquiry_thankyou'		=> __("Thank you for contacting us.\nYou will get notified at %s when the appointment has been confirmed.", 'atom-appointment-management'),
	'formfields'					=> array(
		array(
			"label" 	=> __('Name', 'atom-appointment-management'),
			"id" 		=> "field_name",
			"type" 		=> "text"
		),
		array(
			"label" 	=> __('E-Mail', 'atom-appointment-management'),
			"id" 		=> "field_email",
			"type" 		=> "email",
			"required"	=> 'on'
		),
		array(
		   "label" 	=> __('Phone', 'atom-appointment-management'),
		   "id" 		=> "field_tel",
		   "type" 		=> "tel"
		)
  	),
	'send_button_label'				=> __('Make appointment', 'atom-appointment-management'),
	'received_enabled'				=> true,
	'received_subject'				=> __('Thank you for appointment request', 'atom-appointment-management'),
	'received_text'					=> __('We received your appointment request and we will notify you once it has been confirmed.', 'atom-appointment-management'),
	'cancel_info_text'				=> __('Please <a href="%s">cancel your appointment</a> in time if your plans change.', 'atom-appointment-management'),
	'confirm_subject'				=> __('Your appointment has been confirmed', 'atom-appointment-management'),
	'confirm_text'					=> __('We are happy to inform you that your appointment has been confirmed. Thank you for contacting us, we look forward to the appointment.', 'atom-appointment-management'),
	'cancel_subject'				=> __('Your appointment has been cancelled', 'atom-appointment-management'),
	'cancel_text'					=> __('Unfortunately we have to cancel your appointment on ((date)) at ((time)). Please contact us to find a different arrangement.', 'atom-appointment-management'),

);
