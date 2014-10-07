/*
	This file does no actual permissions work and just adds CSS classes to links
	that go to permissions handled calendar events, so don't get any funny
	ideas :p - GF
*/
(function(global, $){

	function getUrlArgs(url)
	{
		var urlVars = {},
			url 	= url || window.location.href,
			parts 	= url.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value)
			{
				urlVars[key] = value;
			});

		return urlVars;
	}

	$(function(){
		var entryData,
			buttonsOptions 	= {},
			dialogInstalled = ( typeof $.fn.dialog !== 'undefined'),
			$dialog 		= $('<div></div>').html(global.calendarFilterSettings.dialogMessage);

		// -------------------------------------
		//	denied dialog
		// -------------------------------------

		if (dialogInstalled)
		{
			//because JS accepts non string object literal names :/
			buttonsOptions[global.calendarFilterSettings.dialogOk] = {
				'click'	: function() { 
					$(this).dialog("close"); 
				},
				'class'	: 'submit',
				'text'	: global.calendarFilterSettings.dialogOk
			}
					
			$dialog.dialog({
				autoOpen: false,
				title 	: global.calendarFilterSettings.dialogTitle,
				buttons : buttonsOptions,
				modal   : true 
			});	
		}

		function aClick (e)
		{
			e.preventDefault();
			
			if (dialogInstalled)
			{
				$dialog.dialog('open');
			}
			else
			{
				alert(global.calendarFilterSettings.dialogMessage);
			}
			// prevent the default action, e.g., following a link
			return false;
		} 

		// -------------------------------------
		//	permission checker
		// -------------------------------------

		//in its own function so it can be rebound
		function checkPermissions()
		{
			//these will have to be recalced in case they change
			var $trs = $('#entries_form table:first tr');

			$.each($trs, function(i, item){ 
				var $tr 		= $(item),
					$del 		= $tr.find('td:last input'),
					$atd 		= $tr.find('td:eq(1)'),
					$a  		= $atd.find('a:first'),
					hrefParts 	= getUrlArgs($a.attr('href'));

				//see if any of the links are in the calendar entries channel_id
				if ($.inArray(hrefParts.channel_id, entryData.channelIds.events) > -1)
				{
					//if we aren't allwed to access, disable
					if ( $.inArray(hrefParts.entry_id, entryData.allowedEntryIds) == -1)
					{
						$atd.addClass('locked');
						$a.click(aClick);
						$del.attr('disabled', 'disabled').removeClass('toggle');
					}
				}
			});
		}
		//END checkPermissions

		// -------------------------------------
		//	json 
		// -------------------------------------

		$.getJSON(global.calendarFilterSettings.act, function(data){
			//no need to do work if none are allowed ;)
			if (data.allAllowed)
			{
				return;
			}

			entryData = data;
			checkPermissions();

			$('body').ajaxComplete(function(event, request, settings){
				if (settings.url.indexOf('edit_ajax_filter') > -1) 
				{
					checkPermissions();
				}
			});

			$('.paginate_button').click(function(){
				setTimeout(function(){
					checkPermissions();
				}, 100);
			});
		});
	});
}(window, jQuery));