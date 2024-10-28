<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if (!class_exists('Atom_Appointment_Management_Admin')):
class Atom_Appointment_Management_Admin {

	public function __construct($atom_appointment_management) {
		$this->aam = $atom_appointment_management;

        add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_enqueue_scripts', array($this, 'register_scripts_and_styles'));
        add_action('admin_init', array($this, 'add_options'));
		add_action('admin_init', array($this, 'handle_adminpage_actions'));

    }


    ////////////////////////////////////////////////////////////////
	//		Functions
	////////////////////////////////////////////////////////////////

	function get_option($option) {
		return $this->aam->get_option($option);
	}

	function get_category_override_setting($category, $setting) {
		return $this->aam->get_category_override_setting($category, $setting);
	}

	public function register_scripts_and_styles($hook) {
		if (strpos($hook, 'atom-appointment-management') === false) {
            return;
        }

		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script('wp-color-picker');
		wp_enqueue_script(
			'aam-admin-js',
			plugins_url(ATOM_AAM_PLUGIN_SLUG) . '/js/atom-appointment-admin.js',
			array(),
			ATOM_AAM_PLUGIN_VERSION
		);
		wp_localize_script('aam-admin-js', 'aamlocalizevars', array(
				'dateformat'			=> get_option('date_format'),
				'text_copied'			=> __('Copied to clipboard', 'atom-appointment-management'),
				'text_delete_formfield'	=> __('Are you sure? You will no longer be able to view the associated data.', 'atom-appointment-management'),
				'text_delete'			=> __('Do you want to delete?', 'atom-appointment-management'),
				'text_cancelled'		=> __('Cancelled', 'atom-appointment-management'),
				'text_confirmed'		=> __('Confirmed', 'atom-appointment-management'),
				'ajaxurl' 				=> admin_url( 'admin-ajax.php')
			)
		);

		wp_enqueue_style('wp-color-picker');
		wp_enqueue_style('jquery-ui-css', plugins_url(ATOM_AAM_PLUGIN_SLUG) . '/css/jquery-ui.css');
		wp_enqueue_style('jquery-ui-datepicker');
		wp_enqueue_style(
			'aam-admin-css',
			plugins_url(ATOM_AAM_PLUGIN_SLUG) . '/css/styles-admin.min.css',
			array(),
			ATOM_AAM_PLUGIN_VERSION
		);
	}

	public function add_admin_menu() {
        add_menu_page (
            __('Appointments', 'atom-appointment-management'),
            __('Appointments', 'atom-appointment-management') . $this->admin_notification_badge(),
            'manage_options',
            ATOM_AAM_PLUGIN_SLUG,
            array($this, 'setup_adminpage'),
            'dashicons-calendar-alt',
            '18'
        );

		add_submenu_page(
			ATOM_AAM_PLUGIN_SLUG,
			__('Appointments', 'atom-appointment-management'),
			__('Settings', 'atom-appointment-management'),
			'manage_options',
			ATOM_AAM_PLUGIN_SLUG . '-settings',
			function() {
				$this->setup_adminpage('settings');
			}
		);

		add_submenu_page(
			ATOM_AAM_PLUGIN_SLUG,
			__('Appointments', 'atom-appointment-management'),
			__('Rule-Based Appointments', 'atom-appointment-management'),
			'manage_options',
			ATOM_AAM_PLUGIN_SLUG . '-rule-based',
			function() {
				$this->setup_adminpage('rule-based');
			}
		);

		add_submenu_page(
			ATOM_AAM_PLUGIN_SLUG,
			__('Appointments', 'atom-appointment-management'),
			__('Individual Appointments', 'atom-appointment-management'),
			'manage_options',
			ATOM_AAM_PLUGIN_SLUG . '-individual',
			function() {
				$this->setup_adminpage('individual');
			}
		);

		add_submenu_page(
			ATOM_AAM_PLUGIN_SLUG,
			__('Appointments', 'atom-appointment-management'),
			__('E-Mails', 'atom-appointment-management'),
			'manage_options',
			ATOM_AAM_PLUGIN_SLUG . '-email',
			function() {
				$this->setup_adminpage('email');
			}
		);
    }

	private function admin_notification_badge() {
		global $wpdb;
		$notif_title = __('New Appointment', 'atom-appointment-management');

		if (!($notif_count = get_transient('aam_admin_notification_badge') )) {
			$data = $wpdb->get_results("SELECT id FROM " . ATOM_AAM_TABLE_ENTRIES . " WHERE confirmed = 0 AND start >= CURDATE();");
			$notif_count = count($data);
			set_transient('aam_admin_notification_badge', $notif_count, HOUR_IN_SECONDS);
		}

		if ($notif_count > 0) {
			return " <span class='update-plugins count-$notif_count' title='$notif_title'><span class='update-count'>" . number_format_i18n($notif_count) . "</span></span>";
		} else {
			return;
		}
	}

    public function setup_adminpage($active_tab = false) {
    	global $wpdb;

		if (!$active_tab) $active_tab = 'table';

        ?>
        <div class="atom wrap">

    		<h1><?php _e('Appointment Management', 'atom-appointment-management'); ?></h1>

    		<h2 class="nav-tab-wrapper">
    			<a href="?page=<?php echo ATOM_AAM_PLUGIN_SLUG; ?>" class="nav-tab <?php echo $active_tab == 'table' ? 'nav-tab-active' : ''; ?>"><?php _e('Appointments', 'atom-appointment-management'); ?></a>
    			<a href="?page=<?php echo ATOM_AAM_PLUGIN_SLUG; ?>-settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>"><?php _e('Settings', 'atom-appointment-management'); ?></a>
				<a href="?page=<?php echo ATOM_AAM_PLUGIN_SLUG; ?>-rule-based" class="nav-tab <?php echo $active_tab == 'rule-based' ? 'nav-tab-active' : ''; ?>"><?php _e('Rule-Based Appointments', 'atom-appointment-management'); ?></a>
    			<a href="?page=<?php echo ATOM_AAM_PLUGIN_SLUG; ?>-individual" class="nav-tab <?php echo $active_tab == 'individual' ? 'nav-tab-active' : ''; ?>"><?php _e('Individual Appointments', 'atom-appointment-management'); ?></a>
				<a href="?page=<?php echo ATOM_AAM_PLUGIN_SLUG; ?>-email" class="nav-tab <?php echo $active_tab == 'email' ? 'nav-tab-active' : ''; ?>"><?php _e('E-Mails', 'atom-appointment-management'); ?></a>
    		</h2>

			<?php

			settings_errors();

			switch ($active_tab) {
				case 'table':
				default:
					$this->aam->load_view('admin/tab-table');
					break;
				case 'settings':
					$this->clear_transients();
					$this->aam->load_view('admin/tab-settings');
					break;
				case 'rule-based':
					$this->clear_transients();
					$this->aam->load_view('admin/tab-rule-based');
					break;
				case 'individual':
					$this->clear_transients();
					$this->aam->load_view('admin/tab-individual');
					break;
				case 'email':
					$this->aam->load_view('admin/tab-email');
					break;
			}

			?>

    	</div>
        <?php
    }

