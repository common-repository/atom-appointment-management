"use strict";

/*
 * ATOM Appointment Management Plugin
 *
 * ©2018 Alexander Altendorfer / atomproductions
 * http://www.atomproductions.at
 */


(function () {

	//////////////////////////////////////////
	//   VARIABLES
	//////////////////////////////////////////

	var PLUGIN_PATH 	= aamlocalizevars.pluginpath,
		API_AJAXURL 	= aamlocalizevars.ajaxurl,
		LANGUAGE		= aamlocalizevars.language,
		MINTIME			= aamlocalizevars.min_time,
		MAXTIME			= aamlocalizevars.max_time,
		WEEKLIMIT		= aamlocalizevars.week_limit,
		DEBUG			= aamlocalizevars.debug,
		ANALYTICS_DISABLE_ADMINS	= aamlocalizevars.analytics_disable_admins,
		STRINGS			= aamlocalizevars.strings,

		container,

		isAdmin,
		display,
		eventType,
		category,
		limit,
		daysWithEvents = [],

		calendar,
		fullCalendar,
		loadingIndicator,
		loadingProblem,
		sendString,
		modal,
		confirmation,
		form,
		hdate,
		htime,
		dateFormat,
		firstLoad,
		timeout;

	//////////////////////////////////////////
	//    FUNCTIONS
	//////////////////////////////////////////

	//////////////////////////////////////////
	function calResizeHandler(view) {
	//////////////////////////////////////////

		if (calendar.width() < 576) {
			firstLoad = true;
			fullCalendar.changeView('timeGridDay');
		} else {
			fullCalendar.changeView('timeGridWeek');
		}

	}

	//////////////////////////////////////////
	function eventClickHandler(info) {
	//////////////////////////////////////////

		var calEvent = info.event;

		if (calEvent !== undefined && calEvent.classNames.includes('aam-full')) {
			return false;
		}

		var start = moment.utc(calEvent.start),
			end = moment.utc(calEvent.end);

		hdate = start.format("dddd, D.M.YYYY");
		htime = start.format("LT") + " - " + moment(end).format("LT");

		modal.find('.aam-event-title').html(calEvent.title);
		modal.find('.aam-event-date').html(hdate);
		modal.find('.aam-event-time').html(htime);

		if (typeof(calEvent.extendedProps.moreinfo) !== 'undefined' && calEvent.extendedProps.moreinfo != "-1" && calEvent.extendedProps.moreinfo != ""  && calEvent.extendedProps.moreinfo != "#") {
			modal.find('.aam-event-moreinfo').show().attr('href', calEvent.extendedProps.moreinfo);
		} else {
			modal.find('.aam-event-moreinfo').hide();
		}

		jQuery('#atom-aam-form .atom-aam-input').blur(function() {
			validate(jQuery(this));
		});

		jQuery('#atom-aam-form').unbind('submit').submit(function(e) {
			e.preventDefault();
			formSendHandler(calEvent);
			return false;
		});

		jQuery('.atom-aam-admin').hide();

		if (isAdmin) {
			if (calEvent.extendedProps.type == 'rule') {
				jQuery('#atom-admin-rule').show();

				modal.find('.atom-aam-single-exception').click(function() {
					var $this = jQuery(this);
					jQuery.ajax({
						type: "POST",
						url: API_AJAXURL,
						data: {
							action: 'atom_appointment_add_exception',
							begin: start.format('YYYY-MM-DD HH:mm'),
							end: end.format('YYYY-MM-DD HH:mm'),
							category: calEvent.extendedProps.category,
							type: $this.attr('data-action')
						},
						success: function (data) {
							closeModal();
						}
					}).fail(function (jqXHR) {
						console.error("AJAX request failed");
					});
				});
				modal.find('.atom-aam-day-exception').click(function() {
					var $this = jQuery(this);
					jQuery.ajax({
						type: "POST",
						url: API_AJAXURL,
						data: {
							action: 'atom_appointment_add_exception',
							begin: start.hour(0).minute(0).format('YYYY-MM-DD HH:mm'),
							end: start.hour(0).minute(0).format('YYYY-MM-DD HH:mm'),
							type: $this.attr('data-action')
						},
						success: function (data) {
							closeModal();
						}
					}).fail(function (jqXHR) {
						console.error("AJAX request failed");
					});
				});
				var excbuttonCategory = modal.find('.atom-aam-category-exception');
				if (calEvent.extendedProps.category != -1) {
					excbuttonCategory.show();
					excbuttonCategory.click(function() {
						var $this = jQuery(this);
						jQuery.ajax({
							type: "POST",
							url: API_AJAXURL,
							data: {
								action: 'atom_appointment_add_exception',
								begin: start.hour(0).minute(0).format('YYYY-MM-DD HH:mm'),
								end: start.hour(0).minute(0).format('YYYY-MM-DD HH:mm'),
								category: calEvent.extendedProps.category,
								type: $this.attr('data-action')
							},
							success: function (data) {
								closeModal();
							}
						}).fail(function (jqXHR) {
							console.error("AJAX request failed");
						});
					});
				} else {
					excbuttonCategory.hide();
				}
			} else if (calEvent.extendedProps.type == 'single') {
				jQuery('#atom-admin-single').show();

				var excbutton = modal.find('.atom-aam-remove-slot');
				excbutton.click(function() {
					if (confirm(jQuery(this).html() + '?')) {
						jQuery.ajax({
							type: "POST",
							url: API_AJAXURL,
							data: {
								action: 'atom_appointment_remove_slot',
								id: calEvent.extendedProps.db_id,
								type: excbutton.attr('data-action')
							},
							success: function (data) {
								closeModal();
							}
						}).fail(function (jqXHR) {
							console.error("AJAX request failed");
						});
					}
				});

			} else if (calEvent.extendedProps.type == 'recurring') {
				jQuery('#atom-admin-recurring').show();

				var excbutton = modal.find('.atom-aam-remove-slot');
				excbutton.click(function() {
					if (confirm(jQuery(this).html() + '?')) {
						jQuery.ajax({
							type: "POST",
							url: API_AJAXURL,
							data: {
								action: 'atom_appointment_remove_slot',
								id: calEvent.extendedProps.db_id,
								type: excbutton.attr('data-action')
							},
							success: function (data) {
								closeModal();
							}
						}).fail(function (jqXHR) {
							console.error("AJAX request failed");
						});
					}
				});
			}

		}

		modal.fadeIn();
		modal.addClass('open');
		if (display == 'calendar') container.css('min-height', jQuery('.aam-modal-content').outerHeight() + jQuery('.aam-modal-toolbar').outerHeight());
	}

	function closeModal() {
		modal.removeClass('open');
		modal.fadeOut();
		if (isAdmin) {
			modal.find('.atom-aam-category-exception').unbind('click');
			modal.find('.atom-aam-day-exception').unbind('click');
			modal.find('.atom-aam-single-exception').unbind('click');
			modal.find('.atom-aam-remove-slot').unbind('click');
		}
		jQuery('#atom-aam-form-send').html(sendString).prop('disabled', false);

		setTimeout(function() {
			confirmation.hide();
			jQuery('.aam-modal-content').show();
		}, 300);

		if (display == 'calendar') {
			fullCalendar.refetchEvents();
			container.css('min-height', 0);
		} else if (display == 'list') {
			rerenderListView();
		}
	}

	//////////////////////////////////////////
	function formSendHandler(event) {
	//////////////////////////////////////////

		var	inputFields = jQuery('#atom-aam-form .atom-aam-input'),
			values = [],
			valid = true;

		jQuery('#atom-aam-form-send').prop('disabled', true);

		inputFields.each(function(idx, el) {
			el = jQuery(el);
			if (validate(el)) {
				values.push({key:getFieldKey(el), value:el.val()});
			} else {
				valid = false;
			}

		});

		if (!validateDsgvo(jQuery('#atom-aam-consent'))) valid = false;

		if (valid) {
			sendFormData(values, event);
		} else {
			jQuery('#atom-aam-form-send').prop('disabled', false);
			jQuery('.atom-invalid-input').addClass('wiggle');
			setTimeout(function() {
				jQuery('.atom-invalid-input').removeClass('wiggle');
			}, 1000);
		}

	}

	//////////////////////////////////////////
	function validate(field) {
	//////////////////////////////////////////

		field = jQuery(field);

		var value = field.val(),
			type = field.attr('type'),
			valid = true,
			required = (field.attr('data-required') == 'true');

		switch (type) {
			case 'text':
			case 'textarea':
				valid = (!required || value.trim().length > 0)
				break;
			case 'email':
				var regex = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,})+$/;
				valid = (value.match(regex) || !required && value.trim().length == 0);
				break;
			case 'tel':
				var regex = /^[\s()+-]*([0-9][\s()+-]*){6,20}$/;
				valid = (value.match(regex) || !required && value.trim().length == 0);
				break;
			case 'select':
				valid = (value != null);
				break;
		}

		if (valid) {
			field.removeClass("atom-invalid-input");
		} else {
			field.val("");
			field.addClass("atom-invalid-input");
		}
		return valid;
	}

	//////////////////////////////////////////
	function validateDsgvo() {
	//////////////////////////////////////////

		var consent = jQuery('#atom-aam-consent');

		if (consent.length && !consent[0].checked) {
			consent.addClass("atom-invalid-input");
			return false;
		} else {
			consent.removeClass("atom-invalid-input");
			return true;
		}

	}

	//////////////////////////////////////////
	function getFieldKey(el) {
	//////////////////////////////////////////
		return el.attr('id').substr(15);
	}

	//////////////////////////////////////////
	function confirmBooking(values, event, data) {
	//////////////////////////////////////////

		if (!(isAdmin && ANALYTICS_DISABLE_ADMINS)) {
			if (DEBUG) {console.log('Send conversions');}
			// Google Analytics
			if (typeof ga !== 'undefined') {
				if (DEBUG) {console.log('Send ga event');}
				ga('send', 'event', 'appointment_booked', 'appointment_booked', event.title);
			}
			if (typeof gtag !== 'undefined') {
				if (DEBUG) {console.log('Send gtag event');}
				gtag('event', 'appointment_booked', {
					'event_category': 'appointment_booked',
					'event_label': event.title
				});
			}
			// FB Pixel
			if (typeof fbq !== 'undefined') {
				 fbq('track', 'Lead');
			}
		}

		if (data.redirect) {
			setTimeout(() => {
				window.location.href = data.redirect;
			}, 100);
			return;
		}

		modal.find('.atom-email').html(jQuery('#atom-aam-input-field_email').val());
		confirmation.slideDown();
		jQuery('.aam-modal-content').slideUp();
		setTimeout(function() {
			if (display == 'calendar') container.css('min-height', jQuery('.atom-confirmation-container').outerHeight() + jQuery('.aam-modal-toolbar').outerHeight());
		}, 300);

		if (jQuery(window).scrollTop() > container.offset().top) {
			jQuery('html, body').animate({
				scrollTop: container.offset().top
			}, 300);
		}

	}

	//////////////////////////////////////////
	function goToFirstEvent() {
	//////////////////////////////////////////

		var start = moment();
		var end = moment().add(WEEKLIMIT, 'week').endOf('week');

		jQuery.ajax({
			type: "POST",
			url: API_AJAXURL,
			data: {
				action: 'atom_appointment_fetchevents',
				from: start.format('YYYY-MM-DD'),
				to: end.format('YYYY-MM-DD'),
				type: eventType,
				category: category,
				show_full_events: 0
			},
			success: function (data) {
				data = JSON.parse(data);
				if (data.error) {
					console.error(data.error);
				} else if (typeof data.events[0] !== 'undefined') {

					daysWithEvents = new Set();
					data.events.forEach(function(item, i) {
						daysWithEvents.add(item.start.split('T')[0]);
					});
					daysWithEvents = Array.from(daysWithEvents);

					fullCalendar.gotoDate(data.events[0].start);

				}
			}.bind(daysWithEvents)
		}).fail(function (jqXHR) {
			console.error("AJAX request failed");
		});

	}

	//////////////////////////////////////////
	function fetchEvents(info, successCallback, failureCallback) {
	//////////////////////////////////////////

		var start = FullCalendarMoment.toMoment(info.start, fullCalendar),
			end = FullCalendarMoment.toMoment(info.end, fullCalendar);

		jQuery.ajax({
			type: "POST",
			url: API_AJAXURL,
			data: {
				action: 'atom_appointment_fetchevents',
				from: start.format('YYYY-MM-DD'),
				to: end.format('YYYY-MM-DD'),
				type: eventType,
				category: category
			},
			success: function (data) {
				data = JSON.parse(data);
				if (DEBUG) console.log(data);
				if (data.error) {
					console.error(data.error);
					failureCallback(data.error);
					setTimeout(function () {
						fetchEvents(info, callback);
					}, 1000);
				} else {
					successCallback(data.events);
				}
			}
		}).fail(function (jqXHR) {
			console.error("AJAX request failed");
		});

		if (moment().isBetween(start, end)) {
			jQuery('.fc-prev-button').attr('disabled', 'disabled');
		} else {
			jQuery('.fc-prev-button').removeAttr('disabled');
		}

		if (end.isAfter(moment().add('6', 'week'))) {
			jQuery('.fc-next-button').attr('disabled', 'disabled');
		} else {
			jQuery('.fc-next-button').removeAttr('disabled');
		}

	}

	//////////////////////////////////////////
	function dateRangeChanged(dateInfo) {
	//////////////////////////////////////////

		var activeDay = moment.utc(dateInfo.start),
			weekStart = (activeDay.day() == 0) ? activeDay.clone().day(-6) : activeDay.clone().day(1), // set to monday
			buttonsContainer = jQuery('.aam-day-nav-container'),
			today = moment.utc();

		buttonsContainer.empty();

		for (var i = 0; i < 7; i++) {

			var classes = (weekStart.isSame(activeDay, 'day')) ? 'current' : '',
				hasEvents = (daysWithEvents.length) ? daysWithEvents.includes(weekStart.format('YYYY-MM-DD')) : true,
				disabled = weekStart.isBefore(today) || !hasEvents;

			jQuery('<button class="fc-button fc-button-secondary ' + classes + '" ' + ((disabled) ? 'disabled="disabled"' : '') + 'type="button" data-date="' + weekStart.format() + '">' + weekStart.date() + '</button>').click(function() {
				fullCalendar.gotoDate(jQuery(this).attr('data-date'));
			}).appendTo(buttonsContainer);
			weekStart.add(1, 'day');

		}

	}

	//////////////////////////////////////////
	function dayHeaderContent(args, createElement) {
	//////////////////////////////////////////

		var domNodes = [],
			date = moment.utc(args.date);

		domNodes.push(jQuery('<span class="dow">' + date.format('dd') + '</span>')[0]);
		domNodes.push(jQuery('<span class="date">' + date.format('DD.MM.') + '</span>')[0]);

		if (args.view.type == 'timeGridDay' && !date.isSame(moment.utc(), 'day')) {
			domNodes.push(jQuery('<button class="fc-button">' + STRINGS.today + '</button>').click(function() {
				fullCalendar.today();
			})[0]);
		}

		return {domNodes: domNodes}

	}

	//////////////////////////////////////////
	function indicateLoading(isLoading, view) {
	//////////////////////////////////////////

		if (isLoading) {
			loadingIndicator.show();

			timeout = setTimeout(function() {
				loadingProblem.fadeIn();
			}, 10000);

		} else {
			loadingIndicator.fadeOut();
			clearTimeout(timeout);
			loadingProblem.fadeOut();
		}

	}

	//////////////////////////////////////////
	function sendFormData(values, event) {
	//////////////////////////////////////////

		var time1 = setTimeout(function(){
			jQuery('#atom-aam-form-send').html(STRINGS.loading);
		}, 500);
		var time2 = setTimeout(function(){
			jQuery('#atom-aam-form-send').prop('disabled', false).html(STRINGS.error_generic);
		}, 20000);

		if (event.extendedProps.db_id) {
			var slot_id = event.extendedProps.db_id;
		} else {
			var slot_id = -1;
		}

		jQuery.ajax({
			type: "POST",
			url: API_AJAXURL,
			data: {
				action: 'atom_appointment_formsubmit',
				startdate: moment.utc(event.start).format('YYYY-MM-DD HH:mm'),
				enddate: moment.utc(event.end).format('YYYY-MM-DD HH:mm'),
				values: values,
				slot_id: slot_id,
				title: event.title,
				category: event.extendedProps.category
			},
			success: function(data) {
				data = JSON.parse(data);
				clearTimeout(time1);
				clearTimeout(time2);
				if (data.success) {
					confirmBooking(values, event, data);
				} else {
					console.error(data.error);
					switch (data.error) {
						case 'slot_unavailable':
							jQuery('#atom-aam-form-send').prop('disabled', false).html(STRINGS.error_unavailable);
							break;
						default:
							jQuery('#atom-aam-form-send').prop('disabled', false).html(STRINGS.error_generic);
							break;
					}
				}
			}
		}).fail(function (jqXHR) {
			jQuery('#atom-aam-form-send').prop('disabled', false).html(STRINGS.error_generic);
			console.error("AJAX request failed");
		});
	}

	//////////////////////////////////////////
	function validRange(currentDate) {
	//////////////////////////////////////////

		currentDate = FullCalendarMoment.toMoment(currentDate, fullCalendar);
        return {
            start: currentDate.clone(),
            end: currentDate.clone().add(WEEKLIMIT, 'weeks').endOf('week')
        };

	}

	//////////////////////////////////////////
	function rerenderListView() {
	//////////////////////////////////////////

		loadingIndicator.fadeIn();
		jQuery.ajax({
			type: "POST",
			url: API_AJAXURL,
			data: {
				action: 'atom_appointment_get_frontend_view',
				view: 'list-inner',
				from: moment().format('YYYY-MM-DD'),
				to: moment().add(2, 'week').format('YYYY-MM-DD'),
				type: eventType,
				category: category,
				limit: limit
			},
			success: function(data) {
				container.find('#atom-aam-list').html(data);
				loadingIndicator.fadeOut();
				container.find('.atom-aam-event').click(function() {
					eventClickHandler({event: {
						start:	moment(jQuery(this).attr('data-start')),
						end:	moment(jQuery(this).attr('data-end')),
						title:	jQuery(this).attr('data-title'),
						classNames: jQuery(this).attr('data-classNames'),
						extendedProps: {
							type:	jQuery(this).attr('data-type'),
							db_id:	jQuery(this).attr('data-db_id'),
							moreinfo: jQuery(this).attr('data-moreinfo'),
						}
					}});
				});
			}
		}).fail(function (jqXHR) {
			console.error("AJAX request failed");
		});

	}

	//////////////////////////////////////////
	function initCalendarView() {
	//////////////////////////////////////////

		jQuery.ajax({
			type: "POST",
			url: API_AJAXURL,
			data: {
				action: 'atom_appointment_get_frontend_view',
				view: 'calendar'
			},
			success: function(data) {
				container.html(data);

				calendar = jQuery('#atom-aam-calendar');
				loadingIndicator = jQuery('#atom-aam-loadingindicator');
				loadingProblem = jQuery('#atom-aam-loading-problem');
				sendString = jQuery('#atom-aam-form-send').html();
				modal = jQuery('#atom-aam-modal');
				confirmation = modal.find('#aam-confirmation');

				jQuery('#aam-modal-close').click(function() {
					closeModal();
				});

				jQuery(document).keydown(function(event) {
					if (event.keyCode == 27 && modal.css('display') != 'none') {
						closeModal();
					}
				});

				firstLoad = true;

				fullCalendar = new FullCalendar.Calendar(calendar[0], {
					initialView: 		'timeGridWeek',
					timeZone:			'UTC',
					locale: 			LANGUAGE,
					allDaySlot: 		false,
					aspectRatio:		'auto',
					height:				'auto',
					slotLabelFormat: 	'LT',
					slotDuration: 		'01:00:00',
					slotMinTime:		MINTIME,
					slotMaxTime: 		MAXTIME,
					firstDay: 			1,
					headerToolbar: 		{ start: '', center: '', end: 'today prev,next' },
					dayHeaderContent: 	dayHeaderContent,
					eventClick: 		eventClickHandler,
					windowResize: 		calResizeHandler,
					loading: 			indicateLoading,
					datesSet:			dateRangeChanged
				});
				fullCalendar.setOption('events', fetchEvents);
				fullCalendar.setOption('validRange', validRange);

				fullCalendar.render();

				jQuery('#atom-aam-navigation .fc-prev-button').click(function() {
					fullCalendar.incrementDate({days: -7});
				});
				jQuery('#atom-aam-navigation .fc-next-button').click(function() {
					fullCalendar.incrementDate({days: 7});
				});

				calResizeHandler();
				goToFirstEvent();
			}
		});

	}

	//////////////////////////////////////////
	function initListView() {
	//////////////////////////////////////////

		jQuery.ajax({
			type: "POST",
			url: API_AJAXURL,
			data: {
				action: 'atom_appointment_get_frontend_view',
				view: 'list',
				from: moment().format('YYYY-MM-DD'),
				to: moment().add(2, 'week').format('YYYY-MM-DD'),
				type: eventType,
				category: category,
				limit: limit
			},
			success: function(data) {
				container.append(data);
				loadingIndicator = jQuery('.atom-aam-loadingindicator').fadeOut();
				sendString = jQuery('#atom-aam-form-send').html();
				modal = jQuery('#atom-aam-modal');
				confirmation = modal.find('#aam-confirmation');
				container.find('.atom-aam-event').click(function() {
					eventClickHandler({event: {
						start:	moment(jQuery(this).attr('data-start')),
						end:	moment(jQuery(this).attr('data-end')),
						title:	jQuery(this).attr('data-title'),
						classNames: jQuery(this).attr('data-classNames'),
						extendedProps: {
							type:	jQuery(this).attr('data-type'),
							db_id:	jQuery(this).attr('data-db_id'),
							moreinfo: jQuery(this).attr('data-moreinfo'),
						}
					}});
				});

				jQuery('#aam-modal-close').click(function() {
					closeModal();
				});

				jQuery(document).keydown(function(event) {
					if (event.keyCode == 27 && modal.css('display') != 'none') {
						closeModal();
					}
				});

			}
		}).fail(function (jqXHR) {
			console.error("AJAX request failed");
		});

	}

	//////////////////////////////////////////
	(function init() {
	//////////////////////////////////////////

		jQuery(document).ready(function () {

			moment.locale(LANGUAGE);

			if (LANGUAGE == 'de') {
				dateFormat = 'dd\n DD.MM.';
			} else {
				dateFormat = 'dd\n MM/DD';
			}

			container = jQuery('#atom-appointment-management');
			isAdmin = container.attr('data-admin') == "true";
			display = (container.attr('data-display')) ? container.attr('data-display') : 'calendar';
			eventType = (container.attr('data-type')) ? container.attr('data-type') : 'all';
			category = (container.attr('data-category')) ? container.attr('data-category') : 'all';
			limit = container.attr('data-limit');

			if (DEBUG) console.warn('ATOM APPOINTMENT MANAGEMENT: Debug mode enabled');

			switch (display) {
				case 'list':
					initListView();
					break;
				case 'calendar':
				default:
					initCalendarView();
					break;
			}

		});

	})();

})();
