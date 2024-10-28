<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

if (!class_exists('Atom_AAM_Entry_Table')):
class Atom_AAM_Entry_Table extends WP_List_Table {
	private $archive = false;
	private $formfields;
	private $categories;

	public function __construct($archive, $formfields, $categories) {
		parent::__construct();
		$this->archive = $archive;
		$this->formfields = $formfields;
		$this->categories = $categories;
		$this->prepare_items();
		$this->display();
	}

	function get_columns(){
		$columns = array(
			'date' 	   => __('Date', 'atom-appointment-management'),
			'time'     => __('Time', 'atom-appointment-management'),
			'category' => __('Category', 'atom-appointment-management'),
			'title'    => __('Title', 'atom-appointment-management')
		);
		foreach ($this->formfields as $field) {
			$field_id = $field['id'];
			$columns[$field_id] = $field['label'];
		}
		$columns['actions'] = '';

		return $columns;
	}

	function prepare_items() {
		global $wpdb;
		if ($this->archive) {
			$data = $wpdb->get_results("SELECT id, start, end, title, category, fields, confirmed, booking_date FROM " . ATOM_AAM_TABLE_ENTRIES . " WHERE confirmed = -1 OR start < CURDATE() ORDER BY start DESC;");
		} else {
			$data = $wpdb->get_results("SELECT id, start, end, title, category, fields, confirmed, booking_date FROM " . ATOM_AAM_TABLE_ENTRIES . " WHERE confirmed != -1 AND start >= CURDATE() ORDER BY start ASC;");
		}
		$prep_items = array();

		for ($i = 0; $i < count($data); $i++) {
			$fields = unserialize($data[$i]->fields);

			$start = new Carbon\Carbon($data[$i]->start, 'UTC');
			$end = new Carbon\Carbon($data[$i]->end, 'UTC');
			$date = date_i18n(get_option('date_format'), $start->timestamp);
			$time = date_i18n(get_option('time_format'), $start->timestamp) . " - " . date_i18n(get_option('time_format'), $end->timestamp);

			$booking_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($data[$i]->booking_date));

			$prep_items[$i]['id'] = $data[$i]->id;
			$prep_items[$i]['confirmed'] = $data[$i]->confirmed;
			$prep_items[$i]['date'] = $date;
			$prep_items[$i]['category'] = $data[$i]->category;
			$prep_items[$i]['title'] = $data[$i]->title;
			$prep_items[$i]['date_raw'] = $data[$i]->start;
			$prep_items[$i]['booking_date'] = $booking_date;
			$prep_items[$i]['time'] = $time;

			foreach ($this->formfields as $field) {
				$field_id = $field['id'];
				$prep_items[$i][$field_id] = (isset($fields[$field_id])) ? $fields[$field_id] : '-';
			}

			$prep_items[$i]['actions'] = "";
		}

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->items = $prep_items;
	}

	function column_default( $item, $column_name ) {
		$nonce_action = wp_create_nonce('action');

		if (substr($column_name, 0, 6) == 'field_' && strlen($item[$column_name]) > 40) {
			return mb_substr($item[$column_name], 0, 30) . '... <a href="#" class="atom-show" data-show="' . nl2br($item[$column_name]) . '">' . __('Show more', 'atom-appointment-management') . '</a>';
		}

		switch($column_name) {
			case 'actions':
				$booking_date = '<span class="booking_date">' . __('Booked on', 'atom-appointment-management') . ' ' . $item['booking_date'] . '</span>';
				if ($this->archive) {
					$start = new Carbon\Carbon($item['date_raw'], 'UTC');
					if ($item['confirmed'] == -1 && $start->endOfDay()->gt(Carbon\Carbon::now('UTC'))) {
						$button = "<button class='button atom-delete' disabled>" . __('Cancelled', 'atom-appointment-management') . "</button>
						<a href='admin.php?page=" . ATOM_AAM_PLUGIN_SLUG . "&action=rec&id=".$item['id']."&_wpnonce=".$nonce_action."' class='button'>" . __('Restore', 'atom-appointment-management') . "</a>";
					} else {
						switch ($item['confirmed']) {
							case -1: $button = __('Cancelled', 'atom-appointment-management'); break;
							case 0: $button = __('Not confirmed', 'atom-appointment-management'); break;
							case 1: $button = __('Confirmed', 'atom-appointment-management'); break;
						}
						$button .= "<a href='admin.php?page=" . ATOM_AAM_PLUGIN_SLUG . "&action=del_permanent&id=".$item['id']."&_wpnonce=".$nonce_action."' class='delete'>" . __('Delete', 'atom-appointment-management') . "</a>";
					}
					return $button . $booking_date;
				} else {
					$deletebutton = "<a href='admin.php?page=" . ATOM_AAM_PLUGIN_SLUG . "&action=del&id=".$item['id']."&_wpnonce=".$nonce_action."' class='button atom-delete' data-id='".$item['id']."' data-email='".$item['field_email']."'>" . _x('Cancel', 'Cancel Appointment', 'atom-appointment-management') . "</a>";
					if ($item['confirmed']) {
						$accbutton = "<button class='button button-primary atom-accepted' disabled>" . __('Confirmed', 'atom-appointment-management') . "</button>";
					} else {
						$accbutton = "<a href='admin.php?page=" . ATOM_AAM_PLUGIN_SLUG . "&action=acc&id=".$item['id']."&_wpnonce=".$nonce_action."' class='button button-primary atom-accept' data-id='".$item['id']."' data-email='".$item['field_email']."'>" . __('Confirm', 'atom-appointment-management') . "</a>";
					}
					return $deletebutton . $accbutton . $booking_date;
				}
			case 'category':
				$category = $item['category'];
				if (isset($this->categories[$category]['name'])) {
					$category_string = '<span class="color-indicator" style="background-color:' . $this->categories[$category]['color'] .'"></span>';
					$category_string .= $this->categories[$category]['name'];
				} else {
					$category_string = '-';
				}
				return $category_string;
			default:
				return $item[$column_name];
		}
	}

	function get_table_classes() {
		return array( 'widefat', 'fixed', 'striped', 'aam-entries');
	}

}
endif;