	public function handle_adminpage_actions() {

		if (!$this->is_aam_admin_page()) return;

		global $wpdb;

		// CONFIRM AND DELETE ACTIONS
    	if (isset($_GET['action']) && isset($_GET['id']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'action')) {
    		$id = intval($_GET['id']);
    		if ($_GET['action'] == "acc") {
    			$wpdb->update(
    				ATOM_AAM_TABLE_ENTRIES, 		// table name
    				array( 				// data
    					'confirmed'	=> 1
    				),
    				array( 				// where clause
    					'id'	=> $id
    				)
    			);
				wp_schedule_single_event(time(), 'aam_async_appointment_confirmed_tasks', array($id));
    		} else if ($_GET['action'] == "del") {
    			$wpdb->update(
    				ATOM_AAM_TABLE_ENTRIES, 		// table name
    				array( 				// data
    					'confirmed'	=> -1
    				),
    				array( 				// where clause
    					'id'	=> $id
    				)
    			);
				wp_schedule_single_event(time(), 'aam_async_appointment_cancelled_tasks', array($id));
    		} else if ($_GET['action'] == "del_permanent") {
				$wpdb->delete(
    				ATOM_AAM_TABLE_ENTRIES, 	// table name
    				array( 				// where clause
    					'id'	=> $id
    				)
    			);
    		} else if ($_GET['action'] == "rec") {
    			$wpdb->update(
    				ATOM_AAM_TABLE_ENTRIES, 		// table name
    				array( 				// data
    					'confirmed'	=> 0
    				),
    				array( 				// where clause
    					'id'	=> $id
    				)
    			);
    		} else if ($_GET['action'] == "del_excpt") {
    			$wpdb->delete(
    				ATOM_AAM_TABLE_EXCEPTIONS, 	// table name
    				array( 				// where clause
    					'id'	=> $id
    				)
    			);
    		} else if ($_GET['action'] == "del_slot") {
    			$wpdb->delete(
    				ATOM_AAM_TABLE_SLOTS, 	// table name
    				array( 				// where clause
    					'id'	=> $id
    				)
    			);
    		}
			wp_redirect(get_admin_url() . 'admin.php?page=' . sanitize_text_field($_GET['page']));
    	}

    	// ADD EXCEPTION ACTION
    	if (isset($_POST['atom_submit_exception'])) {

			$excpt_begin = $this->validate_date($_POST['atom_exception_begin'], 'Y-m-d');
			$excpt_end = $this->validate_date($_POST['atom_exception_end'], 'Y-m-d');
			$excpt_category = (isset($_POST['atom_exception_category'])) ? intval($_POST['atom_exception_category']) : -1;
			$excpt_description = (isset($_POST['atom_exception_description'])) ? sanitize_text_field($_POST['atom_exception_description']) : '';

			if (!isset($_POST['atom_exception_fullday'])) {
				$excpt_begin .= ' ' . $this->validate_time($_POST['atom_exception_begin_time']);
				$excpt_end .= ' ' . $this->validate_time($_POST['atom_exception_end_time']);
			} else {
				$excpt_begin .= ' 00:00';
				$excpt_end .= ' 00:00';
			}

    		$wpdb->insert(
    			ATOM_AAM_TABLE_EXCEPTIONS, 	// table name
    			array( 				// data
    				'excpt_begin'		=> $excpt_begin,
    				'excpt_end'			=> $excpt_end,
					'excpt_category'	=> $excpt_category,
    				'excpt_description'	=> $excpt_description
    			)
    		);

			wp_redirect(get_admin_url() . 'admin.php?page=' . sanitize_text_field($_GET['page']));
    	}

	}

