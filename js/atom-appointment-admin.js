"use strict";

/*
 * ATOM Appointment Management Plugin
 *
 * ©2018 Alexander Altendorfer / atomproductions
 * http://www.atomproductions.at
 */


(function () {

	//----------------//
	//   VARIABLES
	//----------------//

	var DATE_FORMAT 			= aamlocalizevars.dateformat,
		TEXT_DELETE_FORMFIELD	= aamlocalizevars.text_delete_formfield,
		TEXT_DELETE				= aamlocalizevars.text_delete,
		TEXT_COPIED				= aamlocalizevars.text_copied,
		TEXT_CANCELLED			= aamlocalizevars.text_cancelled,
		TEXT_CONFIRMED			= aamlocalizevars.text_confirmed,
		API_AJAXURL 			= aamlocalizevars.ajaxurl;

	//----------------//
	//    FUNCTIONS
	//----------------//

	function initCategoryTable() {
		var table = jQuery('#atom_aam_categories');
		var atom_aam_category_prototype = jQuery('.atom_aam_category');
		atom_aam_category_prototype.detach();
		var categoriesIndex = jQuery('#last_category_index').attr('data-index');
		initCategoryRow();

		jQuery('#atom_aam_add_category').click(function(e) {
			e.preventDefault();

			categoriesIndex++;
			var newRow = atom_aam_category_prototype.clone();
			newRow.find('input').each(function() {
				if (typeof jQuery(this).attr('name') !== 'undefined') {
					jQuery(this).attr('name', jQuery(this).attr('name').replace('{i}', categoriesIndex));
				}
			});

			initCategoryRow(newRow);
			table.append(newRow);

			return false;
		});
	}

	function initCategoryRow(obj = false) {
		if (!obj) obj = jQuery('#atom_aam_categories');
		obj.find('.atom_category_colorpicker').wpColorPicker();
		obj.find('button.remove-category').click(function(e) {
			e.preventDefault();
			if (confirm(TEXT_DELETE)) {
				jQuery(this).closest('tr').detach();
			}
			return false;
		});
	}

	function initFormFieldsTable() {
		var table = jQuery('#atom_aam_formfields');
		var atom_aam_formfield_prototype = jQuery('.atom_aam_formfields');
		atom_aam_formfield_prototype.detach();
		var formfieldsIndex = jQuery('#last_formfield_index').attr('data-index');
		initFormfieldRow();

		jQuery('#atom_aam_add_formfield').click(function(e) {
			e.preventDefault();

			formfieldsIndex++;
			var newRow = atom_aam_formfield_prototype.clone();
			newRow.find('input, select').each(function() {
				if (typeof jQuery(this).attr('name') !== 'undefined') {
					jQuery(this).attr('name', jQuery(this).attr('name').replace('{i}', formfieldsIndex));
				}
			});

			initFormfieldRow(newRow);
			table.append(newRow);

			return false;
		});

		var rowsWithSelectType = table.find('.type-input option[value="select"]:selected').parent().parent().parent();
		rowsWithSelectType.find('input[type=checkbox]').hide();
		rowsWithSelectType.find('.selectvalues-input').show();

		table.sortable();
	}

	function initFormfieldRow(obj = false) {
		if (!obj) obj = jQuery('#atom_aam_formfields');
		obj.find('button.remove-formfield').click(function(e) {
			e.preventDefault();
			if (confirm(TEXT_DELETE_FORMFIELD)) {
				jQuery(this).closest('tr').detach();
			}
			return false;
		});

		obj.find('.type-input').change(function(e) {
			var objParent = jQuery(this).parent().parent();
			if (jQuery(this).val() == 'select') {
				objParent.find('input[type=checkbox]').hide();
				objParent.find('.selectvalues-input').show();
			} else {
				objParent.find('input[type=checkbox]').show();
				objParent.find('.selectvalues-input').hide();
			}
		});
	}

	function editIndividualSlot() {
		var row = jQuery(this).closest('tr');
		var restore = row.clone();

		var slotId = row.find('button.edit-slot').attr('data-id');

		jQuery(this).hide();
		row.find('input.save-slot').show().before('<input type="hidden" name="atom_update_slot_id" value="' + slotId + '">');
		row.find('button.cancel-edit-slot').show().click(function() {
			restoreSlotRow(row, restore);
		});
		row.siblings().find('button.edit-slot').on('click.atomslot'+row.find('button.edit-slot').attr('data-id'), function() {
			restoreSlotRow(row, restore);
		});

		row.find('td.date').html('<input type="text" name="atom_slot_day_readable" data-target="atom_slot_update_day" class="atom_datepicker" value="' + row.find('td.date span.value').html() + '" /><input type="text" name="atom_slot_day" class="atom_datepicker_value" id="atom_slot_update_day" value="' + row.find('td.date span.data').attr('data-date') + '" />');

		var start = row.find('td.time span').attr('data-start');
		var end = row.find('td.time span').attr('data-end');
		row.find('td.time').html('<input name="atom_slot_time_start" type="time" value="' + start + '" style="width:40%;">')
						   .append(' - <input name="atom_slot_time_end" type="time" value="' + end + '" style="width:40%;">');

		row.find('td.title').html('<input name="atom_slot_title" type="text" value="' + row.find('td.title').html() + '">');

		var moreinfo = row.find('td.moreinfo span').attr('data-moreinfo');
		if (moreinfo == "") moreinfo = '-';
		row.find('td.moreinfo').html(jQuery('table.form-table select[name=atom_slot_moreinfo]').clone())
							   .find('option[value='+moreinfo+']').attr('selected','selected');

		var category = row.find('td.category span').attr('data-category');
		if (category == "") category = 'none';
		row.find('td.category').html(jQuery('table.form-table select[name=atom_slot_category]').clone())
							   .find('option[value='+category+']').attr('selected','selected');

		row.find('td.bookings_per_slot').html('<input name="atom_slot_bookings_per_slot" type="number" min="1" value="' + row.find('td.bookings_per_slot').html() + '">');

		var recurring = row.find('td.recurring span').attr('data-recurring');
		var recurringUntilValue = row.find('td.recurring span').attr('data-recurringuntilvalue');
		var recurringUntilData = row.find('td.recurring span').attr('data-recurringuntildata');
		row.find('td.recurring').html('<input type="checkbox" name="atom_slot_recurring" id="atom_slot_recurring">')
							    .append(jQuery('table.form-table select[name=atom_slot_repeat]').clone())
								.append('<br>').append(jQuery('#atom_until_string').html())
								.append('<input type="text" name="atom_slot_repeat_until_readable" data-target="atom_slot_update_repeat_until" class="atom_datepicker" value="' + recurringUntilValue + '" /><input type="text" name="atom_slot_repeat_until" class="atom_datepicker_value" id="atom_slot_update_repeat_until" value="' + recurringUntilData + '" />')
							    .find('option[value='+recurring+']').attr('selected','selected');
		if (recurring != 'none') row.find('td.recurring input[name=atom_slot_recurring]').attr('checked', 'checked');

		row.find('.atom_datepicker').each(function(i,v) {
			var el = jQuery(v);
			el.on('blur', function() {
				if (el.val() == "") {
					jQuery('#' + el.attr('data-target')).val('');
				}
			});
			el.datepicker({
				altField: '#' + el.attr('data-target'),
				altFormat: 'yy-mm-dd'
			});
		});
	}

	function restoreSlotRow(row, restore) {
		row.siblings().find('button.edit-slot').off('click.atomslot'+row.find('button.edit-slot').attr('data-id'));
		row.replaceWith(restore);
		restore.find('button.edit-slot').click(editIndividualSlot);
	}

	function send_user_email(type, button) {
		var id = button.attr('data-id'),
			email = button.attr('data-email'),
			href = button.attr('href'),
			subject,
			body;

		jQuery.ajax({
			type: "POST",
			url: API_AJAXURL,
			data: {
				action: 'atom_appointment_send_user_mail',
				id: id,
				type: type
			}
		}).fail(function (jqXHR) {
			console.error("AJAX request failed");
		}).done(function(data) {

			data = JSON.parse(data);
			if (!data.automated_emails_enabled) {
				window.location = "mailto:" + email + "?subject=" + data.subject + "&body=" + data.text;
			}

			jQuery.ajax({
				type: "GET",
				url: href,
			}).fail(function (jqXHR) {
				console.error("AJAX request failed");
			}).done(function(data) {
				if (type == 'confirm') {
					button.addClass('atom-accepted').attr('disabled', 'disabled').html(TEXT_CONFIRMED);
				} else {
					button.attr('disabled', 'disabled').html(TEXT_CANCELLED);
				}
			});

		});

	}

	function toggleExternalUrlField() {
		if (jQuery(this).val() == 'external') {
			jQuery(this).parent().find('input').fadeIn();
		} else {
			jQuery(this).parent().find('input').fadeOut();
		}
	}

	//----------------//
	(function init() {
	//----------------//

		jQuery(document).ready(function () {

			jQuery('.atom_check_workdays').change(function() {
				var el = jQuery(this);
				var i = el.attr("data-target");
				var inputs = el.parent().parent().find('input[type=time]');

				if (el.is(':checked')) {
					inputs.prop('readonly', false);
					inputs[0].value = '09:00';
					inputs[1].value = '18:00';
				} else {
					inputs.prop('readonly', true);
					inputs.val('00:00');
				}
			});

			jQuery('.atom-show').click(function(e) {
				e.preventDefault();
				alert(jQuery(this).attr('data-show'));
				return false;
			});

			jQuery('.atom_colorpicker').wpColorPicker();

			jQuery('.atom_recurring').hide();
			jQuery('#atom_slot_recurring').click(function() {
				jQuery('.atom_recurring').toggle();
			});

			if (jQuery('input[name="override_rulebased"]')) {
				if (!jQuery('input[name="override_rulebased"]').attr("checked")) {
					jQuery('.override-rulebased').hide();
				}
				jQuery('input[name="override_rulebased"]').click(function() {
					jQuery('.override-rulebased').slideToggle();
				});
			}

			if (jQuery('input[name="exchange_activated"]')) {
				if (!jQuery('input[name="exchange_activated"]').attr("checked")) {
					jQuery('.aam-exchange-settings').hide();
				}
				jQuery('input[name="exchange_activated"]').click(function() {
					jQuery('.aam-exchange-settings').slideToggle();
				});
			}

			// The "Upload" button
			jQuery('.upload_image_button').click(function() {
				var send_attachment_bkp = wp.media.editor.send.attachment;
				var button = jQuery(this);
				wp.media.editor.send.attachment = function(props, attachment) {
					jQuery(button).parent().prev()
						.attr('src', attachment.url)
						.fadeIn();
					jQuery(button).prev().val(attachment.id);
					wp.media.editor.send.attachment = send_attachment_bkp;
				}
				wp.media.editor.open(button);
				return false;
			});

			// The "Remove" button (remove the value from input type='hidden')
			jQuery('.remove_image_button').click(function() {
				var answer = confirm(TEXT_DELETE);
				if (answer == true) {
					jQuery(this).parent().prev()
						.attr('src', '')
						.fadeOut();
					jQuery(this).prev().prev().val('');
				}
				return false;
			});

			jQuery('.atom_datepicker').each(function(i,v) {
				var el = jQuery(v);
				el.datepicker({
					altField: '#' + el.attr('data-target'),
					altFormat: 'yy-mm-dd'
				});
			});

			jQuery('.atom_datepicker[name=atom_exception_begin_readable]').change(function() {
				if (!jQuery('#atom_exception_end').val()) {
					jQuery('#atom_exception_end').val(
						jQuery('#atom_exception_begin').val()
					);
					jQuery('.atom_datepicker[name=atom_exception_end_readable]').val(
						jQuery('.atom_datepicker[name=atom_exception_begin_readable]').val()
					);
				}
			});

			jQuery('.excpt_hide').hide();
			jQuery('#atom_exception_fullday').click(function() {
				jQuery('.excpt_hide').toggle();
			});

			if (jQuery('#atom_aam_categories').length) {
				initCategoryTable();
			}

			if (jQuery('#atom_aam_formfields').length) {
				initFormFieldsTable();
			}

			jQuery('.atom button.edit-slot').click(editIndividualSlot);

			jQuery('.atom_shortcode_copy').focus(function() {
				var el = jQuery(this),
					url = jQuery(this).val(),
					succeed = false;
				el[0].focus();
				el[0].setSelectionRange(0, el[0].value.length);
				try {
					succeed = document.execCommand("copy");
				} catch(e) {
					succeed = false;
				}
				if (succeed) {
					el[0].blur();
					el.val(TEXT_COPIED);
					setTimeout(function () {
						el.val(url);
					}, 2000);
				}
			});

			jQuery('.aam-entries .atom-accept').click(function(e) {
				e.preventDefault();
				send_user_email('confirm', jQuery(this));
				return false;
			});
			jQuery('.aam-entries .atom-delete').click(function(e) {
				e.preventDefault();
				if (confirm(TEXT_DELETE)) {
					send_user_email('cancel', jQuery(this));
				}
				return false;
			});

			jQuery('.atom_urlselector select').each(toggleExternalUrlField);
			jQuery('.atom_urlselector select').change(toggleExternalUrlField);

		});

	})();

})();
