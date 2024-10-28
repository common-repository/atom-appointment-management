<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

if (!class_exists('Atom_AAM_Exception_Table')):
class Atom_AAM_Exception_Table extends WP_List_Table {
	private $categories;

	public function __construct($categories) {
		parent::__construct();
		$this->categories = $categories;
		$this->prepare_items();
		$this->display();
	}

	function get_columns(){
		$columns = array(
			'start' => __('Start', 'atom-appointment-management'),
			'end'    => __('Duration', 'atom-appointment-management'),
			'category' => __('Category', 'atom-appointment-management'),
			'description' => __('Description', 'atom-appointment-management'),
			'actions'    => ''
		);
		return $columns;
	}

	function prepare_items() {
		global $wpdb;

		$data = $wpdb->get_results("SELECT id, excpt_begin, excpt_end, excpt_category, excpt_description FROM " . ATOM_AAM_TABLE_EXCEPTIONS . " WHERE excpt_end >= CURDATE() ORDER BY excpt_begin ASC;");
		$prep_items = array();

		for ($i = 0; $i < count($data); $i++) {
			$start = new Carbon\Carbon($data[$i]->excpt_begin, 'UTC');
			$end = new Carbon\Carbon($data[$i]->excpt_end, 'UTC');

			$start_output = date_i18n(get_option('date_format'), $start->timestamp);

			if ($start->isSameDay($end)) {

				if ($start->format('H:i') == '00:00' && $end->format('H:i') == '00:00') {
					$end_output = __('Full day', 'atom-appointment-management');
				} else {
					$end_output = date_i18n(get_option('time_format'), $start->timestamp) . ' - ' . date_i18n(get_option('time_format'), $end->timestamp);
				}

			} else {

				if ($start->format('H:i') == '00:00' && $end->format('H:i') == '00:00') {
					$end_output = date_i18n(get_option('date_format'), $end->timestamp);
				} else {
					$end_output = date_i18n(get_option('time_format'), $start->timestamp) . ' - ' .  date_i18n(get_option('date_format'), $end->timestamp) . ' ' . date_i18n(get_option('time_format'), $end->timestamp);
				}

			}


			$prep_items[$i]['id'] = $data[$i]->id;
			$prep_items[$i]['start'] = $start_output;
			$prep_items[$i]['end'] = $end_output;
			$prep_items[$i]['category'] = $data[$i]->excpt_category;
			$prep_items[$i]['description'] = $data[$i]->excpt_description;
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

		switch($column_name) {
			case 'start':
			case 'end':
				return $item[$column_name];
			case 'category':
				$category = $item[$column_name];
				if (isset($this->categories[$category]['name'])) {
					$category_string = '<span class="color-indicator" style="background-color:' . $this->categories[$category]['color'] .'"></span>';
					$category_string .= $this->categories[$category]['name'];
				} else {
					$category_string = '-';
				}
				return $category_string;
			case 'description':
				return ($item['description'] == 'aam_frontend_exception') ? __('Removed from calendar', 'atom-appointment-management') : $item['description'];
			case 'actions':
				return "<a href='admin.php?page=" . ATOM_AAM_PLUGIN_SLUG . "-rule-based&action=del_excpt&id=" . $item['id'] . "&_wpnonce=".$nonce_action."' class='button atom-delete'>" . __('Delete', 'atom-appointment-management') .  "</a>";
			default:
				return "xxx";
		}
	}

}
endif;