    public function add_options() {

	    // Settings
		register_setting(
			'atom_booking_settings',
			'atom_aam_settings',
			array($this, 'validate_input')
		);

	    	// General
	    	add_settings_section(
	    		'atom_section_general',
	    		__('General', 'atom-appointment-management'),
	    		'',
	    		'atom_booking_settings'
	    	);

				add_settings_field(
					'atom_notif_mail',
					__('E-mail address for notifications', 'atom-appointment-management'),
					array($this, 'display_notif_mail'),
					'atom_booking_settings',
					'atom_section_general'
				);

	    		add_settings_field(
	    			'atom_weeks_in_advance',
	    			__('Available weeks in advance', 'atom-appointment-management'),
					array($this, 'display_weeks_in_advance'),
	    			'atom_booking_settings',
	    			'atom_section_general'
	    		);

	    		add_settings_field(
	    			'atom_first_possible_booking',
	    			__('Hours until first appointment', 'atom-appointment-management'),
	    			array($this, 'display_first_possible_booking'),
	    			'atom_booking_settings',
	    			'atom_section_general'
	    		);

				add_settings_field(
	    			'atom_first_possible_booking_count_open_hours',
	    			__('Only count open hours', 'atom-appointment-management'),
	    			array($this, 'display_first_possible_booking_count_open_hours'),
	    			'atom_booking_settings',
	    			'atom_section_general'
	    		);

				add_settings_field(
	    			'atom_show_full_events',
	    			__('Show fully booked appointments', 'atom-appointment-management'),
	    			array($this, 'display_show_full_events'),
	    			'atom_booking_settings',
	    			'atom_section_general'
	    		);

				if ($this->aam->is_available_formal_language(get_locale())) {
					add_settings_field(
		    			'atom_formal_language',
		    			__('Use formal language', 'atom-appointment-management'),
		    			array($this, 'display_formal_language'),
		    			'atom_booking_settings',
		    			'atom_section_general'
		    		);
				}

				add_settings_field(
	    			'atom_ics_key',
	    			__('Enable calendar subscription', 'atom-appointment-management'),
	    			array($this, 'display_ics_key'),
	    			'atom_booking_settings',
	    			'atom_section_general'
	    		);

			// Categories
	    	add_settings_section(
	    		'atom_section_categories',
	    		__('Categories', 'atom-appointment-management'),
	    		'',
	    		'atom_booking_settings'
	    	);

	            add_settings_field(
	                'atom_categories',
	                __('Manage categories', 'atom-appointment-management'),
	                array($this, 'display_categories'),
	                'atom_booking_settings',
	                'atom_section_categories'
	            );

			// Color scheme
	    	add_settings_section(
	    		'atom_section_color',
	    		__('Color scheme', 'atom-appointment-management'),
	    		'',
	    		'atom_booking_settings'
	    	);

	        	add_settings_field(
	                'atom_color_main',
	                __('Main color', 'atom-appointment-management'),
	                function() {
						$this->display_settings_field('maincolor', 'color', array(
							'description'	=> __('Calendar entries and some form elements will be displayed in this color.', 'atom-appointment-management'),
							'divider'		=> true
						));
					},
	                'atom_booking_settings',
	                'atom_section_color'
	            );

				add_settings_field(
	                'atom_color_bg',
	                __('Background color', 'atom-appointment-management'),
	                function() {
						$this->display_settings_field('color_bg', 'color', array(
							'description'	=> __('Background color of the calendar and popup. Make sure to adjust text color to keep everything readable.', 'atom-appointment-management'),
							'divider'		=> true
						));
					},
	                'atom_booking_settings',
	                'atom_section_color'
	            );

				add_settings_field(
	                'atom_color_border',
	                __('Border color', 'atom-appointment-management'),
	                function() {
						$this->display_settings_field('color_border', 'color', array(
							'description'	=> __('Color of table cells and rows.', 'atom-appointment-management'),
							'divider'		=> true
						));
					},
	                'atom_booking_settings',
	                'atom_section_color'
	            );

				add_settings_field(
	                'atom_color_text',
	                __('Text color', 'atom-appointment-management'),
	                function() {
						$this->display_settings_field('color_text', 'color', array(
							'description'	=> __('Color of text for calendar labels and popup. Make sure this has enough contrast with "Background color".', 'atom-appointment-management'),
							'divider'		=> true
						));
					},
	                'atom_booking_settings',
	                'atom_section_color'
	            );

				add_settings_field(
	                'atom_color_entrylabel_text',
	                __('Calendar entry text color', 'atom-appointment-management'),
	                function() {
						$this->display_settings_field('color_entrylabel_text', 'color', array(
							'description'	=> __('Color of text for calendar entries. Make sure this has enough contrast with "Main color".', 'atom-appointment-management'),
							'divider'		=> true
						));
					},
	                'atom_booking_settings',
	                'atom_section_color'
	            );

	        // Content
	    	add_settings_section(
	    		'atom_section_content',
	    		__('Content', 'atom-appointment-management'),
	    		'',
	    		'atom_booking_settings'
	    	);

	    		add_settings_field(
	    			'atom_modal_infotext',
	    			__('Description next to form', 'atom-appointment-management'),
	    			array($this, 'display_modal_infotext'),
	    			'atom_booking_settings',
	    			'atom_section_content'
	    		);

	            // add_settings_field(
	    		// 	'atom_modal_backgroundimage',
	    		// 	__('Background image', 'atom-appointment-management'),
	    		// 	array($this, 'display_modal_backgroundimage'),
	    		// 	'atom_booking_settings',
	    		// 	'atom_section_content'
	    		// );

				add_settings_field(
	    			'atom_modal_inquiry_thankyou',
	    			__('Inquiry thank you text', 'atom-appointment-management'),
	    			array($this, 'display_modal_inquiry_thankyou'),
	    			'atom_booking_settings',
	    			'atom_section_content'
	    		);

			// Form fields
	    	add_settings_section(
	    		'atom_section_formfields',
	    		__('Form fields', 'atom-appointment-management'),
	    		'',
	    		'atom_booking_settings'
	    	);

	            add_settings_field(
	                'atom_formfields',
	                __('Manage form fields', 'atom-appointment-management'),
	                array($this, 'display_formfields'),
	                'atom_booking_settings',
	                'atom_section_formfields'
	            );

				add_settings_field(
					'atom_send_button_label',
					__('Send button label', 'atom-appointment-management'),
					array($this, 'display_send_button_label'),
					'atom_booking_settings',
					'atom_section_formfields'
				);

			// CTA
	    	add_settings_section(
	    		'atom_section_cta',
	    		__('Call To Action', 'atom-appointment-management'),
	    		array($this, 'display_cta'),
	    		'atom_booking_settings'
	    	);

				add_settings_field(
					'atom_cta1_link',
					__('Link', 'atom-appointment-management'),
					array($this, 'display_cta1_link'),
					'atom_booking_settings',
					'atom_section_cta'
				);
				add_settings_field(
					'atom_cta1_img',
					__('Image', 'atom-appointment-management'),
					array($this, 'display_cta1_img'),
					'atom_booking_settings',
					'atom_section_cta'
				);

				add_settings_field(
					'atom_cta2_link',
					__('Link', 'atom-appointment-management'),
					array($this, 'display_cta2_link'),
					'atom_booking_settings',
					'atom_section_cta'
				);
				add_settings_field(
					'atom_cta2_img',
					__('Image', 'atom-appointment-management'),
					array($this, 'display_cta2_img'),
					'atom_booking_settings',
					'atom_section_cta'
				);
				add_settings_field(
					'atom_cta_redirect',
					__('Redirect URL', 'atom-appointment-management'),
					array($this, 'display_cta_redirect_url'),
					'atom_booking_settings',
					'atom_section_cta'
				);

			// Privacy
			add_settings_section(
				'atom_section_privacy',
				__('Privacy', 'atom-appointment-management'),
				array($this, 'display_privacy'),
				'atom_booking_settings'
			);

				add_settings_field(
					'atom_privacy_mode',
					__('Require user consent for submitting and processing data', 'atom-appointment-management'),
					array($this, 'display_privacy_mode'),
					'atom_booking_settings',
					'atom_section_privacy'
				);
				add_settings_field(
					'atom_privacy_text',
					__('Privacy information text', 'atom-appointment-management'),
					array($this, 'display_privacy_text'),
					'atom_booking_settings',
					'atom_section_privacy'
				);
				add_settings_field(
					'atom_privacy_link',
					__('Link to privacy policy', 'atom-appointment-management'),
					array($this, 'display_privacy_link'),
					'atom_booking_settings',
					'atom_section_privacy'
				);
				add_settings_field(
					'atom_privacy_autodelete',
					__('Automatically delete older entries', 'atom-appointment-management'),
					array($this, 'display_privacy_autodelete'),
					'atom_booking_settings',
					'atom_section_privacy'
				);

			// Exchange
			add_settings_section(
				'atom_section_exchange',
				__('Microsoft Exchange', 'atom-appointment-management'),
				array($this, 'display_exchange'),
				'atom_booking_settings'
			);

				add_settings_field(
					'atom_exchange_activated',
					__('Activate Microsoft Exchange Connection', 'atom-appointment-management'),
					array($this, 'display_exchange_activated'),
					'atom_booking_settings',
					'atom_section_exchange'
				);
				add_settings_field(
					'atom_exchange_server',
					__('Exchange Server', 'atom-appointment-management'),
					array($this, 'display_exchange_server'),
					'atom_booking_settings',
					'atom_section_exchange'
				);
				add_settings_field(
					'atom_exchange_username',
					__('Exchange Username', 'atom-appointment-management'),
					array($this, 'display_exchange_username'),
					'atom_booking_settings',
					'atom_section_exchange'
				);
				add_settings_field(
					'atom_exchange_password',
					__('Exchange Password', 'atom-appointment-management'),
					array($this, 'display_exchange_password'),
					'atom_booking_settings',
					'atom_section_exchange'
				);

			// Analytics
			add_settings_section(
				'atom_section_analytics',
				__('Analytics', 'atom-appointment-management'),
				array($this, 'display_analytics'),
				'atom_booking_settings'
			);

				add_settings_field(
					'atom_analytics_disable_admins',
					__('Disable conversion tracking for logged in administrators', 'atom-appointment-management'),
					array($this, 'display_analytics_disable_admins'),
					'atom_booking_settings',
					'atom_section_analytics'
				);


		// Rule Based
		register_setting(
			'atom_booking_rulebased',
			'atom_aam_settings_rulebased',
			array($this, 'validate_input')
		);

			// Office hours
	    	add_settings_section(
	    		'atom_section_officehours',
	    		__('Office hours', 'atom-appointment-management'),
	    		'',
	    		'atom_booking_rulebased'
	    	);

	    		add_settings_field(
	    			'atom_workdays',
	    			__('Edit work days and hours', 'atom-appointment-management'),
	    			array($this, 'display_workdays'),
	    			'atom_booking_rulebased',
	    			'atom_section_officehours'
	    		);

	    	// Duration
	    	add_settings_section(
	    		'atom_section_duration',
	    		__('Duration', 'atom-appointment-management'),
	    		'',
	    		'atom_booking_rulebased'
	    	);

	    		add_settings_field(
	    			'atom_event_duration',
	    			__('Duration of appointments', 'atom-appointment-management') . ' (hh:mm)',
	    			array($this, 'display_event_duration'),
	    			'atom_booking_rulebased',
	    			'atom_section_duration'
	    		);

	    		add_settings_field(
	    			'atom_event_gap',
	    			__('Break between appointments', 'atom-appointment-management') . ' (hh:mm)',
	    			array($this, 'display_event_gap'),
	    			'atom_booking_rulebased',
	    			'atom_section_duration'
	    		);

			// Appointment
	    	add_settings_section(
	    		'atom_section_appointment',
	    		__('Appointment', 'atom-appointment-management'),
	    		'',
	    		'atom_booking_rulebased'
	    	);

				add_settings_field(
					'atom_rulebased_title',
					__('Appointment title', 'atom-appointment-management'),
					array($this, 'display_rulebased_title'),
					'atom_booking_rulebased',
					'atom_section_appointment'
				);

				// add_settings_field(
				// 	'atom_rulebased_price',
				// 	__('Price', 'atom-appointment-management'),
				// 	array($this, 'display_rulebased_price'),
				// 	'atom_booking_rulebased',
				// 	'atom_section_appointment'
				// );

				add_settings_field(
					'atom_rulebased_moreinfo',
					__('More information link', 'atom-appointment-management'),
					array($this, 'display_rulebased_moreinfo'),
					'atom_booking_rulebased',
					'atom_section_appointment'
				);

				add_settings_field(
	    			'atom_bookings_per_slot_rulebased',
	    			__('Maximum number of appointments per time slot', 'atom-appointment-management'),
	    			array($this, 'display_bookings_per_slot_rulebased'),
	    			'atom_booking_rulebased',
	    			'atom_section_appointment'
	    		);

		// Individual
		register_setting(
			'atom_booking_individual',
			'atom_aam_settings_individual',
			array($this, 'validate_input')
		);

			// Duration
			add_settings_section(
				'atom_section_individual_settings',
				__('Settings', 'atom-appointment-management'),
				'',
				'atom_booking_individual'
			);

	    // EMAIL
		register_setting(
			'atom_booking_email',
			'atom_aam_settings_email',
			array($this, 'validate_input')
		);

			// Request Received Mail
			add_settings_section(
				'atom_section_received',
				__('Request received mail', 'atom-appointment-management'),
				array($this, 'display_received'),
				'atom_booking_email'
			);

				add_settings_field(
					'atom_received_enabled',
					__('Enable request received mail', 'atom-appointment-management'),
					array($this, 'display_received_enabled'),
					'atom_booking_email',
					'atom_section_received'
				);

				add_settings_field(
					'atom_received_subject',
					__('Subject', 'atom-appointment-management'),
					array($this, 'display_received_subject'),
					'atom_booking_email',
					'atom_section_received'
				);

				add_settings_field(
					'atom_received_text',
					__('Text', 'atom-appointment-management'),
					array($this, 'display_received_text'),
					'atom_booking_email',
					'atom_section_received'
				);

				add_settings_field(
					'atom_cancel_info_text',
					__('Cancel info', 'atom-appointment-management'),
					array($this, 'display_cancel_info_text'),
					'atom_booking_email',
					'atom_section_received'
				);

			// Automated E-Mails
			add_settings_section(
				'atom_section_automated_emails',
				__('Automated E-Mails', 'atom-appointment-management'),
				'',
				'atom_booking_email'
			);

				add_settings_field(
					'atom_automated_emails_enabled',
					__('Enable automated e-mails', 'atom-appointment-management'),
					array($this, 'display_automated_emails_enabled'),
					'atom_booking_email',
					'atom_section_automated_emails'
				);

	    	// Confirmation Mail
	    	add_settings_section(
	    		'atom_section_confirm',
	    		__('Confirmation mail', 'atom-appointment-management'),
	    		array($this, 'display_confirm'),
	    		'atom_booking_email'
	    	);

	    		add_settings_field(
	    			'atom_confirm_subject',
	    			__('Subject', 'atom-appointment-management'),
	    			array($this, 'display_confirm_subject'),
	    			'atom_booking_email',
	    			'atom_section_confirm'
	    		);

	    		add_settings_field(
	    			'atom_confirm_text',
	    			__('Text', 'atom-appointment-management'),
	    			array($this, 'display_confirm_text'),
	    			'atom_booking_email',
	    			'atom_section_confirm'
	    		);

	    	// Cancellation Mail
	    	add_settings_section(
	    		'atom_section_cancel',
	    		__('Cancellation mail', 'atom-appointment-management'),
	    		array($this, 'display_cancel'),
	    		'atom_booking_email'
	    	);

	    		add_settings_field(
	    			'atom_cancel_subject',
	    			__('Subject', 'atom-appointment-management'),
	    			array($this, 'display_cancel_subject'),
	    			'atom_booking_email',
	    			'atom_section_cancel'
	    		);

	    		add_settings_field(
	    			'atom_cancel_text',
	    			__('Text', 'atom-appointment-management'),
	    			array($this, 'display_cancel_text'),
	    			'atom_booking_email',
	    			'atom_section_cancel'
	    		);

			// Reminder Mail
	    	add_settings_section(
	    		'atom_section_reminder',
	    		__('Reminder mail', 'atom-appointment-management'),
	    		array($this, 'display_reminder'),
	    		'atom_booking_email'
	    	);

				add_settings_field(
					'atom_reminder_schedule_1',
					__('Schedule reminder 1 (X days before appointment)', 'atom-appointment-management'),
					array($this, 'display_reminder_schedule_1'),
					'atom_booking_email',
					'atom_section_reminder'
				);

				add_settings_field(
					'atom_reminder_schedule_2',
					__('Schedule reminder 2 (X days before appointment)', 'atom-appointment-management'),
					array($this, 'display_reminder_schedule_2'),
					'atom_booking_email',
					'atom_section_reminder'
				);

	    		add_settings_field(
	    			'atom_reminder_subject',
	    			__('Subject', 'atom-appointment-management'),
	    			array($this, 'display_reminder_subject'),
	    			'atom_booking_email',
	    			'atom_section_reminder'
	    		);

	    		add_settings_field(
	    			'atom_reminder_text',
	    			__('Text', 'atom-appointment-management'),
	    			array($this, 'display_reminder_text'),
	    			'atom_booking_email',
	    			'atom_section_reminder'
	    		);

			// Sender
			add_settings_section(
				'atom_section_sender',
				__('Sender for automated emails', 'atom-appointment-management'),
				array($this, 'display_sender'),
				'atom_booking_email'
			);

				add_settings_field(
					'atom_sender_name',
					__('Sender name', 'atom-appointment-management'),
					array($this, 'display_sender_name'),
					'atom_booking_email',
					'atom_section_sender'
				);

				add_settings_field(
					'atom_sender_email',
					__('Sender email', 'atom-appointment-management'),
					array($this, 'display_sender_email'),
					'atom_booking_email',
					'atom_section_sender'
				);

    }

