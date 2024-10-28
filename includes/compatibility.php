<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

global $wpdb;

// Update exception table
if (get_option('atom_aam_version') < '2.0.0') {

	$exceptions = $wpdb->get_results("SELECT * FROM " . ATOM_AAM_TABLE_EXCEPTIONS, "ARRAY_A");
	foreach ($exceptions as $exception) {
		$excpt_begin = $exception['excpt_day'] . ' ' . explode(' ', $exception['excpt_begin'])[1];
		$excpt_end = $exception['excpt_day'] . ' ' . explode(' ', $exception['excpt_end'])[1];

		$wpdb->update(
			ATOM_AAM_TABLE_EXCEPTIONS, 		// table name
			array( 				// data
				'excpt_begin'	=> $excpt_begin,
				'excpt_end'		=> $excpt_end
			),
			array( 				// where clause
				'id'	=> $exception['id']
			)
		);

	}

}
