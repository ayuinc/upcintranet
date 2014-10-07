jQuery(function($) {

	//gets uri arguments
	var getArgs = function()
	{
		var args	= {};
		var query	= location.search.substring(1);
		var pairs	= query.split("&");

		for (var i = 0, l = pairs.length; i < l; i++)
		{
			var pos			= pairs[i].indexOf('=');

			if (pos === -1)
			{
				continue;
			}

			var argname		= pairs[i].substring(0, pos);
			var value		= pairs[i].substring(pos + 1);

			args[argname]	= unescape(value);
		}

		return args;
	};

	var args			= getArgs();
	var $cal_fields		= $('#calendar_fields');
	var $move_to		= $('#find_calendar_fields');
	var $form			= $cal_fields.closest('form');

	// -------------------------------------
	//	cal has a hidden field that needs to
	//	be filled in for required field
	// -------------------------------------

	var	$hidden_field	= false;

	if (typeof CALENDAR_FIELD_INPUT != 'undefined')
	{
		var	$hidden_field = $('input[name="' + CALENDAR_FIELD_INPUT + '"]');
	}

	//default datepicker settings. Lets not repeat ourselves :)
	var dateDefaultSet = {
		dateFormat		: SSCalendar.dateFormat,
		firstDay		: SSCalendar.firstDay,
		dayNames		: SSCalendar.dateFormatSettings.dayNames,
		dayNamesShort	: SSCalendar.dateFormatSettings.dayNamesShort,
		dayNamesMin		: SSCalendar.dateFormatSettings.dayNamesMin,
		monthNames		: SSCalendar.dateFormatSettings.monthNames,
		monthNamesShort	: SSCalendar.dateFormatSettings.monthNamesShort,
		changeMonth		: true,
		changeYear		: true
	};

	// -------------------------------------
	//	binds all functions to widgets
	// -------------------------------------

	var initialize = function()
	{
		// -------------------------------------
		//	Publish/edit form
		// -------------------------------------

		if ($form.length > 0 || $('#publishForm').length > 0)
		{
			// Place the calendar fields at the top of the publish form
			if ($cal_fields.length > 0)
			{
				if ($('.publishRows').length > 1)
				{
					$('.publishRows:eq(0)').before($move_to);
				}
				else if ($('div:regex(id,hold_field_[0-9]+)').length > 1)
				{
					$('div:regex(id,hold_field_[0-9]+):eq(0)').before($move_to);
				}
			}

			// -------------------------------------
			//	Add the timezone menu, if appropriate
			// -------------------------------------

			var $tz = $('#calendar_timezone_menu');

			if ($tz.length > 0)
			{
				var $tz_field = $('#'+$tz.attr('title'));

				//	ee 2.x doesn't use the field_id_ prefix for the id tag,
				//	its just the number of the field
				if ($tz_field.length === 0)
				{
					$tz_field = $('#'+$tz.attr('title').replace('field_id_', ''));
				}

				if ($tz_field.length > 0)
				{
					$tz_field.replaceWith(
						$tz.find('select').
							attr('id', $tz.attr('title')).
							attr('name', $tz.attr('title'))
					);
					$tz.remove();
				}
			}

			// -------------------------------------
			//	Add the default time format, if appropriate
			// -------------------------------------

			var $tf = $('#calendar_time_format');

			if ($tf.length > 0)
			{
				var $tf_field = $('#'+$tf.attr('title'));

				if ($tf_field.length > 0)
				{
					$tf_field.val($tf.text());
					$tf.remove();
				}
			}

			// Toggle display of details based on whether a calendar is selected
			toggle_event_details();

			// Set the default From and To dates
			set_default_dates();

			// Deactivate time fields for all day events
			toggle_time_fields($('div.all_day input:checkbox', $cal_fields));

			// Toggle monthly by
			toggle_monthly_by();

			// Add date picker to first From field
			$('div.first input[name=start_date]', $cal_fields).datepicker($.extend({
				onSelect		: function()
				{
					update_from_field_mindate($('div.rule:not(.first)', $cal_fields));
					update_to_field_mindate($('div.first', $cal_fields), true);
					update_end_by_mindate($('div.rule', $cal_fields));
					update_picker_three_mindate($('div.rule', $cal_fields));
				}
			}, dateDefaultSet));

			// Add date picker to first To field
			$('div.first input[name=end_date]', $cal_fields).datepicker(dateDefaultSet);

			//if this is an edit, we dont need to update mindates
			if (typeof args.entry_id !== 'undefined' &&
				args.entry_id == 0 )
			{
				update_to_field_mindate($('div.first', $cal_fields), false);
			}

			// Add date picker to other From fields
			$('div.rule:not(.first) input[name=start_date]', $cal_fields).datepicker($.extend({
				onSelect		: function()
				{
					$mama = $(this).closest('.rule');
					update_to_field_mindate($mama, false);
					update_end_by_mindate($mama);
					update_picker_three_mindate($mama);
				}
			}, dateDefaultSet));

			// Add date picker to other To fields
			$('div.rule:not(.first) input[name=end_date]', $cal_fields).
				datepicker(dateDefaultSet).
				each(function() {
					update_to_field_mindate($(this).closest('.rule'), false);
				});

			// Create select_dates picker
			create_select_dates_picker();

			// Create selector widgets
			create_selector_widget($('select.selector', $cal_fields));

			// Toggle end by
			toggle_end_by();

			//add date picker to endby
			$('input[name=end_by_date]').datepicker(dateDefaultSet);
			//update_end_by_mindate($('div.rule', $cal_fields));

			// Format dates that are in YYYYMMDD format
			$('input.picker', $cal_fields).each(function() {
				var val = $(this).val();
				$(this).val(
					$.datepicker.formatDate(
						SSCalendar.dateFormat,
						_get_date(val),
						SSCalendar.dateFormatSettings
					)
				);
			});

			show_hide_x();
		}

		// Calendar Module control panel
		else if ($('#contentNB').length > 0)
		{
			// Add date picker to relevant fields
			$('input.picker').datepicker($.extend({
				yearRange: '1900:2050'
			}, dateDefaultSet));
		}
	};
	//END intialize

	// -------------------------------------
	//	date picker
	// -------------------------------------

	var create_select_dates_picker = function($context)
	{
		if ($context == undefined)
		{
			$context = $cal_fields;
		}

		// Add date picker
		$('.picker_three', $context).each(function()
		{
			var $this = $(this);

			$this.closest('.rule').
				find('.date_range .date input').
					datepicker('destroy').remove().
				end().
				find('.date_range .time label').remove();

			var data = new Array;

			$this.children('span.date').each(function()
			{
				data.push(
					$.datepicker.formatDate(
						SSCalendar.dateFormat,
						_get_date($(this).text()),
						SSCalendar.dateFormatSettings
					)
				);
			}).hide().end();

			$this.data('values', data);

			var startDate = ($this.data('values')[0]) ? $this.data('values')[0] : '';

			$this.datepicker($.extend({
				numberOfMonths		: 2,
				showButtonPanel		: true,
				onSelect			: function(dateText, inst)
				{
					//var $p3 = $(this).find('.ui-datepicker-current-day').closest('.picker_three');
					var data = $this.data('values');
					var index = $.inArray(dateText, data);

					if (index > -1)
					{
						data.splice(index, 1);
					}
					else
					{
						data.push(dateText);
					}

					$this.data('values', data);
				},
				beforeShowDay		: function(date)
				{
					var array = new Array;

					array[0] = true;
					array[1] = '';
					array[2] = '';

					if ($.inArray(
							$.datepicker.formatDate(
								SSCalendar.dateFormat,
								date,
								SSCalendar.dateFormatSettings
							),
							$this.data('values')
						) > -1)
					{
						array[1] = 'ui-datepicker-selected-day';
					}

					return array;
				}
			}, dateDefaultSet));

			$this.datepicker('setDate', _get_date(startDate));

			if ($('#calendar_wrapper .rule.first input[repeat_select]').val() == 'select_dates') {
				$this.datepicker(
					'option',
					'minDate',
					_get_date($('#calendar_wrapper .rule.first input[name=start_date]').val())
				);
			}

			//------------------------------------------
			//added this section to help with visuals
			$this.addClass('ss_select_dates ui-corner-all');

		});

		//update_picker_three_mindate($context);
	};

	// -------------------------------------
	//	set default dates
	// -------------------------------------

	var set_default_dates = function()
	{
		var $rule = $(get_rule(1));

		if ($rule.length == 0) return;

		var ee_date = get_ee_date();
		var $from_date = $rule.find('input[name=start_date]');
		var $to_date = $rule.find('input[name=end_date]');

		/*
		if ($from_date.val() == '') {
			$from_date.val(ee_date);
		}

		if ($to_date.val() == '') {
			$to_date.val(ee_date);
		}
		*/
	};

	// -------------------------------------
	//	get_rule
	// -------------------------------------

	var get_rule = function(which)
	{
		var $rule = $('.rule', $cal_fields);

		if ($rule[which-1] !== 'undefined')
		{
			return $rule[which-1];
		}

		return false;
	};

	// -------------------------------------
	//	get_ee_date
	// -------------------------------------

	var get_ee_date = function()
	{
		var date = $('#entry_date').val();

		if (date != undefined)
		{
			date = date.substring(0, 10).split('-');
			date = $.datepicker.formatDate(
				SSCalendar.dateFormat,
				new Date(date[0], date[1] - 1, date[2]),
				SSCalendar.dateFormatSettings
			);
		}
		else
		{
			date = $.datepicker.formatDate(
				SSCalendar.dateFormat,
				new Date(),
				SSCalendar.dateFormatSettings
			);
		}

		return date;
	};

	// -------------------------------------
	//	toggle_time_fields
	// -------------------------------------

	var toggle_time_fields = function($fields)
	{
		$fields.each(function()
		{
			var $rule = $(this).closest('.rule');

			if ($rule.find('select[name=type]').val() != '-')
			{
				$rule.find('.date_range, .date_range *').removeClass('inactive')
			}

			if ($(this).is(':checked'))
			{
				// Is "Select Dates" selected?
				if ($rule.find('select[name=interval]').val() == 'select_dates')
				{
					$rule.find('.date_range div.time, .date_range div.date').
						addClass('inactive').end().
					find('.all_day input[type=checkbox]').attr('checked', 'checked');
				}
				else
				{
					$(this).closest('.date_range').
						find('.time').addClass('inactive').end().
						find('.all_day input[type=checkbox]').attr('checked', 'checked');
				}
			}
		});
	};

	// -------------------------------------
	//	update from field mindate
	// -------------------------------------

	var update_from_field_mindate = function($context)
	{
		var global_start_date;
		var local_start_date;
		var ymd;

		if ($context.length == 0) return;

		//--------------------------------------------
		//	date picker? need to get all dates, and sort
		//--------------------------------------------

		if ($('div.first select[name=interval]', $cal_fields).val() == 'select_dates')
		{
			var dates 	= $('div.first .picker_three', $cal_fields).data('values');

			var ymds 	= [];

			var ymd_obj = {};

			//sort by YMD and find original date
			$.each(dates, function(k, v) {

				var date = $.datepicker.formatDate(
					'yymmdd',
					_get_date(v),
					SSCalendar.dateFormatSettings
				);

				ymds.push(date);
				ymd_obj[date] = v;
			});

			ymd = ymd_obj[ymds.sort()[0]];
		}
		else
		{
			ymd = $('div.first input[name=start_date]', $cal_fields).val();
		}

		global_start_date 	= $.datepicker.parseDate(
			SSCalendar.dateFormat,
			ymd,
			SSCalendar.dateFormatSettings
		);

		local_start_date 	= $('div.date_range input[name=start_date]:eq(0)', $context).val();

		if (local_start_date != '')
		{
			local_start_date = $.datepicker.parseDate(
				SSCalendar.dateFormat,
				local_start_date,
				SSCalendar.dateFormatSettings
			);
		}
		else
		{
			local_start_date = global_start_date;
		}

		$('div.date_range input[name=start_date]:eq(0)', $context).datepicker('option', 'minDate', global_start_date);

		if (local_start_date == 0 || local_start_date <= global_start_date)
		{

			$('div.date_range input[name=start_date]:eq(0)', $context).val(
				$.datepicker.formatDate(
					SSCalendar.dateFormat,
					global_start_date,
					SSCalendar.dateFormatSettings
				)
			);

			update_to_field_mindate($context, true);
			update_picker_three_mindate($context);
			update_end_by_mindate($context);
		}
	};

	var update_to_field_mindate = function($context, adjustToStartValue) {
		if ($context.length == 0) return;

		var $end 		= $('div.date_range input[name=end_date]:eq(0)', $context);
		var start_val 	= String($('div.date_range input[name=start_date]:eq(0)', $context).val());
		var end_val 	= String($end.val());

		var start_date 	= _get_date(start_val);
		var end_date 	= _get_date(end_val);

		$end.datepicker('option', 'minDate', start_date);

		if (end_date < start_date || (adjustToStartValue && end_date > start_date))
		{
			$end.val($.datepicker.formatDate(SSCalendar.dateFormat, start_date, SSCalendar.dateFormatSettings));
		}
		else
		{
			$end.val($.datepicker.formatDate(SSCalendar.dateFormat, end_date, SSCalendar.dateFormatSettings));
		}
	};

	var update_end_by_mindate = function($context) {
		var $eb = $context.find('.end input[name=end_by_date]');
		$eb.each(function() {
			$(this).datepicker('setDate', _get_date($(this).val()));
			var $start_date = $(this).closest('.rule').find('input[name=start_date]');
			$(this).datepicker('option', 'minDate', _get_date($start_date.val()));
			if ($(this).datepicker('getDate') == null || $(this).datepicker('getDate') < $start_date.datepicker('getDate'))
			{
				$(this).datepicker('setDate', $start_date.datepicker('getDate'));
			}
			$.datepicker._refreshDatepicker($(this));
		});
	};

	var update_picker_three_mindate = function($context) {
		var $p3 = $('.picker_three', $context);
		$p3.each(function() {
			$(this).datepicker('option', 'minDate', _get_date($(this).closest('.rule').find('input[name=start_date]').val()));
			$.datepicker._refreshDatepicker($(this));
		});
	};

	var _get_date = function(val) {
		if (val == undefined) {
			return new Date();
		}
		if (parseInt(val) == val && String(val).length == 8) {
			return $.datepicker.parseDate('yymmdd', val, SSCalendar.dateFormatSettings);
		} else {
			return $.datepicker.parseDate(SSCalendar.dateFormat, val, SSCalendar.dateFormatSettings);
		}
	};

	var toggle_event_details = function() {
		if ($('#calendar_calendars select[name=calendar_calendar_id]', $cal_fields).val() != '') {
			$('#calendar_new_date, div.rule', $cal_fields).show();
		} else {
			$('#calendar_new_date, div.rule', $cal_fields).hide();
		}
	};

	var toggle_selector_item = function($item) {
		if ($item.hasClass('close')) {
			reset_selector_widget($item.closest('.selector'));
		} else {
			if ($item.data('selector') == true) {
				$item.removeData('selector').find('a').removeClass('selected');
			}
			else {
				$item.data('selector', true).find('a').addClass('selected');
			}
			update_selector_widget($item.closest('.selector'));
		}
	};

	var update_selector_widget = function($selector) {
		$selector.removeData('selector')
		var data = new Array;
		$selector.find('.item').each(function() {
			if ($(this).data('selector') == true && $(this).data('value') != undefined) {
				data.push($(this).data('value'));
			}
		});
		//console.log(data);
		$selector.data('selector', data);
	};

	var create_selector_widget = function($selectors) {
		$selectors.each(function() {
			var $div = $("<div class='selector line'></div>");
			var items = new Array;
			var count = 1;
			$(this).find('option').each(function() {
				var text = $(this).text();
				var selected = ($(this).is(':selected')) ? true : false;
				var d_class = (count % 7 == 1) ? " newline" : '';
				var s_class = (selected == true) ? " selected" : '';
				items.push(
					$(
						"<div class='item"+d_class+"'>" +
							"<a class='ui-corner-all " + s_class + "' href='#'>" +
								text +
							"</a>" +
						"</div>"
					).data('value', $(this).val()).data('selector', selected));

				count++;
			});
			if (items.length > 0) {
				items.push(
					"<div class='item close'>" +
						"<a class='ui-corner-all' href='#'>" +
							"<span class='ui-icon  ui-icon-close'>" +
							"</span>" +
						"</a>" +
					"</div>"
				);
			}
			$.each(items, function(k, item) {
				$div.append(item);
			});
			$(this).replaceWith($div);
		});
	};

	var reset_selector_widget = function($selector) {
		$selector.removeData('selector').find('.item').each(function() {
			$(this).removeData('selector').find('a').removeClass('selected');
		})
	};

	var toggle_interval_details = function($select)
	{
		$select.each(function()
		{
			var $this 		= $(this),
				$date_range = $this.closest('.inner').find('.date_range'),
				$rule 		= $this.closest('.rule'),
				output 		= {
					options:'',
					extra:''
				};

			$this.
				closest('.repeat_select').
					siblings('.options').remove().
				end().
				closest('.repeat').
					siblings('.end').remove().
				end().
				find('br.clear').remove().
				end().
				find('.picker_three').remove();

			if (_get_rule_type($rule) == '-' )
			{
				$date_range.removeClass('inactive').
					find('.time').hide().end().
					find('.all_day').hide().
						find('checkbox').attr('checked', 'checked');
			}

			switch($this.val())
			{
				case 'daily' :
					output = _html_repeat_interval_daily();
					restore_date_fields($this);
					break;
				case 'weekly' :
					output = _html_repeat_interval_weekly();
					restore_date_fields($this);
					break;
				case 'monthly' :
					output = _html_repeat_interval_monthly();
					restore_date_fields($this);
					break;
				case 'yearly' :
					output = _html_repeat_interval_yearly();
					restore_date_fields($this);
					break;
				case 'select_dates' :
					output = _html_repeat_interval_dates();
					// Discard From and To date fields

					if (_get_rule_type($rule) == '-' )
					{
						$date_range.addClass('inactive');
					}

					$this.
						closest('.rule:not(.first)').
							find('.date_range .date input').
								datepicker('destroy').remove().
							end().
							find('.date_range .time label').remove();

					break;
				default :
					restore_date_fields($this);

					if (_get_rule_type($rule) == '-' )
					{
						$date_range.addClass('inactive');
					}

					if (_get_rule_type($rule) == '+' &&
						_get_rule_all_day($rule) == '')
					{
						toggle_time_fields($this);
					}
					break;
			};

			$this.closest('.repeat_select').
					after(output.options).
					closest('.repeat').
					after(output.extra);

			initialize_interval_details($this.closest('.rule'));
		});
	};

	// -------------------------------------
	//	html templates
	//	these should really be in another format
	// -------------------------------------

	var _html_repeat_interval_daily = function()
	{
		var output = {
			options:	'<div class="options">' +
							'<label>' + SSCalendar.lang.every + '</label>' +
							'<input type="text" name="every" value="1" />' +
							' ' + SSCalendar.lang.day_s + '.' +
						'</div>' +
						'<br class="clear" />',

			extra:		'<div class="group end line">' +
							'<div class="leader">' +
								'<label>' + SSCalendar.lang.end + '</label>' +
								'<select name="end">' +
									'<option value="never">' +
										SSCalendar.lang.never +
									'</option>' +
									'<option value="by_date">' +
										SSCalendar.lang.by_date +
									'</option>' +
									'<option value="after">' +
										SSCalendar.lang.after +
									'</option>' +
								'</select>' +
							'</div>' +
							'<br class="clear" />' +
						'</div>'
		};
		return output;
	};

	var _html_repeat_interval_weekly = function()
	{
		var output = {
			options:	'<div class="options">' +
							'<label>' + SSCalendar.lang.every + '</label>' +
							'<input type="text" name="every" value="1" /> ' +
							SSCalendar.lang.week_s_on + ':' +
							'<div class="extended line dows">' +
								'<select class="selector" multiple="multiple">' +
									'<option value="U">' +
										SSCalendar.lang.day_0_3 +
									'</option>' +
									'<option value="M">' +
										SSCalendar.lang.day_1_3 +
									'</option>' +
									'<option value="T">' +
										SSCalendar.lang.day_2_3 +
									'</option>' +
									'<option value="W">' +
										SSCalendar.lang.day_3_3 +
									'</option>' +
									'<option value="R">' +
										SSCalendar.lang.day_4_3 +
									'</option>' +
									'<option value="F">' +
										SSCalendar.lang.day_5_3 +
									'</option>' +
									'<option value="S">' +
										SSCalendar.lang.day_6_3 +
									'</option>' +
								'</select>' +
							'</div>' +
							'<br class="clear" />' +
						'</div>' +
						'<br class="clear" />',

			extra:		'<div class="group end line">' +
							'<div class="leader">' +
								'<label>'+SSCalendar.lang.end+'</label>' +
								'<select name="end">' +
									'<option value="never">' +
										SSCalendar.lang.never +
									'</option>' +
									'<option value="by_date">' +
										SSCalendar.lang.by_date +
									'</option>' +
									'<option value="after">' +
										SSCalendar.lang.after +
									'</option>' +
								'</select>' +
							'</div>' +
							'<br class="clear" />' +
						'</div>'
		};
		return output;
	};

	var _html_repeat_interval_monthly = function()
	{
		var output = {
			options:	'<div class="options">' +
							'<div class="every">' +
								'<label>' +
									SSCalendar.lang.every +
								'</label>' +
								'<input type="text" name="every" value="1" /> ' +
								'<label>' +
									SSCalendar.lang.month_s_by_day_of +
								'</label> ' +
								'<select name="by">' +
									'<option value="by_date">' +
										SSCalendar.lang.month +
									'</option>' +
									'<option value="by_relative">' +
										SSCalendar.lang.week +
									'</option>' +
								'</select>' +
							'</div>' +
							'<div class="extended by_relative">' +
								'<select class="selector" multiple="multiple">' +
									'<option value="1">' +
										SSCalendar.lang.x1st +
									'</option>' +
									'<option value="2">' +
										SSCalendar.lang.x2nd +
									'</option>' +
									'<option value="3">' +
										SSCalendar.lang.x3rd +
									'</option>' +
									'<option value="4">' +
										SSCalendar.lang.x4th +
									'</option>' +
									'<option value="5">' +
										SSCalendar.lang.x5th +
									'</option>' +
									'<option value="6">' +
										SSCalendar.lang.last +
									'</option>' +
								'</select>' +
								'<br class="clear" />' +
							'</div>' +
							'<div class="extended by_date">' +
								'<select class="selector" multiple="multiple">' +
									'<option value="1">1</option>' +
									'<option value="2">2</option>' +
									'<option value="3">3</option>' +
									'<option value="4">4</option>' +
									'<option value="5">5</option>' +
									'<option value="6">6</option>' +
									'<option value="7">7</option>' +
									'<option value="8">8</option>' +
									'<option value="9">9</option>' +
									'<option value="A">10</option>' +
									'<option value="B">11</option>' +
									'<option value="C">12</option>' +
									'<option value="D">13</option>' +
									'<option value="E">14</option>' +
									'<option value="F">15</option>' +
									'<option value="G">16</option>' +
									'<option value="H">17</option>' +
									'<option value="I">18</option>' +
									'<option value="J">19</option>' +
									'<option value="K">20</option>' +
									'<option value="L">21</option>' +
									'<option value="M">22</option>' +
									'<option value="N">23</option>' +
									'<option value="O">24</option>' +
									'<option value="P">25</option>' +
									'<option value="Q">26</option>' +
									'<option value="R">27</option>' +
									'<option value="S">28</option>' +
									'<option value="T">29</option>' +
									'<option value="U">30</option>' +
									'<option value="V">31</option>' +
								'</select>' +
								'<p class="clear">' +
									SSCalendar.lang.only_on+':' +
								'</p>' +
							'</div>' +
							'<div class="extended line dows">' +
								'<select class="selector" multiple="multiple">' +
									'<option value="U">' +
										SSCalendar.lang.day_0_3 +
									'</option>' +
									'<option value="M">' +
										SSCalendar.lang.day_1_3 +
									'</option>' +
									'<option value="T">' +
										SSCalendar.lang.day_2_3 +
									'</option>' +
									'<option value="W">' +
										SSCalendar.lang.day_3_3 +
									'</option>' +
									'<option value="R">' +
										SSCalendar.lang.day_4_3 +
									'</option>' +
									'<option value="F">' +
										SSCalendar.lang.day_5_3 +
									'</option>' +
									'<option value="S">' +
										SSCalendar.lang.day_6_3 +
									'</option>' +
								'</select>' +
								'<br class="clear" />' +
							'</div>' +
						'</div>' +
						'<br class="clear" />',

			extra:		'<div class="group end line">' +
							'<div class="leader">' +
								'<label>' +
									SSCalendar.lang.end +
								'</label>' +
								'<select name="end">' +
									'<option value="never">' +
										SSCalendar.lang.never +
									'</option>' +
									'<option value="by_date">' +
										SSCalendar.lang.by_date +
									'</option>' +
									'<option value="after">' +
										SSCalendar.lang.after +
									'</option>' +
								'</select>' +
							'</div>' +
							'<br class="clear" />' +
						'</div>'
		};
		return output;
	};

	var _html_repeat_interval_yearly = function()
	{
		var output = {
			options:	'<div class="options">' +
							'<label>' + SSCalendar.lang.every + '</label>' +
							'<input type="text" name="every" value="1" /> ' +
							SSCalendar.lang.year_s+'.' +
						'</div>' +
						'<br class="clear" />',

			extra:		'<div class="group end line">' +
							'<div class="leader">' +
								'<label>'+SSCalendar.lang.end+'</label>' +
								'<select name="end">' +
									'<option value="never">' +
										SSCalendar.lang.never +
									'</option>' +
									'<option value="by_date">' +
										SSCalendar.lang.by_date +
									'</option>' +
									'<option value="after">' +
										SSCalendar.lang.after +
									'</option>' +
								'</select>' +
							'</div>' +
							'<br class="clear" />' +
						'</div>'
		};
		return output;
	};

	var _html_repeat_interval_dates = function()
	{
		var output = {
			options:	'<div class="options">' +
							'<div class="picker_three">' +
							'</div>' +
						'</div>',
			extra:		''
		};
		return output;
	};

	var _html_end_by_date = function()
	{
		var output =	'<div class="options">' +
							'<input type="text" name="end_by_date" />' +
						'</div>';
		return output;
	};

	var _html_end_after = function()
	{
		var output =	'<div class="options">' +
							'<input type="text" name="end_after" /> ' +
							SSCalendar.lang.time_s +
						'</div>';
		return output;
	};

	var _html_new_rule = function()
	{
		var time = new Date();
		var myTime = time.getTime();

		//yes, this are disgustingly long
		//to be replaces with templates later
		if (SSCalendar.version < 2)
		{
			var output = '' +
				'<div class="rule">' +
					'<div class="inner">' +
						'<div class="rule_number">' +
							'<span></span>' +
						'</div>' +
						'<div class="rule_close">' +
							'<a href="#">x</a>' +
						'</div>' +
						'<div class="group first type line">' +
							'<div class="leader">' +
								'<label>' +
									SSCalendar.lang.type +
								'</label>' +
								'<select name="type">' +
									'<option value="+">' +
										SSCalendar.lang.include +
									'</option>' +
									'<option value="-">' +
										SSCalendar.lang.exclude +
									'</option>' +
								'</select>' +
							'</div>' +
							'<br  class="clear" />' +
						'</div>' +
						'<div class="group date_range">' +
							'<div class="all_day line">' +
								'<input type="checkbox" ' +
									'id="all_day_' + myTime +
									'" name="all_day_' + myTime +
									'" value="y" />' +
								' <label for="all_day_'+ myTime +'">' +
									SSCalendar.lang.all_day_event +
								'</label>' +
							'</div>' +
							'<div class="from line">' +
								'<div class="date leader">' +
									'<label>' +
										SSCalendar.lang.from +
									'</label>' +
									'<input type="text" ' +
										'name="start_date" '  +
										'class="picker" value="" />' +
								'</div>' +
								'<div class="time">' +
									'<label>' +
										SSCalendar.lang.at +
									'</label>' +
									'<input type="text" ' +
										'name="start_time" />' +
									'<select name="ampm">' +
										'<option value="am">' +
											SSCalendar.lang.am +
										'</option>' +
										'<option value="pm">' +
											SSCalendar.lang.pm +
										'</option>' +
									'</select>' +
								'</div>' +
								'<br class="clear" />' +
							'</div>' +
							'<div class="to line">' +
								'<div class="date leader">' +
									'<label>' +
										SSCalendar.lang.to +
									'</label>' +
									'<input type="text" ' +
										'name="end_date" ' +
										'class="picker" value="" />' +
								'</div>' +
								'<div class="time">' +
									'<label>' +
										SSCalendar.lang.at +
									'</label>' +
									'<input type="text" name="end_time" />' +
									'<select name="ampm">' +
										'<option value="am">' +
											SSCalendar.lang.am +
										'</option>' +
										'<option value="pm">' +
											SSCalendar.lang.pm +
										'</option>' +
									'</select>' +
								'</div>' +
								'<br class="clear" />' +
							'</div>' +
						'</div>' +
						'<div class="group repeat line">' +
							'<div class="repeat_select leader">' +
								'<label>' +
									SSCalendar.lang.repeat +
								'</label>' +
								'<select name="interval">' +
									'<option value="none">' +
										SSCalendar.lang.none +
									'</option>' +
									'<option value="daily">' +
										SSCalendar.lang.daily +
									'</option>' +
									'<option value="weekly">' +
										SSCalendar.lang.weekly +
									'</option>' +
									'<option value="monthly">' +
										SSCalendar.lang.monthly +
									'</option>' +
									'<option value="yearly">' +
										SSCalendar.lang.yearly +
									'</option>' +
									'<option value="select_dates">' +
										SSCalendar.lang.select_dates +
									'</option>' +
								'</select>' +
							'</div>' +
							'<div class="options">' +
							'</div>' +
							'<br class="clear" />' +
						'</div>' +
						'<br class="clear" />' +
					'</div>' +
				'</div>';
		}
		else
		{
			var output = '' +
				'<div class="rule ui-widget-content ' +
							'ui-corner-all ui-widget ">' +
					'<div class="inner">' +
						'<div class="rule_number ui-state-default ' +
							'ui-corner-br ui-corner-tl">' +
							'<span></span>' +
						'</div>' +
						'<div class="rule_close ui-state-default ' +
									'ui-corner-bl ui-corner-tr">' +
							'<span class="ui-icon ui-icon-close"></span>' +
						'</div>' +
						'<div class="group first type line">' +
							'<div class="leader">' +
								'<label>'+SSCalendar.lang.type+'</label>' +
								'<select name="type">' +
									'<option value="+">' +
										SSCalendar.lang.include +
									'</option>' +
									'<option value="-">' +
										SSCalendar.lang.exclude +
									'</option>' +
								'</select>' +
							'</div>' +
							'<br  class="clear" />' +
						'</div>' +
						'<div class="group date_range">' +
							'<div class="all_day line">' +
								'<input type="checkbox" ' +
									'id="all_day_' + myTime + '" ' +
									'name="all_day_'+ myTime +'" value="y" /> ' +
								'<label for="all_day_'+ myTime +'">' +
									SSCalendar.lang.all_day_event +
								'</label>' +
							'</div>' +
							'<div class="from line">' +
								'<div class="date leader">' +
									'<label>'+SSCalendar.lang.from+'</label>' +
									'<input type="text" name="start_date" ' +
										'class="picker" value="" />' +
								'</div>' +
								'<div class="time">' +
									'<label>'+SSCalendar.lang.at+'</label>' +
									'<input type="text" name="start_time" />' +
									'<select name="ampm">' +
										'<option value="am">' +
											SSCalendar.lang.am +
										'</option>' +
										'<option value="pm">' +
											SSCalendar.lang.pm +
										'</option>' +
									'</select>' +
								'</div>' +
								'<br class="clear" />' +
							'</div>' +
							'<div class="to line">' +
								'<div class="date leader">' +
									'<label>'+SSCalendar.lang.to+'</label>' +
									'<input type="text" name="end_date" ' +
										'class="picker" value="" />' +
								'</div>' +
								'<div class="time">' +
									'<label>'+SSCalendar.lang.at+'</label>' +
									'<input type="text" name="end_time" />' +
									'<select name="ampm">' +
										'<option value="am">' +
											SSCalendar.lang.am +
										'</option>' +
										'<option value="pm">' +
											SSCalendar.lang.pm +
										'</option>' +
									'</select>' +
								'</div>' +
								'<br class="clear" />' +
							'</div>' +
						 '</div>' +
						 '<div class="group repeat line">' +
							'<div class="repeat_select leader">' +
								'<label>' +
									SSCalendar.lang.repeat +
								'</label>' +
								'<select name="interval">' +
									'<option value="none">' +
										SSCalendar.lang.none +
									'</option>' +
									'<option value="daily">' +
										SSCalendar.lang.daily +
									'</option>' +
									'<option value="weekly">' +
										SSCalendar.lang.weekly +
									'</option>' +
									'<option value="monthly">' +
										SSCalendar.lang.monthly +
									'</option>' +
									'<option value="yearly">' +
										SSCalendar.lang.yearly +
									'</option>' +
									'<option value="select_dates">' +
										SSCalendar.lang.select_dates +
									'</option>' +
								'</select>' +
							'</div>' +
							'<div class="options"></div>' +
							'<br class="clear" />' +
						'</div>' +
						'<br class="clear" />' +
					'</div>' +
				'</div>';
		}

		return output;
	};
	//END _html_new_rule()

	// -------------------------------------
	//	restore date fields
	// -------------------------------------

	var restore_date_fields = function($context)
	{
		var $range = $context.closest('.rule').find('.date_range');

		if ($range.find('.date input, .time label').length == 0)
		{
			$range.find('.from .date label').
				after('<input type="text" name="start_date" class="picker" value="" />');

			$range.find('.to .date label').
				after('<input type="text" name="end_date" class="picker" value="" />');

			$range.find('.time').prepend('<label>at</label>');

			// Add date picker to From field
			var $rule = $context.closest('.rule');

			$('input[name=start_date]', $rule).datepicker({
				dateFormat		: SSCalendar.dateFormat,
				firstDay		: SSCalendar.firstDay,
				changeMonth		: true,
				changeYear		: true,
				onSelect		: function()
				{
					update_to_field_mindate($rule, true);
					update_picker_three_mindate($rule);
					update_end_by_mindate($rule);
				}
			});

			// Add date picker to To field
			$('input[name=end_date]', $rule).datepicker({
				dateFormat		: SSCalendar.dateFormat,
				firstDay		: SSCalendar.firstDay,
				changeMonth		: true,
				changeYear		: true
			});

			update_from_field_mindate($rule);
			update_to_field_mindate($rule, true);
			//removed this because it was setting it to the current date eventhough its restoring
			//update_end_by_mindate($rule);
		}

		$range.find('.date.inactive').removeClass('inactive');

		//this adds back in a spacer that css depends on being there
		//(yes, that is bad, but look around this file.)
		if ($context.val() == 'none')
		{
			$context.parent().parent().
				append('<div class="options"></div><br class="clear" />');
		}
	};

	// -------------------------------------
	//	initialize_interval_details
	// -------------------------------------

	var initialize_interval_details = function($rule)
	{
		create_selector_widget($('select.selector', $rule));
		toggle_monthly_by($rule);
		toggle_end_by($rule);
		create_select_dates_picker($rule);
	};

	// -------------------------------------
	//	toggle_monthly_by
	// -------------------------------------

	var toggle_monthly_by = function($context)
	{
		if ($context == undefined)
		{
			$context = $('div.rule', $cal_fields);
		}

		$context.each(function()
		{
			if ($(this).find('select[name=interval]', $context).val() == 'monthly')
			{
				var $select = $(this).find('select[name=by]', $context);
				var val = $select.val();

				$(this).
					find('.'+val).show().end().
					find('.extended').
						filter(':not(.'+val+')').
						filter(':not(.dows)').hide();

				$select.unbind('change').change(function()
				{
					toggle_monthly_by($(this).closest('.rule'));
				});
			}
		});
	};

	// -------------------------------------
	//	toggle end by
	// -------------------------------------

	var toggle_end_by = function($context)
	{
		if ($context == undefined)
		{
			$context = $cal_fields;
		}

		$context.find('select[name=end]').change(function()
		{
			var $select = $(this);

			switch($select.val())
			{
				case 'by_date' :
					$select.parent().
						siblings('.options').remove().end().
						after(_html_end_by_date());

					$select.parent().siblings('.options').find('input').datepicker({
						dateFormat 		: SSCalendar.dateFormat,
						firstDay		: SSCalendar.firstDay,
						changeMonth 	: true,
						changeYear 		: true
					});

					update_end_by_mindate($(this).closest('.rule'));
					break;

				case 'after' :
					$select.parent().siblings('.options').remove().end().after(_html_end_after());
					break;

				default :
					$select.parent().siblings('.options').remove();
					break;
			};
		})
	};

	// -------------------------------------
	//	renumber_rules
	// -------------------------------------

	var renumber_rules = function()
	{
		$('.rule', $cal_fields).each(function(n)
		{
			$(this).find('.rule_number span').text(n+1);
		});
	};

	// -------------------------------------
	//	save calendar data (runs on submit)
	// -------------------------------------

	var save_calendar_data = function()
	{
		var success = false;
		var data = new Object;

		// Remove old saved data
		$('#calendar_data').remove();

		// Remove old errors
		$('#calendar_errors').remove();

		// Get the calendar ID
		data.calendar_id = $('select[name=calendar_calendar_id]', $cal_fields).val();

		// Prepare for our rule data
		data.rules = new Array;
		data.dates = new Array;

		// Iterate through the rules
		$('div.rule', $cal_fields).each(function()
		{

			var first = $(this).is('.first');

			var rule = {

				'rule_id'	: _get_rule_id($(this).find('input[name=rule_id]')),
				'rule_type'	: _get_rule_type($(this)),
				'all_day'	: _get_rule_all_day($(this)),
				'start_time': (_get_rule_all_day($(this)) == 'y') ?
								'' : _get_rule_time($(this).find('.date_range .from .time input')),
				'end_time'	: (_get_rule_all_day($(this)) == 'y') ?
								'' : _get_rule_time($(this).find('.date_range .to .time input'))
			};

			if ($(this).find('select[name=interval]').val() == 'select_dates')
			{
				if (first != true)
				{
					$(this).find('input[name=start_date], input[name=end_date]').remove();
				}
				else
				{
					rule.start_date = _get_rule_date($(this).find('input[name=start_date]'));
					rule.end_date 	= _get_rule_date($(this).find('input[name=end_date]'));
					data.rules.push(rule);
				}

				var dates = $(this).find('.picker_three').data('values');

				$.each(dates, function(k, v)
				{
					var o = {
						'date'			: $.datepicker.formatDate(
							'yymmdd',
							_get_date(v),
							SSCalendar.dateFormatSettings
						),
						'start_time' 	: rule.start_time,
						'end_time'		: rule.end_time,
						'all_day'		: rule.all_day,
						'rule_type'		: rule.rule_type
					};

					data.dates.push(o);
				});
			}
			else
			{
				rule.start_date 	= _get_rule_date($(this).find('input[name=start_date]'));
				rule.end_date 		= _get_rule_date($(this).find('input[name=end_date]'));
				rule.all_day 		= _get_rule_all_day($(this));
				rule.repeat_years 	= _get_rule_years($(this));
				rule.repeat_months 	= _get_rule_months($(this));
				rule.repeat_weeks 	= _get_rule_weeks($(this));
				rule.repeat_days 	= _get_rule_days($(this));
				rule.days_of_week 	= _get_rule_dow($(this));
				rule.relative_dow 	= _get_rule_relative($(this));
				rule.days_of_month 	= _get_rule_dom($(this));
				rule.months_of_year = '';
				rule.end_by 		= _get_rule_date($(this).find('input[name=end_by_date]'));
				rule.end_after		= _get_rule_end_after($(this));
				data.rules.push(rule);
			}
		});

		// Save the rules as hidden inputs
		var string = '<div id="calendar_data">';

		if (data.calendar_id)
		{
			string += _create_hidden_input('calendar_id', data.calendar_id);
		}

		$.each(data.rules, function(k, rule)
		{
			$.each(rule, function(name, value)
			{
				string += _create_hidden_input(name+'['+k+']', value);
			});
		});

		$.each(data.dates, function(k, date)
		{
			$.each(date, function(name, value)
			{
				string += _create_hidden_input('occurrences['+name+'][]', value);
			});
		});

		string += '</div>';

		if ($('#entryform').length > 0)
		{
			$('#entryform').append(string);
		}
		else if ($('#publishForm').length > 0)
		{
			$('#publishForm').append(string);
		}
		else if ($('#submit_button').length > 0)
		{
			$('#submit_button').before(string);
		}

		// TODO: We could check for errors at some point...
		success = true;

		return success;
	};
	//END save_calendar_data

	// -------------------------------------
	//	get rule id
	// -------------------------------------

	var _get_rule_id = function($context)
	{
		if ($context.closest('.rule').find('select[name=interval]').val() == 'none')
		{
			return '';
		}

		var val = $context.val();

		return (val == undefined) ? '' : val;

	};

	// -------------------------------------
	//	_get_rule_type
	// -------------------------------------

	var _get_rule_type = function($context)
	{
		var val = $context.find('select[name=type]').val();
		return (val == undefined || val == '') ? '+' : val;
	};

	// -------------------------------------
	//	_get_rule_date
	// -------------------------------------

	var _get_rule_date = function($field)
	{
		var val = $field.val();

		if (val == undefined)
		{
			return '';
		}

		val = $.datepicker.formatDate(
			'yymmdd',
			_get_date(val),
			SSCalendar.dateFormatSettings
		);

		return val;
	};

	// -------------------------------------
	//	_get_rule_all_day
	// -------------------------------------

	var _get_rule_all_day = function($context)
	{
		return ($('.all_day input:checkbox', $context).
					is(':checked')) ? 'y' : '';
	};

	// -------------------------------------
	//	_get_rule_time
	// -------------------------------------

	var _get_rule_time = function($field)
	{
		var val = String($field.val());

		if (val == undefined || val == '')
		{
			return '';
		}

		var minute = '';
		var hour = '';
		var ampm = ($field.siblings('select[name=ampm]').length == 1) ?
						$field.siblings('select[name=ampm]').val() : '';

		// Just an hour
		if (val.length == 1 || val.length == 2)
		{
			minute 	= '00';
			hour 	= val;
		}
		else if (val.indexOf(':') != -1)
		{
			var temp 	= val.split(':');
			hour 		= temp[0];
			minute 		= temp[1];
		}
		else if (val.length == 3 || val.length == 4)
		{
			minute 	= val.substring(val.length - 2);
			hour 	= val.substring(0, val.length - 2);
		}
		else
		{
			minute 	= '00';
			hour 	= '00';
		}

		// Remove leading zero, or else we won't like what parseInt() gives us
		if (hour.substring(0, 1) == 0)
		{
			hour = hour.substring(1);
		}

		//adjust hours to military if pm
		if (ampm == 'pm' && parseInt(hour, 10) < 12)
		{
			hour = parseInt(hour, 10) + 12;
		}
		else if (ampm == 'am' && parseInt(hour, 10) == 12)
		{
			hour = '00';
		}

		//no negatives of over 24h
		if (parseInt(hour, 10) < 0 || parseInt(hour, 10) > 23)
		{
			hour = '00';
		}

		//no negatives of over 60m
		if (parseInt(minute, 10) < 0 || parseInt(minute, 10) > 59)
		{
			minute = '00';
		}

		//add leading 0
		if (hour.length == 1)
		{
			hour = "0" + hour;
		}

		return hour + minute;
	};
	//END _get_rule_time


	// -------------------------------------
	//	_get_rule_years
	// -------------------------------------

	var _get_rule_years = function($context)
	{
		var val = '';

		if ($('select[name=interval]', $context).val() == 'yearly')
		{
			val = $('input[name=every]', $context).val();
		}

		return val;
	};

	// -------------------------------------
	//	_get_rule_months
	// -------------------------------------

	var _get_rule_months = function($context)
	{
		var val = '';

		if ($('select[name=interval]', $context).val() == 'monthly')
		{
			val = $('input[name=every]', $context).val();
		}

		return val;
	};

	// -------------------------------------
	//	_get_rule_weeks
	// -------------------------------------

	var _get_rule_weeks = function($context)
	{
		var val = '';

		if ($('select[name=interval]', $context).val() == 'weekly')
		{
			val = $('input[name=every]', $context).val();
		}

		return val;
	};

	// -------------------------------------
	//	_get_rule_days
	// -------------------------------------

	var _get_rule_days = function($context)
	{
		var val = '';

		if ($('select[name=interval]', $context).val() == 'daily')
		{
			val = $('input[name=every]', $context).val();
		}

		return val;
	};

	// -------------------------------------
	//	_get_rule_dow
	// -------------------------------------

	var _get_rule_dow = function($context)
	{
		var val = [];

		$('.dows .selector .item', $context).each(function()
		{
			if ($(this).data('selector') == true)
			{
				val.push($(this).data('value'));
			}
		});

		return val.join('|');
	};

	// -------------------------------------
	//	_get_rule_relative
	// -------------------------------------

	var _get_rule_relative = function($context)
	{
		var val = [];

		if ($('select[name=by]', $context).val() == 'by_relative')
		{
			$('.by_relative .selector .item', $context).each(function(){

				if ($(this).data('selector') == true)
				{
					val.push($(this).data('value'));
				}
			});
		}

		return val.join('|');
	};

	// -------------------------------------
	//	_get_rule_dom
	// -------------------------------------

	var _get_rule_dom = function($context)
	{
		var val = new Array;

		if ($('select[name=by]', $context).val() == 'by_date')
		{
			$('.by_date .selector .item', $context).each(function()
			{
				if ($(this).data('selector') == true)
				{
					val.push($(this).data('value'));
				}
			});
		}

		return val.join('|');
	};

	// -------------------------------------
	//	_get_rule_end_after
	// -------------------------------------

	var _get_rule_end_after = function($context)
	{
		var val = '';

		if ($('select[name=end]', $context).val() == 'after')
		{
			val = $('input[name=end_after]', $context).val();
		}

		return val;
	}

	// -------------------------------------
	//	_create_hidden_input
	// -------------------------------------

	var _create_hidden_input = function(name, value)
	{
		return '<input type="hidden" name="'+name+'" value="'+value+'" />';
	};

	// -------------------------------------
	//	show_hide_x
	// -------------------------------------

	var show_hide_x = function()
	{
		var $rules = $('.rule', $cal_fields);

		// Hide X if there is only one rule
		if ($rules.length == 1)
		{
			$('.rule_close, .type', $rules).addClass('inactive');
		}
		// Hide X on the first rule if there is only one include rule
		else if ($('.type select[value="+"]', $rules).length == 1)
		{
			$('.type select[value="+"]', $rules).closest('.rule').
				find('.rule_close, .type').addClass('inactive');
		}
		// Show X on first rule
		else
		{
			$('.rule:first .rule_close', $cal_fields).removeClass('inactive');
		}
	}

	//------------------------------------------------------------------
	//	start everything
	//------------------------------------------------------------------

	// Systems are go!
	initialize();

	// 12 hour or 24 hour?
	var time_format = (
		$('.rule.first select[name=ampm]', $cal_fields).length > 0
	) ? '12' : '24';

	// When the user clicks submit, the work is just getting started...
	//$('input[name=submit], input[name=save], .calendar_submit').click(function()

	//this can be slow, but its the only way to
	//always get the form thats getting submitted
	var $publishForm = $cal_fields.closest('form');

	$publishForm.submit(function()
	{
		return save_calendar_data();
	});

	// Toggle open/close the Dates & Options fields
	$('.label a', $cal_fields).click(function()
	{
		// Hide 'em, Danno
		$('#calendar_wrapper', $cal_fields).toggle();

		// Swap the +/- image
		$(this).find('img').toggle();

		return false;
	});

	// Toggle the time fields when switching between Include and Exclude
	$('select[name=type]').live('change', function()
	{
		var val = $(this).val();

		//if we are at val== '-', lets make select dates default if none is selected
		if (val == '-')
		{
			if ($(this).closest('.rule').find('select[name=interval]').val() == 'none')
			{
				$(this).closest('.rule').find('select[name=interval]').val('select_dates').trigger('change');
			}
		}

		if (val == '+')
		{
			$(this).closest('.inner').
				find('.date_range, .date_range *').removeClass('inactive').
					find('.time').show().end().
					find('.all_day').show();
		}
		else if ($(this).closest('.rule').find('select[name=interval]').val() == 'select_dates')
		{
			$(this).closest('.inner').find('.date_range').addClass('inactive');
		}
		else
		{
			$(this).closest('.inner').
				find('.date_range').
					find('.time, .all_day').addClass('inactive');
		}

		show_hide_x();
	});

	// Toggle display of time fields
	$('div.all_day input:checkbox').live('change', function()
	{
		toggle_time_fields($(this));
	});

	// Toggle display of event details
	$('#calendar_calendars select[name=calendar_calendar_id]', $cal_fields).change(function()
	{
		//fills the hidden field so 'required' field works
		if ($hidden_field)
		{
			$hidden_field.val($.trim($(this).val()));
		}
		toggle_event_details();
	});

	//fills the hidden field so 'required' field works (in case this is an edit)
	if ($hidden_field)
	{
		var tempVal = $.trim(
			$('#calendar_calendars select[name=calendar_calendar_id]', $cal_fields).val()
		);

		if (tempVal)
		{
			$hidden_field.val(tempVal);
		}
	}

	// Toggle display of interval details
	$('.repeat_select select', $cal_fields).change(function()
	{
		toggle_interval_details($(this));
	});

	// Selector widget actions
	$('.selector .item a', $cal_fields).live('click', function()
	{
		var $item = $(this).closest('.item');

		toggle_selector_item($item);

		return false;
	});

	// Add a new rule
	$('#calendar_new_date button').click(function() {

		var $rule = $(_html_new_rule());

		// Get rid of am/pm dropdowns if we're in 24 hour time
		if (time_format == '24')
		{
			$rule.find('select[name=ampm]').remove();
		}

		// Add rule number
		$rule.find('div.rule_number span').text($('.rule', $cal_fields).length+1);

		// Add date picker to From field
		$('input[name=start_date]', $rule).datepicker({
			dateFormat		: SSCalendar.dateFormat,
			firstDay		: SSCalendar.firstDay,
			changeMonth		: true,
			changeYear		: true,
			onSelect		: function()
			{
				update_to_field_mindate($rule, true);
				update_picker_three_mindate($rule);
				update_end_by_mindate($rule);
				update_picker_three_mindate($rule);
			}
		});

		// Add date picker to To field
		$('input[name=end_date]', $rule).datepicker({
			dateFormat		: SSCalendar.dateFormat,
			firstDay		: SSCalendar.firstDay,
			changeMonth		: true,
			changeYear		: true
		});

		update_from_field_mindate($rule);
		update_to_field_mindate($rule, true);

		// Toggle display of interval details
		$('.repeat_select select', $rule).change(function() {
			toggle_interval_details($(this));
		});

		$('#calendar_wrapper div.rule:last').after($rule);

		show_hide_x();

		return false;
	});

	// Close the rule
	$('.rule_close', $cal_fields).live('click', function(){

		var $firstrule = $(this).closest('.rule.first');

		if ($firstrule.length == 1)
		{
			var $includes = $('.type select[value="+"]', $cal_fields).
				not($firstrule.find('.type select'));

			if ($includes.length > 0)
			{
				$includes.eq(0).
					closest('.rule').addClass('first').
						replaceAll($firstrule).
							find('.group').addClass('first');
			}
		}
		else
		{
			$(this).closest('.rule').remove();
		}

		renumber_rules();
		show_hide_x();

		// Remove rule_type from first rule
		$('.rule.first .type').addClass('inactive');

		return false;
	});

	var $firstRuleTypes = $('.rule.first .type');

	// -------------------------------------
	//	Sometimes we get more than one .first
	//	class going on. Thats bad, MKay?
	// -------------------------------------

	if ($firstRuleTypes.length > 1)
	{
		var first = false;

		$firstRuleTypes.each(function(){
			if ( ! first)
			{
				first = true;
				return;
			}

			$(this).closest('.rule.first').removeClass('first');
		});

		//fix jQuery array
		$firstRuleTypes = $('.rule.first .type');
	}

	// Remove rule_type from first rule
	$firstRuleTypes.addClass('inactive');
});

jQuery.expr[':'].regex = function(elem, index, match)
{
	var matchParams = match[3].split(','),
		validLabels = /^(data|css):/,
		attr = {
			method: matchParams[0].match(validLabels) ?
						matchParams[0].split(':')[0] : 'attr',
			property: matchParams.shift().replace(validLabels,'')
		},
		regexFlags = 'ig',
		regex = new RegExp(matchParams.join('').replace(/^\s+|\s+$/g,''), regexFlags);
	return regex.test(jQuery(elem)[attr.method](attr.property));
}