	function display_settings_field($key, $type = 'text', $args = array()) {

		$additional_attributes = array();
		$additional_attributes = implode(' ' , $additional_attributes);

		switch ($type) {
			case 'color':
				echo '<input type="text" name="atom_aam_settings[' . $key . ']" class="atom_colorpicker" value="' . $this->get_option($key) . '" ' . $additional_attributes . ' />';
				break;
			default:
				echo '<input type="' . $type . '" name="atom_aam_settings[' . $key . ']" value="' . $this->get_option($key) . '" ' . $additional_attributes . ' />';
				break;
		}

		if (isset($args['description'])) echo '</td></tr><tr><td colspan="2" class="atom_description">' . $args['description'] . '<p></p>';
		if (isset($args['divider']) && $args['divider']) echo '<hr />';

	}

    function display_weeks_in_advance() {
    	?>
    		<input type="number" name="atom_aam_settings[weeks_in_advance]" id="atom_weeks_in_advance" value="<?php echo $this->get_option('weeks_in_advance'); ?>" min="1" />
			</td></tr><tr><td colspan="2" class="atom_description">
			<p>
				<?php _e('The number of weeks for which appointments are availiable in advance', 'atom-appointment-management'); ?>
			</p>
			<hr />
    	<?php
    }

    function display_first_possible_booking() {
    	?>
    		<input type="number" name="atom_aam_settings[first_possible_booking]" id="atom_first_possible_booking" value="<?php echo $this->get_option('first_possible_booking'); ?>" min="0" />
			</td></tr><tr><td colspan="2" class="atom_description">
			<p>
				<?php _e('The minimum number of hours between the booking and the start of an appointment.', 'atom-appointment-management'); ?>
			</p>
    	<?php
    }


	function display_first_possible_booking_count_open_hours() {
    	?>
		<input type="checkbox" name="atom_aam_settings[first_possible_booking_count_open_hours]" id="atom_first_possible_booking_count_open_hours" <?php if ($this->get_option('first_possible_booking_count_open_hours')) echo 'checked'; ?> />
		</td></tr><tr><td colspan="2" class="atom_description">
		<p>
			<?php _e('When activated, only open hours count for the above minimum number of hours between booking and start of an appointment.', 'atom-appointment-management'); ?>
		</p>
		<hr />
    	<?php
    }

	function display_show_full_events() {
    	?>
    		<input type="checkbox" name="atom_aam_settings[show_full_events]" id="atom_show_full_events" <?php if ($this->get_option('show_full_events')) echo 'checked'; ?> />
			</td></tr><tr><td colspan="2" class="atom_description">
			<p>
				<?php _e('Show fully booked appointments as greyed out entries in the calendar.', 'atom-appointment-management'); ?>
			</p>
			<hr />
    	<?php
    }

	function display_formal_language() {
    	?>
    		<input type="checkbox" name="atom_aam_settings[formal_language]" value="formal_language" id="atom_formal_language" <?php if ($this->get_option('formal_language')) echo 'checked'; ?> />
			</td></tr><tr><td colspan="2" class="atom_description">
			<p>
				<?php _e('Enable formal language on the frontend and backend of this plugin.', 'atom-appointment-management'); ?>
			</p>
			<hr />
    	<?php
    }

	function display_ics_key() {
		?>
    		<input type="checkbox" name="atom_aam_pro" id="atom_enable_ics" disabled />
			</td></tr><tr><td colspan="2" class="atom_description">
			<p>
				<?php _e('ICS Calendar subscription allows you to automatically display all your booked appointments in your favourite calendar app (like Apple Calendar, Microsoft Outlook or Google Calendar).', 'atom-appointment-management'); ?>
			</p>
			<?php $this->display_pro_info(); ?>
			<hr />
    	<?php
    }

    function display_bookings_per_slot_rulebased() {
    	?>
    		<input type="number" name="atom_aam_settings_rulebased[bookings_per_slot_rulebased]" id="atom_bookings_per_slot_rulebased" value="<?php echo $this->get_option('bookings_per_slot_rulebased'); ?>" min="1" />
			</td></tr><tr><td colspan="2" class="atom_description">
			<p>
				<?php _e('The number of appointments that can be booked for the same time slot.', 'atom-appointment-management'); ?>
			</p>
			<hr />
    	<?php
    }

	function display_rulebased_title() {
    	?>
    		<input type="text" name="atom_aam_settings_rulebased[rulebased_title]" id="atom_rulebased_title" value="<?php echo $this->get_option('rulebased_title'); ?>" />
			</td></tr><tr><td colspan="2" class="atom_description">
			<p>
				<?php _e('This title will be displayed in the calendar and when users book the appointment.', 'atom-appointment-management'); ?>
			</p>
			<hr />
    	<?php
    }

	function display_rulebased_price() {
    	?>
    		<input type="number" name="atom_aam_settings_rulebased[rulebased_price]" id="atom_rulebased_price" value="<?php echo $this->get_option('rulebased_price'); ?>" />
			</td></tr><tr><td colspan="2" class="atom_description">
			<p>
				<?php
				_e('The price will be displayed to your customer during booking.', 'atom-appointment-management');
				if (ATOM_AAM_PLUGIN_SLUG == 'atom-appointment-management-pro') {
					_e('With PayPal configured, customers will be instructed to pay before they can make the appointment.', 'atom-appointment-management');
				} else {
					_e('With AAM Pro and PayPal configured, customers can be instructed to pay before they can make the appointment.', 'atom-appointment-management');
				}
				?>
			</p>
			<hr />
    	<?php
    }

	function display_rulebased_moreinfo() {
			$this->display_pages_and_posts_dropdown('atom_aam_settings_rulebased', 'rulebased_moreinfo');
    	?>
			</td></tr><tr><td colspan="2" class="atom_description">
			<p>
				<?php _e('This title will be displayed in the calendar and when users book the appointment.', 'atom-appointment-management'); ?>
			</p>
			<hr />
    	<?php
    }

	function display_license_key() {
    	?>
    		<input type="text" name="atom_aam_settings[license_key]" id="atom_license_key" value="<?php echo $this->get_option('license_key'); ?>" />
			</td></tr><tr><td colspan="2" class="atom_description">
			<p>
				<?php _e('Enter your license key here to enable plugin updates.', 'atom-appointment-management'); ?>
			</p>
			<hr />
    	<?php
    }

    function display_notif_mail() {
    	?>
    		<input type="email" name="atom_aam_settings[notif_mail]" id="atom_notif_mail" value="<?php echo $this->get_option('notif_mail'); ?>" />
			</td></tr><tr><td colspan="2" class="atom_description">
			<p>
				<?php _e('Notifications are sent to this mail address when a new appointment is booked.', 'atom-appointment-management'); ?>
			</p>
			<hr />
    	<?php
    }

    function display_workdays() {
    	$opt = $this->get_option('workdays');

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
					<td><input type="checkbox" data-target="<?php echo $i; ?>" class="atom_check_workdays" <?php $this->isInactiveWorkday($opt[$i], '', 'checked'); ?>> <?php echo $day_names[$i]; ?></td>
					<td><input type="time" name="atom_aam_settings_rulebased[workdays][<?php echo $i; ?>][start]" id="atom_workdays[<?php echo $i; ?>][start]" value="<?php echo $opt[$i]['start'] ?>" <?php $this->isInactiveWorkday($opt[$i], 'readonly'); ?> /></td>
					<td><input type="time" name="atom_aam_settings_rulebased[workdays][<?php echo $i; ?>][end]" id="atom_workdays[<?php echo $i; ?>][end]" value="<?php echo $opt[$i]['end'] ?>" <?php $this->isInactiveWorkday($opt[$i], 'readonly'); ?> /></td>
				</tr>
			<?php } ?>

		</table>
		</td></tr><tr><td colspan="2" class="atom_description">
		<p>
			<?php
			_e('Edit available days and hours for appointments. Exceptions to this rules, like holidays or vacations can be set below or by removing slots from the front-end widget.', 'atom-appointment-management');
			?>
		</p>
		<hr />
    <?php
    }

    function isInactiveWorkday($opt, $string1, $string2 = '') {
    	echo ($opt['start'] == '00:00' && $opt['end'] == '00:00') ? $string1 : $string2;
    }

    function display_event_duration() {
    	?>
    		<input type="time" name="atom_aam_settings_rulebased[event_duration]" id="atom_event_duration" value="<?php echo $this->get_option('event_duration'); ?>" />
			</td></tr><tr><td colspan="2" class="atom_description">
			<p>
				<?php _e('The duration of a single appointment in hours and minutes', 'atom-appointment-management'); ?>
			</p>
			<hr />
    	<?php
    }

    function display_event_gap() {
    	?>
    		<input type="time" name="atom_aam_settings_rulebased[event_gap]" id="atom_event_gap" value="<?php echo $this->get_option('event_gap'); ?>" />
			</td></tr><tr><td colspan="2" class="atom_description">
			<p>
				<?php _e('The duration between two appointments in hours and minutes.', 'atom-appointment-management'); ?>
			</p>
			<hr />
    	<?php
    }

    function display_maincolor() {
        ?>
        <input type="text" value="<?php echo $this->get_option('maincolor'); ?>" class="atom_colorpicker" name="atom_aam_settings[maincolor]" />
		</td></tr><tr><td colspan="2" class="atom_description">
		<p>
			<?php _e('The calendar and some form elements will be displayed in this color.', 'atom-appointment-management'); ?>
		</p>
		<hr />
        <?php
    }

	function display_categories() {
		?>
		<div class="aam-pro-blur">
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php _e('Name', 'atom-appointment-management'); ?></th>
						<th><?php _e('Color scheme', 'atom-appointment-management'); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody id="atom_aam_categories">
					<tr class="atom_aam_category">
						<td><input type="text" value="" name="atom_aam_pro" /></td>
						<td><input type="text" value="" class="atom_category_colorpicker" name="atom_aam_pro" /></td>
						<td class="actions">
							<button class="button" disabled><?php _e('Not yet saved', 'atom-appointment-management'); ?></button>
						</td>
					</tr>

					<tr>
						<td><input type="text" value="Category Name" name="atom_aam_pro" disabled /></td>
						<td><input type="text" value="##dd9933" class="atom_colorpicker" name="atom_aam_pro" /></td>
						<td class="actions">
							<a href="#" class="button"><?php _e('Category Settings', 'atom-appointment-management'); ?></a>
							<button class="button remove-category" type="button" role="button" disabled><?php _e('Remove', 'atom-appointment-management'); ?></button>
						</td>
					</tr>

				</tbody>
				<tfoot>
					<tr>
						<td colspan="2"></td>
						<td class="actions"><button id="atom_aam_add_category" class="button" type="button" disabled><?php _e('Add', 'atom-appointment-management'); ?></button></td>
					</tr>
				</tfoot>
			</table>
		</div>

		</td></tr><tr><td colspan="2" class="atom_description">
		<p>
			<?php
				echo sprintf(__('Categories allow you to group individual appointments.<br />Display only select categories in your calendar by adding the category parameter to your shortcode, for example: %s', 'atom-appointment-management'), 'Category Name');
			?>
		</p>
		<?php $this->display_pro_info(); ?>
		<hr />
		<?php
	}

    function display_modal_infotext() {
        $option = $this->get_option('modal_infotext');
    	$args = array(
			'textarea_name' => 'atom_aam_settings[modal_infotext]',
			'media_buttons' => false

		);
    	wp_editor( $option, "atom_modal_infotext", $args );
		?>
		</td></tr><tr><td colspan="2" class="atom_description">
		<p>
			<?php _e('This text will be displayed next to the form when a user has selected a date.', 'atom-appointment-management'); ?>
		</p>
		<hr />
		<?php
    }

	function display_modal_inquiry_thankyou() {
		?>
		<textarea name="atom_aam_settings[modal_inquiry_thankyou]" id="atom_modal_inquiry_thankyou" rows="8"><?php echo $this->get_option('modal_inquiry_thankyou'); ?></textarea>
		</td></tr><tr><td colspan="2" class="atom_description">
		<p>
			<?php _e("This text will be displayed after a successful booking. %s is a placeholder and will be replaced with the user's email address.", 'atom-appointment-management'); ?>
		</p>
		<hr />
		<?php
    }

    function display_modal_backgroundimage() {
        $options = $this->get_option('modal_backgroundimage');
        $src = "";

        if ($options) {
            $image_attributes = wp_get_attachment_image_src($options, 'thumbnail');
            $src = $image_attributes[0];
        }
        ?>

        <div class="upload">
            <img src="<?php echo $src; ?>" height="150px" <?php if (!$options) {echo 'style="display:none;"';} ?> />
            <div>
                <input type="hidden" name="atom_aam_settings[modal_backgroundimage]" id="atom_modal_backgroundimage" value="<?php echo $options; ?>" />
                <button type="button" class="upload_image_button button"><?php _e('Choose/Upload Image', 'atom-appointment-management'); ?></button>
                <button type="button" class="remove_image_button button">&times;</button>
            </div>
        </div>

		</td></tr><tr><td colspan="2" class="atom_description">
		<p>
			<?php _e('Optional background image for the form. A static grey background will be displayed when no image is choosen.', 'atom-appointment-management'); ?>
		</p>
		<hr />

        <?php
    }

	function display_formfields() {
		$opt = $this->get_option('formfields');
		?>

		<div class="aam-pro-blur">
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php _e('Label', 'atom-appointment-management'); ?></th>
						<th><?php _e('ID', 'atom-appointment-management'); ?></th>
						<th><?php _e('Field type', 'atom-appointment-management'); ?></th>
						<th><?php _e('Required', 'atom-appointment-management'); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody id="atom_aam_formfields">

					<?php
					if ($opt) {
						foreach ($opt as $key => $value) {
							if ($value['label']) {
								$email = ($value['type'] == 'email');
							?>
							<tr>
								<td>
									<input type="text" value="<?php echo $value['label']; ?>" name="atom_aam_pro" />
								</td>
								<td>
									<?php if ($email) { ?>
										<select name="atom_aam_pro" disabled>
											<option value="email" selected><?php _e('E-Mail', 'atom-appointment-management'); ?></option>
										</select>
									<?php } else { ?>
										<select name="atom_aam_pro" class="type-input">
											<option value="text" <?php if($value['type'] == 'text') echo 'selected'; ?>><?php _e('Text', 'atom-appointment-management'); ?></option>
											<option value="tel" <?php if($value['type'] == 'tel') echo 'selected'; ?>><?php _e('Phone number', 'atom-appointment-management'); ?></option>
											<option value="select" <?php if($value['type'] == 'select') echo 'selected'; ?>><?php _e('Select field', 'atom-appointment-management'); ?></option>
										</select>
									<?php } ?>
								</td>
								<td>
									<?php if ($email) { ?>
										<input type="checkbox" name="atom_aam_pro" checked disabled />
									<?php } else { ?>
										<input type="checkbox" name="atom_aam_pro" <?php if(isset($value['required']) && $value['required']) echo 'checked'; ?> />
									<?php } ?>
								</td>
								<td>
									<?php if (!$email) { ?>
										<button class="button remove-formfield" type="button"><?php _e('Remove', 'atom-appointment-management'); ?></button>
									<?php } ?>
								</td>
							</tr>
							<?php
							}
						}
					}
					?>

				</tbody>
				<tfoot>
					<tr>
						<td colspan="2"></td>
						<td><button id="atom_aam_add_formfield" class="button" type="button" disabled><?php _e('Add', 'atom-appointment-management'); ?></button></td>
					</tr>
				</tfoot>
			</table>
		</div>

		</td></tr><tr><td colspan="2" class="atom_description">
		<p>
			<?php _e('Add the form fields your user has to fill out when he wants to book an appointment. You can also drag and reorder the fields as you like. The E-Mail field is required and cannot be deleted.', 'atom-appointment-management'); ?>
		</p>
		<?php $this->display_pro_info(); ?>
		<hr />
		<?php
	}

	function display_send_button_label() {
    	?>
    		<input type="text" name="atom_aam_settings[send_button_label]" id="atom_send_button_label" value="<?php echo $this->get_option('send_button_label'); ?>" />
			</td></tr><tr><td colspan="2" class="atom_description">
			<p>
				<?php _e('Changes the text on the button to send the form and make the appointment.', 'atom-appointment-management'); ?>
			</p>
			<hr />
    	<?php
    }

	function display_cta() {
    	echo "<p>" . __('When your customer successfully scheduled an appointment, you can offer him other actions to take, like subscribing to your newsletter or liking your facebook page.', 'atom-appointment-management') . "</p>";
    }

	function display_cta1_link() {
		$this->display_pages_and_posts_dropdown('atom_aam_settings', 'cta1_link');
    }

	function display_cta1_img() {

        $options = $this->get_option('cta1_img');
        $src = "";

        if ($options) {
            $image_attributes = wp_get_attachment_image_src($options, 'thumbnail');
            $src = $image_attributes[0];
        }
        ?>

        <div class="upload">
            <img src="<?php echo $src; ?>" height="150px" <?php if (!$options) {echo 'style="display:none;"';} ?> />
            <div>
                <input type="hidden" name="atom_aam_settings[cta1_img]" id="atom_cta1_img" value="<?php echo $options; ?>" />
                <button type="button" class="upload_image_button button"><?php _e('Choose/Upload Image', 'atom-appointment-management'); ?></button>
                <button type="button" class="remove_image_button button">&times;</button>
            </div>
        </div>

		</td></tr><tr><td colspan="2" class="atom_description">
		<p>
			<?php _e('The first CTA (Call to Action) field. Leave link and image empty to disable this feature.', 'atom-appointment-management'); ?>
		</p>
		<hr />

		<?php
	}

	function display_cta2_link() {
    	$this->display_pages_and_posts_dropdown('atom_aam_settings', 'cta2_link');
    }

	function display_cta2_img() {

        $options = $this->get_option('cta2_img');
        $src = "";

        if ($options) {
            $image_attributes = wp_get_attachment_image_src($options, 'thumbnail');
            $src = $image_attributes[0];
        }
        ?>

        <div class="upload">
            <img src="<?php echo $src; ?>" height="150px" <?php if (!$options) {echo 'style="display:none;"';} ?> />
            <div>
                <input type="hidden" name="atom_aam_settings[cta2_img]" id="atom_cta2_img" value="<?php echo $options; ?>" />
                <button type="button" class="upload_image_button button"><?php _e('Choose/Upload Image', 'atom-appointment-management'); ?></button>
                <button type="button" class="remove_image_button button">&times;</button>
            </div>
        </div>

		</td></tr><tr><td colspan="2" class="atom_description">
		<p>
			<?php _e('The second CTA (Call to Action) field. Leave link and image empty to disable this feature.', 'atom-appointment-management'); ?>
		</p>
		<hr />

		<?php
	}

	function display_cta_redirect_url() {
		$this->display_pages_and_posts_dropdown('atom_aam_settings', 'cta_redirect_url');
		?>
			</td></tr><tr><td colspan="2" class="atom_description">
			<p>
				<?php _e('As an alternative to the CTA images, you can specify a page where your customers will be automatically redirected after a successful booking.', 'atom-appointment-management'); ?>
			</p>
			<hr />
		<?php
	}

	function display_privacy() {
    	echo "<p>" . __('Enable this option to require the user to give his consent for processing and storing his data. This makes the plugin compliant with EU-DSGVO.', 'atom-appointment-management') . "</p>";
    }

	function display_privacy_mode() {
    	?>
    		<input type="checkbox" name="atom_aam_settings[privacy_mode]" id="atom_privacy_mode" value="privacy_mode_true" <?php if ($this->get_option('privacy_mode')) echo 'checked'; ?> />
    	<?php
    }

	function display_privacy_text() {
    	?>
    		<textarea name="atom_aam_settings[privacy_text]" id="atom_privacy_text" rows="5"><?php echo $this->get_option('privacy_text'); ?></textarea>
    	<?php
    }

	function display_privacy_link() {
		$this->display_pages_and_posts_dropdown('atom_aam_settings', 'privacy_link');
		echo '</td></tr><tr><td colspan="2" class="atom_description"><hr />';
    }

	function display_privacy_autodelete() {
		$current_value = $this->get_option('privacy_autodelete');
    	?>
    		<select name="atom_aam_settings[privacy_autodelete]" id="privacy_autodelete">
				<option value="0"><?php _e('Never', 'atom-appointment-management'); ?></option>
				<?php
				echo '<option value="0" disabled>---</option>';
				for ($i = 1; $i < 12; $i++) {
					$plural_label = sprintf(_n('after %s month', 'after %s months', $i, 'atom-appointment-management'), $i);
					echo '<option value="' . $i . '"' . (($current_value == $i) ? 'selected' : '') . '>' . $plural_label . '</option>';
				}
				echo '<option value="0" disabled>---</option>';
				for ($i = 1; $i < 6; $i++) {
					$plural_label = sprintf(_n('after %s year', 'after %s years', $i, 'atom-appointment-management'), $i);
					echo '<option value="' . $i * 12 . '"' . (($current_value == $i * 12) ? 'selected' : '') . '>' . $plural_label . '</option>';
				}
				?>
			</select>
			</td></tr><tr><td colspan="2" class="atom_description">
    	<?php
    }

	function display_exchange() {
    	echo "<p>" . __('Sync your appointments immediately with your Microsoft Exchange Calendar. Existing calendar entries will block the time slot for new appointments.', 'atom-appointment-management') . "</p>";
    }
	
	function display_exchange_activated() {
    	?>
    		<input type="checkbox" name="atom_aam_settings[exchange_activated]" id="atom_exchange_activated" value="exchange_activated" <?php if ($this->get_option('exchange_activated')) echo 'checked'; ?> />
		</td></tr><tr><td colspan="2" class="atom_description"><p><?php _e('You can also configure different Exchange accounts per category in the category settings.', 'atom-appointment-management') ?></p>
    	<?php
    }

	function display_exchange_server() {
    	?>
    		<input type="text" name="atom_aam_settings[exchange_server]" id="atom_exchange_server" value="<?php echo $this->get_option('exchange_server'); ?>">
    	<?php
    }

	function display_exchange_username() {
    	?>
    		<input type="text" name="atom_aam_settings[exchange_username]" id="atom_exchange_username" value="<?php echo $this->get_option('exchange_username'); ?>">
    	<?php
    }

	function display_exchange_password() {
    	?>
    		<input type="password" name="atom_aam_settings[exchange_password]" id="atom_exchange_password" value="<?php echo $this->get_option('exchange_password'); ?>" autocomplete="new-password">
    	<?php
    }

	function display_sender() {
    	echo "<p>" . sprintf(__('Specify a custom name and email address that automated messages from this plugin will be sent from.<br />To avoid your email being marked as spam, it is highly recommended that your "from" domain matches your website domain. Some hosts may also require that your "from" address is a legitimate address.<br />If you have problems receiving emails, consider using a plugin like %s and leaving the settings below empty.', 'atom-appointment-management'), '<a href="https://wordpress.org/plugins/wp-mail-smtp/">WP Mail SMTP</a>') . "</p>";
    }

	function display_sender_name() {
    	?>
    		<input type="text" name="atom_aam_settings_email[sender_name]" id="sender_name" value="<?php echo $this->get_option('sender_name'); ?>" />
    	<?php
    }

	function display_sender_email() {
    	?>
    		<input type="email" name="atom_aam_settings_email[sender_email]" id="sender_email" value="<?php echo $this->get_option('sender_email'); ?>" />
			</td></tr><tr><td colspan="2" class="atom_description">
    	<?php
    }

	function display_received() {
    	echo "<p>" . __('Your customers will receive a confirmation email when they request an appointment. The email contains an overview of the appointment details and you can customize the accompanying text and subject line.', 'atom-appointment-management') . "</p>";
    }

	function display_received_enabled() {
		?>
    		<input type="checkbox" name="atom_aam_settings_email[received_enabled]" id="atom_received_enabled" <?php if ($this->get_option('received_enabled')) echo 'checked'; ?> />
    	<?php
	}

	function display_received_subject() {
    	?>
    		<input type="text" name="atom_aam_settings_email[received_subject]" id="atom_received_subject" value="<?php echo $this->get_option('received_subject'); ?>" />
    	<?php
    }

    function display_received_text() {
    	?>
    		<textarea name="atom_aam_settings_email[received_text]" id="atom_received_text" rows="8"><?php echo $this->get_option('received_text'); ?></textarea>
			</td></tr><tr><td colspan="2" class="atom_description">
    	<?php
    }

	function display_cancel_info_text() {
    	?>
    		<textarea name="atom_aam_settings_email[cancel_info_text]" id="atom_cancel_info_text" rows="8"><?php echo $this->get_option('cancel_info_text'); ?></textarea>
			</td></tr><tr><td colspan="2" class="atom_description">
    	<?php
    }

	function display_automated_emails_enabled() {
		?>
    		<input type="checkbox" name="atom_aam_pro" disabled />
			</td></tr><tr><td colspan="2" class="atom_description">
			<p><?php _e('If enabled, your customer will receive automated and beautifully formatted e-mails when you confirm or cancel an appointment. If you disable this option, an e-mail window will pop up with the predefined text and you can send the mail manually.', 'atom-appointment-management'); ?></p>
			<?php $this->display_pro_info(); ?>
			<hr>
    	<?php
	}

	function display_confirm() {
    	echo "<p>" . __('Your customers will receive an email notification when you confirm their appointment. The email contains an overview of the appointment details and you can customize the accompanying text and subject line.', 'atom-appointment-management') . "</p>";
    }

    function display_confirm_subject() {
    	?>
    		<input type="text" name="atom_aam_settings_email[confirm_subject]" id="atom_confirm_subject" value="<?php echo $this->get_option('confirm_subject'); ?>" />
    	<?php
    }

    function display_confirm_text() {
    	?>
    		<textarea name="atom_aam_settings_email[confirm_text]" id="atom_confirm_text" rows="8"><?php echo $this->get_option('confirm_text'); ?></textarea>
			</td></tr><tr><td colspan="2" class="atom_description">
    	<?php
    }

	function display_cancel() {
    	echo "<p>" . __('Your customers will receive an email notification when you cancel their appointment. You can customize the text and subject line.', 'atom-appointment-management') . "</p>";
    }

    function display_cancel_subject() {
    	?>
    		<input type="text" name="atom_aam_settings_email[cancel_subject]" id="atom_cancel_subject" value="<?php echo $this->get_option('cancel_subject'); ?>" />
    	<?php
    }

    function display_cancel_text() {
    	?>
    		<textarea name="atom_aam_settings_email[cancel_text]" id="atom_cancel_text" rows="8"><?php echo $this->get_option('cancel_text'); ?></textarea>
			</td></tr><tr><td colspan="2" class="atom_description">
			<hr />
    	<?php
    }

	function display_reminder() {
    	echo "<p>" . __('Send automated emails to remind yourself and your customer about an upcoming appointment. You can schedule up to two reminder emails that will be sent the configured number of days before the appointment. Leave the schedule empty to disable reminder emails.', 'atom-appointment-management') . "</p>";
    }

	function display_reminder_schedule_1() {
    	?>
    		<input type="number" name="atom_aam_settings_email[reminder_schedule_1]" id="atom_reminder_schedule_1" value="<?php echo $this->get_option('reminder_schedule_1'); ?>" placeholder="<?php _e('Disabled', 'atom-appointment-management'); ?>" />
    	<?php
    }

	function display_reminder_schedule_2() {
    	?>
    		<input type="number" name="atom_aam_settings_email[reminder_schedule_2]" id="atom_reminder_schedule_2" value="<?php echo $this->get_option('reminder_schedule_2'); ?>" placeholder="<?php _e('Disabled', 'atom-appointment-management'); ?>" />
    	<?php
    }

	function display_reminder_subject() {
    	?>
    		<input type="text" name="atom_aam_settings_email[reminder_subject]" id="atom_reminder_subject" value="<?php echo $this->get_option('reminder_subject'); ?>" />
    	<?php
    }

    function display_reminder_text() {
    	?>
    		<textarea name="atom_aam_settings_email[reminder_text]" id="atom_reminder_text" rows="8"><?php echo $this->get_option('reminder_text'); ?></textarea>
			</td></tr><tr><td colspan="2" class="atom_description">
			<hr />
    	<?php
    }

	function display_analytics() {
    	echo "<p>" . __('This plugin supports Google Analytics and Facebook Pixel. If you have enabled Google Analytics (analytics.js or gtag.js), you will automatically receive events when an appointment is booked. The event is called <code>appointment_booked</code> and contains the appointment title as parameter. Facebook Pixel will send a standard <code>Lead</code> event.', 'atom-appointment-management') . "</p>";
    }
	
	function display_analytics_disable_admins() {
    	?>
    		<input type="checkbox" name="atom_aam_settings[analytics_disable_admins]" id="analytics_disable_admins" value="analytics_disable_admins" <?php if ($this->get_option('analytics_disable_admins')) echo 'checked'; ?> />
		</td></tr><tr><td colspan="2" class="atom_description"><p><?php _e('Enable this option to prevent your bookings as admin to show up as conversions in your analytics.', 'atom-appointment-management') ?></p>
    	<?php
    }

	function display_pro_info() {
		echo '<p class="aam-pro-info">' . sprintf(__('Get this feature and many more with <a href="%s" target="_blank">AAM PRO</a>.', 'atom-appointment-management'), ATOM_AAM_PLUGIN_INFOPAGE) . '</p>';
	}

    function clear_transients() {
		$this->aam->clear_transients();
	}

	function category_id_by_name($name) {
		return $this->aam->category_id_by_name($name);
	}

	function category_name_by_id($id) {
		return $this->aam->category_name_by_id($id);
	}

	function is_aam_admin_page() {
		return (isset($_GET['page']) && strpos($_GET['page'], 'atom-appointment-management') !== false);
	}

	function validate_time($input) {
		$input = trim($input);
		if (preg_match("/^(2[0-3]|[01][0-9]):([0-5][0-9])$/", $input)) {
			return $input;
		} else if (preg_match("/^(2[0-3]|[01][0-9])$/", $input)) {
			return $input . ':00';
		} else if (preg_match("/^([0-9]):([0-5][0-9])$/", $input)) {
			return '0' . $input;
		} else if (preg_match("/^([0-9])$/", $input)) {
			return '0' . $input . ':00';
		} else {
			return '00:00';
		}
 	}

	function validate_date($date, $format = 'Y-m-d\TH:i:s\Z') {
		return $this->aam->validate_date($date, $format);
	}

	function validate_input($input) {
		$output = array();

		foreach ($input as $key => $value) {
			if (!in_array($key, array('modal_infotext', 'workdays', 'categories', 'formfields', 'cancel_info_text')) && !is_array($value)) $value = strip_tags(stripslashes($value));

			switch ($key) {
				case 'first_possible_booking':
					$value = (!is_numeric($value) || $value < 0) ? 0 : $value;
					break;
				case 'weeks_in_advance':
				case 'bookings_per_slot_rulebased':
					$value = (!is_numeric($value) || $value < 1) ? 1 : $value;
					break;
				case 'notif_mail':
				case 'sender_mail':
					if ($value != '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
						$value = '';
						add_settings_error('atom_notif_mail', 'invalid-email', __('Invalid E-mail entered as notification address.', 'atom-appointment-management'));
					}
					break;
				case 'workdays':
					foreach ($value as &$entry) {
						$entry['start'] = $this->validate_time($entry['start']);
						$entry['end'] = $this->validate_time($entry['end']);
					}
					break;
				case 'event_duration':
					$value = $this->validate_time($value);
					$value = ($value == '00:00') ? '01:00' : $value;
					break;
				case 'event_gap':
					$value = $this->validate_time($value);
					break;
				case 'formfields':
					$sorted = array();
					$i = 0;
					foreach ($value as $field) {
						$sorted[$i++] = $field;
					}
					$value = $sorted;

					foreach ($value as $fieldskey => $field) {
						if (!isset($value[$fieldskey]['id'])) {
							$fieldname = 'field_' . sanitize_title($value[$fieldskey]['label']);
							$uniqueid = $delimiter = '';
							while (in_array($fieldname . $delimiter . $uniqueid, array_column($value, 'id'))) {
								if ($uniqueid == '') {
									$uniqueid = 1;
									$delimiter = '_';
								}  else {
									$uniqueid++;
								}
							}
							$value[$fieldskey]['id'] = $fieldname . $delimiter . $uniqueid;
						}
					}
					break;
				case 'categories':
					foreach ($value as $categorykey => $categoryvalue) {
						if (isset($categoryvalue['override_settings']) && !is_array($categoryvalue['override_settings'])) {
							$value[$categorykey]['override_settings'] = unserialize($categoryvalue['override_settings']);
						}
					}
					break;
			}

			if ($key != 'atom_aam_pro') {
				$output[$key] = $value;
			}
		}

		if (isset($_POST['option_page']) && $_POST['option_page'] == 'atom_booking_settings') {
			$output['privacy_mode'] = (array_key_exists('privacy_mode', $input));
			$output['exchange_activated'] = (array_key_exists('exchange_activated', $input));
			$output['formal_language'] = (array_key_exists('formal_language', $input));
			$output['show_full_events'] = (array_key_exists('show_full_events', $input));

			if (array_key_exists('enable_ics', $input)) {
				if ($_POST['atom_aam_ics_key'] == '') {
					$output['ics_key'] = md5('aam' . rand(10000, 99999) . get_bloginfo('url') . time());
				} else {
					$output['ics_key'] = sanitize_text_field($_POST['atom_aam_ics_key']);
				}
			} else {
				$output['ics_key'] = '';
			}

		} else if (isset($_POST['option_page']) && $_POST['option_page'] == 'atom_booking_email') {
			$output['automated_emails_enabled'] = (array_key_exists('automated_emails_enabled', $input));
			$output['received_enabled'] = (array_key_exists('received_enabled', $input));
		}

		if (isset($input['license_key']) && $input['license_key'] != $this->get_option('license_key') && isset($this->aam->update_checker)) {
			$this->aam->update_checker->checkForUpdates();
		}

		return $output;
	}

	private function display_pages_and_posts_dropdown($option_domain, $option_name) {
		$selected_option = $this->get_option($option_name);
		$selected_option = (is_array($selected_option)) ? $selected_option : array('select' => '', 'input' => '');
		?>
		<div class="atom_urlselector">
			<select name="<?php echo $option_domain; ?>[<?php echo $option_name; ?>][select]" id="atom_<?php echo $option_name; ?>">
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
			<input type="url" name="<?php echo $option_domain; ?>[<?php echo $option_name; ?>][input]" value="<?php echo $selected_option['input']; ?>" placeholder="https://www.example.com" />
		</div>

		<?php
	}

}
endif;
