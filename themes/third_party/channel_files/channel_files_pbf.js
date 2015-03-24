// ********************************************************************************* //
var ChannelFiles = ChannelFiles ? ChannelFiles : {};
ChannelFiles.SWF = {}; ChannelFiles.HTML5 = {}; ChannelFiles.SWFUPLOAD = {}; ChannelFiles.Templates = {}; ChannelFiles.Refreshing = false;
//********************************************************************************* //

jQuery(document).ready(function() {

	// If you have multiple saef fields we only need to do this once
	if (typeof(ChannelFiles.initfields_done) == 'undefined'){
		ChannelFiles.initfields_done = 'yes';
		ChannelFiles.InitFields();
	}

	// Parse Hogan Templates
	ChannelFiles.Templates['TableTR'] = Hogan.compile(jQuery('#ChannelFilesSingleField').html());

	// Loop over all fields and insert files (if any)
	for (var Field in ChannelFiles.Fields){
		for (var File in ChannelFiles.Fields[Field].files){

			if (typeof(ChannelFiles.Fields[Field]) == 'string') ChannelFiles.Fields[Field] = jQuery.parseJSON($.base64Decode(ChannelFiles.Fields[Field]));

			// Is this the last one? So we can trigger sync..
			var Sync = ((ChannelFiles.Fields[Field].files.length -1) == File) ? true : false;

			ChannelFiles.AddNewFile(ChannelFiles.Fields[Field].files[File], ChannelFiles.Fields[Field].files[File].field_id, Sync);
		}
	}

	// Submit Entry Stop
	jQuery('#submit_button').click(function(Event){
		if (ChannelFiles.CFields.find('tr.FilesQueue div.Done').length > 0){
			jQuery(Event.target).parent(':first').append('<div class="ChannelFilesSubmitWait">' + ChannelFiles.LANG.submitwait + '</div>');
			setTimeout(function(){jQuery(Event.target).attr('disabled', 'disabled').css('background', '#DDE2E5');}, 300);
		}
	});

	if (typeof(Bwf) != 'undefined'){
		Bwf.bind('channel_files', 'previewClose', function(){
			ChannelFiles.RefreshFiles(Bwf._transitionInstance.draftExists);
		});
	}

});

//********************************************************************************* //

ChannelFiles.InitFields = function(){

	// Grabb all fields
	ChannelFiles.CFields = jQuery('.CFField');

	// Loop Over all fields
	ChannelFiles.CFields.each(function(index, elem){
		var FIELDID = jQuery(elem).closest('.CFField').attr('rel');

		// Activate Upload Handlers
		ChannelFiles.ActivateUploadHandlers(FIELDID);
	});

	// Open Error Handler
	ChannelFiles.CFields.find('div.UploadProgress').delegate('a.OpenError', 'click', function(){
		jQuery.colorbox({width:'90%', height:'90%', html:'<pre style="font-size:11px; font-family:helvetica,arial">'+ChannelFiles.LastError+'</pre>'});
		return false;
	});

	// Activate Sortable
	ChannelFiles.CFields.find('tbody.AssignedFiles').sortable({axis:'y', cursor:'move', opacity:0.6, handle:'.FileMove',
		helper:function(e, ui) {
			ui.children().each(function() {
				$(this).width($(this).width());
			});

			return ui;
		},
		update:ChannelFiles.SyncOrderNumbers
	});

	// Add some handlers
	ChannelFiles.CFields.find('tbody.AssignedFiles').delegate('a.FilePrimary', 'click', ChannelFiles.TogglePrimaryFile);
	ChannelFiles.CFields.find('tbody.AssignedFiles').delegate('a.FileDel', 'click', ChannelFiles.DeleteFile);
	ChannelFiles.CFields.find('tbody.AssignedFiles').delegate('.FileReplace', 'click', ChannelFiles.OpenFileReplace);
	ChannelFiles.CFields.find('div.StoredFiles').click(ChannelFiles.OpenStoredFiles);
	ChannelFiles.CFields.find('div.ImportFiles').click(ChannelFiles.OpenImportFiles);
};

//********************************************************************************* //

ChannelFiles.ActivateUploadHandlers = function(FIELD_ID){

	if (typeof(ChannelFiles.Fields['Field_'+FIELD_ID]) == 'string') ChannelFiles.Fields['Field_'+FIELD_ID] = jQuery.parseJSON($.base64Decode(ChannelFiles.Fields['Field_'+FIELD_ID]));

	// Enable Hybrid Upload?
	if (ChannelFiles.Fields['Field_'+FIELD_ID].settings.hybrid_upload == 'yes')
	{
		var input = document.createElement('input');
	    input.type = 'file';

	    if ('multiple' in input && typeof File != "undefined" && typeof (new XMLHttpRequest()).upload != "undefined" ) {
	    	ChannelFiles.HTML5.Init(FIELD_ID);
	    }
	    else {
	    	ChannelFiles.SWFUPLOAD.Init(FIELD_ID);
    	}
	}
	else {
		ChannelFiles.SWFUPLOAD.Init(FIELD_ID);
	}
};

//********************************************************************************* //

ChannelFiles.AddFileToQueue = function(FIELD_ID, Filename, IDstr, RELstr){

	// Can we add more files?
	var Remaining = ChannelFiles.FilesRemaining(FIELD_ID);
	Remaining = (Remaining - jQuery('#ChannelFiles_'+FIELD_ID).find('.FilesQueue .Queued').length);

	if (Remaining > 0) {
		var File = jQuery('<div class="File Queued" id="' + IDstr + '" rel="'+RELstr+'">' + Filename + '</div>');
		jQuery('#ChannelFiles_'+FIELD_ID).find('.FilesQueue').css('display', 'table-row').children('th').append(File);
		return true;
	}
	else return false;
};


//********************************************************************************* //

ChannelFiles.UploadProgress = function(FIELD_ID, loaded, total, speed){
	var ProgressBox = jQuery('#ChannelFiles_' + FIELD_ID).find('div.UploadProgress').show();
	var PercentUploaded = loaded / (total / 100);

	ProgressBox.children('.progress').css('width', PercentUploaded.toFixed(2) + '%');

	ProgressBox.find('.percent').html(PercentUploaded.toFixed(2) + '%');
	if (speed) ProgressBox.find('.speed').html(SWFUpload.speed.formatBPS(speed / 10));
	ProgressBox.find('.bytes .uploadedBytes').html(SWFUpload.speed.formatBytes(loaded));
	ProgressBox.find('.bytes .totalBytes').html('/ ' + SWFUpload.speed.formatBytes(total));
};

//********************************************************************************* //

ChannelFiles.AddNewFile = function(JSONOBJ, FIELD_ID, Sync){

	// Lets add some keys!
	if (!JSONOBJ.file_id) JSONOBJ.file_id = '0';
	if (!JSONOBJ.description) JSONOBJ.description = '';
	if (!JSONOBJ.category) JSONOBJ.category = '';
	if (!JSONOBJ.cffield_1) JSONOBJ.cffield_1 = '';
	if (!JSONOBJ.cffield_2) JSONOBJ.cffield_2 = '';
	if (!JSONOBJ.cffield_3) JSONOBJ.cffield_3 = '';
	if (!JSONOBJ.cffield_4) JSONOBJ.cffield_4 = '';
	if (!JSONOBJ.cffield_5) JSONOBJ.cffield_5 = '';
	if (!JSONOBJ.primary) JSONOBJ.primary = '0';
	if (!JSONOBJ.link_file_id) JSONOBJ.link_file_id = '0';

	// Lets store it for POST
	JSONOBJ.json_data = JSON.stringify(JSONOBJ);

	// Add field_name
	JSONOBJ.field_name = ChannelFiles.Fields['Field_'+FIELD_ID].field_name;

	// Is it primary?
	if (JSONOBJ.primary == 1) JSONOBJ.is_primary = true;

	// Is it linked?
	if (JSONOBJ.link_file_id > 0) JSONOBJ.is_linked = true;

	// Loop through all columns
	for (var column in ChannelFiles.Fields['Field_'+FIELD_ID].settings.columns){
		if (ChannelFiles.Fields['Field_'+FIELD_ID].settings.columns[column] != false){
			// It's not empty, so lets add it
			JSONOBJ['show_'+column] = true;
		}
	}

	if (typeof(ChannelFiles.Fields['Field_'+FIELD_ID].settings.show_download_btn) != 'undefined'){
		if (ChannelFiles.Fields['Field_'+FIELD_ID].settings.show_download_btn == 'yes') JSONOBJ.show_download_btn = true;
	}

	if (typeof(ChannelFiles.Fields['Field_'+FIELD_ID].settings.show_file_replace) != 'undefined'){
		if (ChannelFiles.Fields['Field_'+FIELD_ID].settings.show_file_replace == 'yes') JSONOBJ.show_file_replace = true;
	}

	if (JSONOBJ.is_linked == true) {
		JSONOBJ.show_file_replace = false;
	}

	// Kill Titles and url_titles!
	JSONOBJ.file_title = JSONOBJ.title;
	JSONOBJ.file_url_title = JSONOBJ.url_title;

	delete JSONOBJ.title; delete JSONOBJ.url_title;

	// Render the new row
	var HTML = ChannelFiles.Templates['TableTR'].render(JSONOBJ);

	// Add it
	jQuery('#ChannelFiles_'+FIELD_ID).find('tbody.AssignedFiles').append(HTML);
	jQuery('#ChannelFiles_'+FIELD_ID).find('tr.NoFiles').hide();

	// Activate jEditable
	ChannelFiles.ActivateEditable(jQuery('#ChannelFiles_'+FIELD_ID).find('tbody.AssignedFiles').find('tr.File:last'), FIELD_ID);

	if (Sync === false) return;

	// Sync those numbers
	ChannelFiles.SyncOrderNumbers(FIELD_ID);
	ChannelFiles.FilesRemaining(FIELD_ID);
};

//********************************************************************************* //

ChannelFiles.FilesRemaining = function(FIELD_ID){
	var TotalFiles = jQuery('#ChannelFiles_'+FIELD_ID).find('.AssignedFiles .File').not('.deleted').length;
	var FileLimit = ChannelFiles.Fields['Field_'+FIELD_ID].settings.file_limit;
	var FilesRemaining = (FileLimit - TotalFiles);

	var RemainingColor = (FilesRemaining > 0) ? 'green' : 'red';

	jQuery('#ChannelFiles_'+FIELD_ID).find('.FileLimit .remain').css('color', RemainingColor).text(FilesRemaining);

	return FilesRemaining;
};

//********************************************************************************* //

ChannelFiles.SyncOrderNumbers = function(FIELD_ID){

	// Is it an event object? Get the Field_id
	if (typeof(FIELD_ID) == 'object'){
		FIELD_ID = jQuery(FIELD_ID.target).closest('.CFField').attr('rel');
	}

	var Count = 0;

	// Loop over all Files
	jQuery('#ChannelFiles_'+FIELD_ID).find('tbody.AssignedFiles').find('tr.File').each(function(Fileindex, Elem){
		var FILETD = jQuery(Elem);

		if (FILETD.hasClass('deleted') == false){
			 Count++;
		}

		// Insert the row number (most of the time it's the first column)
		FILETD.find('td.num').html(Count);

		//jQuery(FILETD).find('a.FilePrimary').attr('rel', 'CF_' + (Fileindex+1));

		// Find all form inputs
		jQuery(FILETD).find('input, textarea, select').each(function(){
			// Get it's attribute and change it
			attr = jQuery(this).attr('name').replace(/\[files\]\[.*?\]/, '[files][' + (Fileindex+1) + ']');
			jQuery(this).attr('name', attr);
		});
	});

	// Odd/Even
	jQuery('#ChannelFiles_'+FIELD_ID).find('tr.File').removeClass('odd');
	jQuery('#ChannelFiles_'+FIELD_ID).find('tr.File:odd').addClass('odd');
};

//********************************************************************************* //

ChannelFiles.TogglePrimaryFile = function(Event){

	// Store!
	var TableParent = jQuery(Event.target).closest('table');
	var Parent = jQuery(Event.target).closest('tr');

	// Are we unchecking?
	var Uncheck = false;
	if ( jQuery(Event.target).hasClass('StarIcon') ) Uncheck = true;

	// Find all files and remove the StarClass & Cover Value
	TableParent.find('tbody.AssignedFiles').find('tr.File').each(function(i, elem){
		jQuery(elem).removeClass('PrimaryFile').find('a.StarIcon').removeClass('StarIcon').addClass('StarGreyIcon');
		ChannelFiles.ChangeFile('primary', '0', jQuery(elem));
	});


	if (Uncheck == true) return false;

	ChannelFiles.ChangeFile('primary', '1', Parent);

	// Add the star status to the clicked file
	Parent.addClass('PrimaryFile').find('.StarGreyIcon').removeClass('StarGreyIcon').addClass('StarIcon');

	return false;
};

//********************************************************************************* //

ChannelFiles.ChangeFile = function(attr, value, file){

	// Double check!
	if (typeof(file) != 'object') return;
	if (file.length == 0) return;

	// Grab the json
	var jsondata = file.find('textarea.FileData').html();
	jsondata = JSON.parse(jsondata);

	// Set the attribute
	jsondata[attr] = value;

	// Put it back!
	file.find('textarea.FileData').html(JSON.stringify(jsondata));
};

//********************************************************************************* //

ChannelFiles.DeleteFile = function(Event){

	Event.preventDefault();

	if ( $(Event.target).hasClass('FileLinked') == true){
		confirm_delete = confirm(ChannelFiles.LANG.unlink_file);
		if (confirm_delete == false) return false;
	}
	else {
		confirm_delete = confirm(ChannelFiles.LANG.unlink_file);
		if (confirm_delete == false) return false;
	}

	// Store!
	var Parent = $(Event.target).closest('tr');
	var FIELD_ID = $(Event.target).closest('div.CFField').attr('rel');

	var jsondata = Parent.find('textarea.FileData').html();
	jsondata = JSON.parse(jsondata);

	if (typeof(jsondata.file_id) == 'undefined') jsondata.file_id = 0;

	if (jsondata.file_id > 0) ChannelFiles.ChangeFile('delete', '1', Parent);

	// Add the star status to the clicked file
	Parent.addClass('deleted').fadeOut('slow', function(){
		if (jsondata.file_id < 1) Parent.remove();
		ChannelFiles.SyncOrderNumbers(FIELD_ID);
		ChannelFiles.FilesRemaining(FIELD_ID);
	});

	return false;
};

//********************************************************************************* //

ChannelFiles.OpenStoredFiles = function(Event){

	var Target = $(Event.target).closest('div.CFField');
	var Parent = Target.find('tr.SearchFiles');
	var FieldID = Target.attr('rel');
	var Timer;

	// Is it hidden already?
	if (Parent.css('display') == 'none'){
		Parent.css('display', '');
	}
	else {
		Parent.css('display', 'none');
		return false;
	}

	// Did we already binded our events?
	if (Parent.data('event_binded') != true){
		Parent.find('.filefilter .filter select').change(function(Event){
			ChannelFiles.StoredFilesLoadFiles(Event, FieldID);
		});

		// Activate Filter
		Parent.find('td.filefilter div.filter input').keyup(function(Event){
			Timer && clearTimeout(Timer);
			Timer = setTimeout(function(){
				ChannelFiles.StoredFilesLoadFiles(Event, FieldID);
			}, 350);
		});

		// Disable Enter!
		Parent.find('td.filefilter div.filter input').keydown(function(event){ if (event.keyCode == 13) return false;  });

		// Add the click Event
		Parent.find('div.results').delegate('.rFile a', 'click', ChannelFiles.StoredFilesAddFile);

		// Store our marker
		Parent.data('event_binded', true);
	}

	// Load files..
	ChannelFiles.StoredFilesLoadFiles(false, FieldID);

	return false;
};

//********************************************************************************* //

ChannelFiles.StoredFilesLoadFiles = function(Event, FieldID){

	var SearchFilesBox = jQuery('#ChannelFiles_'+FieldID).find('tr.SearchFiles');

	// Show the loading message
	SearchFilesBox.find('p.Loading').show();

	// Lets store some params
	var Params = new Object();
	Params.field_id = FieldID;
	Params.entry_id = ChannelFiles.entry_id;
	Params.ajax_method = 'search_files';
	Params.XID = EE.XID;
	Params.site_id = ChannelFiles.site_id;
	Params.keywords = 'JUST_OPEN';
	Params.limit = 20;

	if (Event != false){
		// Grab all input fields
		SearchFilesBox.find('div.filter').find('input[type=text], input[type=hidden]').each(function(){
			Params[jQuery(this).attr('rel')] = jQuery(this).val();
		});
	}

	jQuery.ajax({
		url: ChannelFiles.AJAX_URL, type: 'POST', data: Params, dataType: 'html',
		success: function(rData){

			// Hide the spinner
			SearchFilesBox.find('p.Loading').css('display', 'none');

			// The results!
			SearchFilesBox.find('div.results_wrapper div.results').empty().html(rData);
		}
	});

};

//********************************************************************************* //

ChannelFiles.StoredFilesAddFile = function(Event){
	Event.preventDefault();

	var TargetBox = jQuery(Event.target).closest('div.CFField');
	var FIELD_ID = TargetBox.attr('rel');

	// Can we add more files?
	var Remaining = ChannelFiles.FilesRemaining(FIELD_ID);
	Remaining = (Remaining - jQuery('#ChannelFiles_'+FIELD_ID).find('.FilesQueue .Queued').length);
	if (Remaining < 1) return false;

	// Add the loading class
	jQuery(Event.target).addClass('loading');

	var Params = new Object();
	Params.field_id = FIELD_ID;
	Params.file_id = jQuery(Event.target).attr('rel');
	Params.ajax_method = 'add_linked_file';
	Params.XID = EE.XID;

	jQuery.ajax({
		url: ChannelFiles.AJAX_URL, type: 'POST', data: Params, dataType: 'json',
		success: function(rData){
			ChannelFiles.AddNewFile(rData, FIELD_ID);

			jQuery(Event.target).closest('.rFile').slideUp('slow', function(){jQuery(this).remove();});

		}
	});
};

//********************************************************************************* //

ChannelFiles.DisableEnter = function(Event){
	if (Event.which == 13)	{
		jQuery(Event.target).closest('.CFField').find('.SearchFilesBTN').click();
		return false;
	}
};

//********************************************************************************* //

ChannelFiles.ClearFileSearch = function(Event){

	var TargetBox = jQuery(Event.target).closest('.CFField');

	TargetBox.find('.FilesResult').slideToggle('slow', function(){ jQuery(this).empty(); });

	return false;

};

//********************************************************************************* //

ChannelFiles.ActivateEditable = function(TargetTR, FIELD_ID){

	var jEvent = ChannelFiles.Fields['Field_'+FIELD_ID].settings.jeditable_event;

	TargetTR.find('td[rel=title]').editable(ChannelFiles.EditFileDetails, {type:'text', placeholder: ChannelFiles.LANG[jEvent+'2edit'], onedit:ChannelFiles.ActivateLiveUrlTitle, event: jEvent, onblur: 'submit'});
	TargetTR.find('td[rel=url_title]').editable(ChannelFiles.EditFileDetails, {type:'text', placeholder: ChannelFiles.LANG[jEvent+'2edit'], event: jEvent, onblur: 'submit'});
	TargetTR.find('td[rel=description]').editable(ChannelFiles.EditFileDetails, {type:'textarea', placeholder: ChannelFiles.LANG[jEvent+'2edit'], rows:2, event: jEvent, onblur:'submit'});
	TargetTR.find('td[rel=category]').editable(ChannelFiles.EditFileDetails, {type:'select', data: ChannelFiles.Fields['Field_'+FIELD_ID].categories, placeholder: ChannelFiles.LANG[jEvent+'2edit'], event: jEvent, onblur:'submit'});
	TargetTR.find('td[rel=cffield_1]').editable(ChannelFiles.EditFileDetails, {type:'text', placeholder: ChannelFiles.LANG[jEvent+'2edit'], event: jEvent, onblur:'submit'});
	TargetTR.find('td[rel=cffield_2]').editable(ChannelFiles.EditFileDetails, {type:'text', placeholder: ChannelFiles.LANG[jEvent+'2edit'], event: jEvent, onblur:'submit'});
	TargetTR.find('td[rel=cffield_3]').editable(ChannelFiles.EditFileDetails, {type:'text', placeholder: ChannelFiles.LANG[jEvent+'2edit'], event: jEvent, onblur:'submit'});
	TargetTR.find('td[rel=cffield_4]').editable(ChannelFiles.EditFileDetails, {type:'text', placeholder: ChannelFiles.LANG[jEvent+'2edit'], event: jEvent, onblur:'submit'});
	TargetTR.find('td[rel=cffield_5]').editable(ChannelFiles.EditFileDetails, {type:'text', placeholder: ChannelFiles.LANG[jEvent+'2edit'], event: jEvent, onblur:'submit'});
};

//********************************************************************************* //

ChannelFiles.EditFileDetails = function(value, settings){
	var InputClass = jQuery(this).attr('rel');

	ChannelFiles.ChangeFile(InputClass, value, jQuery(this).closest('tr.File'));

	return value;
};

//********************************************************************************* //

ChannelFiles.ActivateLiveUrlTitle = function(options, parentTD){
	var parentTR = $(parentTD).closest('tr.File');
	setTimeout(function(){
		$(parentTD).find('input[name=value]').liveUrlTitle(parentTR.find('td[rel=url_title]'), {separator: EE.publish.word_separator});
		$(parentTD).find('input[name=value]').liveUrlTitle(parentTR.find('.inputs .url_title'), {separator: EE.publish.word_separator});
	}, 500);
};

//********************************************************************************* //

ChannelFiles.OpenImportFiles = function(Event){
	Event.preventDefault();

	var FIELD_ID = jQuery(Event.target).closest('div.CFField').attr('rel');
	var Remaining = ChannelFiles.FilesRemaining(FIELD_ID);

	jQuery.colorbox({
		href: ChannelFiles.AJAX_URL + '&ajax_method=import_files_ui&field_id='+ FIELD_ID + '&remaining='+Remaining,
		onComplete: function(){

			// Store it
			var Elem = jQuery('#cboxContent');

			Elem.find('.ImportFilesBtn').click(function(){
				// Show the indicator
				Elem.find('.ImportFilesBtn span').css('display', 'inline-block');

				// Prepare Params
				var Params = {};
				Params.ajax_method = 'import_files';
				Params.field_id = Elem.find('.CFTable').attr('rel');
				Params.key = ChannelFiles.Fields['Field_'+FIELD_ID].key;
				Params.files = [];

				// Loop over all checkboxes
				jQuery('#cboxContent').find('input[type=checkbox]:checked').each(function(i, el){
					Params.files.push(el.value);
				});

				jQuery.post(ChannelFiles.AJAX_URL, Params, function(rData){

					for (var File in rData.files){
						ChannelFiles.AddNewFile(rData.files[File], FIELD_ID);
					}

					// Lets fake it so we get the submit wait message
					jQuery('#ChannelFiles_'+FIELD_ID).find('tr.FilesQueue').append('<div class="Done"></div>');

					jQuery.colorbox.close();
				}, 'json');
			});
		}
	});

};

//********************************************************************************* //








//********************************************************************************* //
ChannelFiles.HTML5.Init = function(FIELD_ID) {

	// Create an Input
	// opacity: 0; filter:alpha(opacity: 0); IS REQUIRED!
	// So we can click the placeholder and still trigger the dialog
	var input = document.createElement('input');
	//input.setAttribute('multiple', 'multiple');
	input.setAttribute('type', 'file');
    input.setAttribute('name', 'channel_files_file');
    input.setAttribute('id', 'cf_upload_btn_'+FIELD_ID);
    input.setAttribute('style', 'width:100px; position:absolute; cursor:pointer; top:0; left:0; opacity: 0; filter:alpha(opacity: 0);');

    var iOS = /(iPad|iPhone|iPod)/g.test( window.navigator.userAgent );
    //alert(iOS);
    if (!iOS) {
    	input.setAttribute('multiple', 'multiple');
    	//input.removeAttribute('multiple');
    }

    // Replace the placeholder with the input
    jQuery('#ChannelFilesSelect_'+FIELD_ID).replaceWith(input);

    // Add the Change event (FileDialogClosed)
    jQuery('#cf_upload_btn_'+FIELD_ID).change(ChannelFiles.HTML5.FileDialogClosed);

    // Cancel all drop of files, so browser doesn't redirect!
    jQuery(document.body).bind('dragover', function(e) {e.preventDefault(); return false;});
    jQuery(document.body).bind('drop', function(e) {e.preventDefault(); return false;});


	var FIELD = jQuery('#ChannelFiles_' + FIELD_ID);

	// STEP 1: Bind DragOver to the Main Field
	FIELD.bind('dragover', function(Event){
		Event.preventDefault(); Event.stopPropagation();
		jQuery('#CFDragDrop_'+FIELD_ID).css({width:FIELD.width(), height:FIELD.height()}).show();
	});

	// STEP 2: Bind DragLeave To the DragDrop Wrapper that shows up
	jQuery('#CFDragDrop_'+FIELD_ID).bind('dragleave', function(Event){
		Event.preventDefault(); Event.stopPropagation();
		jQuery('#CFDragDrop_'+FIELD_ID).hide();
	});

	// STEP 3: Bind the DROP to the Main Field
	FIELD.bind('drop', function(Event){
		Event.stopPropagation(); Event.preventDefault();

		// Remove all queued items!
		ChannelFiles.HTML5.CleanQueue(FIELD_ID);

		// Get the files and store them in our main field object
		ChannelFiles.Fields['Field_'+FIELD_ID].FilesDropped = Event.originalEvent.dataTransfer.files;

		// Hide the drop wrapper
		jQuery('#CFDragDrop_'+FIELD_ID).hide();

		// Loop through all files
		for (var i=0; i<ChannelFiles.Fields['Field_'+FIELD_ID].FilesDropped.length; i++) {

			// File extension check
			if (ChannelFiles.HTML5.CheckFileExtension(FIELD_ID, ChannelFiles.Fields['Field_'+FIELD_ID].FilesDropped[i].name) == false) continue;

			// Add it to the queue
			ChannelFiles.AddFileToQueue(FIELD_ID, ChannelFiles.Fields['Field_'+FIELD_ID].FilesDropped[i].name, 'File_'+i, i);
	    }

		// Trigger Start Upload!
		ChannelFiles.HTML5.UploadStart(FIELD_ID);

	});

};

//********************************************************************************* //

ChannelFiles.HTML5.FileDialogClosed = function(Event) {

	// We need the Fiel ID
	var FIELD_ID = jQuery(Event.target).closest('.CFField').attr('rel');

	// Loop through all files
	for (var i=0; i<Event.target.files.length; i++) {

		// File extension check
		if (ChannelFiles.HTML5.CheckFileExtension(FIELD_ID, Event.target.files[i].name) == false) continue;

		// Add it to the queue
		ChannelFiles.AddFileToQueue(FIELD_ID, Event.target.files[i].name, 'File_'+i, i);
    }

	ChannelFiles.HTML5.UploadStart(FIELD_ID);
};

//********************************************************************************* //

ChannelFiles.HTML5.CheckFileExtension = function(FIELD_ID, Filename) {
	var Allowed = ChannelFiles.Fields['Field_'+FIELD_ID].settings.file_extensions;
	var Extension = Filename.split('.').pop();

	// Check for (allow all)
	if (Allowed == '*.*') return true;

	// Parse it! (example: *.zip;*.rar)
	Allowed = Allowed.replace(/\*\./g, '').split(';');

	// Extension in there?
	if (Allowed.indexOf(Extension) >= 0) return true;

	return false;
};

//********************************************************************************* //

ChannelFiles.HTML5.UploadStart = function(FIELD_ID) {
	ChannelFiles.UploadError = false;

	var UploadURL = ChannelFiles.UPLOAD_URL + '&field_id='+ FIELD_ID + '&key=' + ChannelFiles.Fields['Field_'+FIELD_ID].key;

	// Find the next on the line!
	var FileQueue = jQuery('#ChannelFiles_'+FIELD_ID).find('.FilesQueue').find('.Queued:first');

	// Nothing found? Quit!
	if (FileQueue.length == 0) {

		// Hide the progress box
		jQuery('#ChannelFiles_' + FIELD_ID).find('div.UploadProgress').hide();

		// Empty the input field
		jQuery(document.getElementById('cf_upload_btn_' + FIELD_ID)).val('');
		ChannelFiles.Fields['Field_'+FIELD_ID].FilesDropped = null;
		jQuery('#ChannelFiles_' + FIELD_ID).trigger('UploadComplete');
		return false;
	}

	jQuery('#ChannelFiles_' + FIELD_ID).trigger('UploadStart');

	// Show StopUpload
	jQuery('#ChannelFiles_' + FIELD_ID).find('.StopUpload').show();

	// Mark it as uploading..
	FileQueue.removeClass('Queued').addClass('Uploading');

	// What key was it?
	var Index = FileQueue.attr('rel');

	// Grab the file object (check if it's a dropped file one before)
	if (ChannelFiles.Fields['Field_'+FIELD_ID].FilesDropped) var File = ChannelFiles.Fields['Field_'+FIELD_ID].FilesDropped[Index];
	else var File = document.getElementById('cf_upload_btn_' + FIELD_ID).files[Index];

	var xhr = new XMLHttpRequest();

	// Log Progress Events!
	xhr.upload['onprogress'] = function(rpe) {
		ChannelFiles.UploadProgress(FIELD_ID, rpe.loaded, rpe.total);
	};

	// When done!
	xhr.onload = function(load){ ChannelFiles.HTML5.UploadReponse(load, xhr, File, FIELD_ID, FileQueue); };

	xhr.open('post', UploadURL, true);
	xhr.setRequestHeader('Cache-Control', 'no-cache');
	xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
	xhr.setRequestHeader('X-File-Name', File.name);
	xhr.setRequestHeader('X-File-Size', File.fileSize);

	//xhr.setRequestHeader("Content-Type", "multipart/form-data");
	//xhr.send(File);


	if (window.FormData) {
		var f = new FormData();
		f.append('channel_files_file', File);
		xhr.send(f);
	}
	else if (File.getAsBinary || window.FileReader) {
		var boundary = '------multipartformboundary' + (new Date).getTime();
		var dashdash = '--';
		var crlf = '\r\n';

		/* Build RFC2388 string. */
		var builder = '';

		builder += dashdash;
		builder += boundary;
		builder += crlf;

		builder += 'Content-Disposition: form-data; name="channel_files_file"';
		builder += '; filename="' + File.name + '"';
		builder += crlf;

		builder += 'Content-Type: application/octet-stream';
		builder += crlf;
		builder += crlf;

		/* Append binary data. */
		if (window.FileReader) {
			reader = new FileReader();
			reader.onload = function(evt) {
				builder += evt.target.result;
				builder += crlf;

				/* Write boundary. */
				builder += dashdash;
				builder += boundary;
				builder += crlf;

				builder += dashdash;
				builder += boundary;
				builder += dashdash;
				builder += crlf;

				xhr.setRequestHeader('content-type', 'multipart/form-data; boundary=' + boundary);
				xhr.sendAsBinary(builder);
			};
			reader.readAsBinaryString(File);
		}
		else if (typeof(File.getAsBinary) != 'undefined') {
			builder += File.getAsBinary();
			builder += crlf;

			/* Write boundary. */
			builder += dashdash;
			builder += boundary;
			builder += crlf;

			builder += dashdash;
			builder += boundary;
			builder += dashdash;
			builder += crlf;

			xhr.setRequestHeader('content-type', 'multipart/form-data; boundary=' + boundary);
			xhr.sendAsBinary(builder);
		}
		else {
			alert('HTML5 Upload Failed! (FILEREADER & GetAsBinary are not supported)');
			return false;
		}
	}
	else {
		alert('HTML5 Upload Failed! (FEATURES_NOT_SUPPORTED)');
		return false;
	}

	// Cancel Upload
	jQuery('#ChannelFiles_' + FIELD_ID).find('a.StopUpload').click(function(){
		jQuery('#ChannelFiles_' + FIELD_ID).find('tr.FilesQueue div.File').not('div.Done').each(function(index,elem){
			var Elem = jQuery(elem);
			xhr.abort();
			Elem.fadeOut(1400, function(){ Elem.remove(); });
			ChannelFiles.HTML5.CleanQueue(FIELD_ID);
			jQuery('#ChannelFiles_' + FIELD_ID).find('div.UploadProgress').hide();
		});
		return false;
	});

};

//********************************************************************************* //

ChannelFiles.HTML5.UploadReponse = function(load, xhr, File, FIELD_ID, FileQueue){

	// Sometimes we get the progressbar to 90%, so lets finish it here
	ChannelFiles.UploadProgress(FIELD_ID, File.size, File.size);

	// Show StopUpload
	jQuery('#ChannelFiles_' + FIELD_ID).find('.StopUpload').hide();

	// Was the request succesfull?
	if (xhr.status == 200){
		try {
			ServerData = JSON.parse(xhr.responseText);
		}
		// JSON ERROR!
		catch(errorThrown) {
			ChannelFiles.LastError = xhr.responseText;
			ChannelFiles.ErrorMSG(ChannelFiles.LANG.xhr_reponse_error, FIELD_ID);
			ChannelFiles.Debug("Server response was not as expected, probably a PHP error. \n RETURNED RESPONSE: \n" + xhr.responseText);
			FileQueue.removeClass('Uploading').addClass('Error');
			ChannelFiles.HTML5.CleanQueue(FIELD_ID);
			return false;
		}

		// Was the upload a success?
		if (ServerData.success == 'yes') {

			// Mark it as done
			FileQueue.removeClass('Uploading').addClass('Done');

			// Hide it?
			var TempCurrentFile = FileQueue;
			setTimeout(function(){
				if (TempCurrentFile.hasClass('Done') == false) return;
				TempCurrentFile.slideUp('slow');
			}, 2000);

			// Add the new file to the table
			ChannelFiles.AddNewFile(ServerData, FIELD_ID);

			// Start a new upload!
			ChannelFiles.HTML5.UploadStart(FIELD_ID);
		}
		else {
			FileQueue.removeClass('Uploading').addClass('Error');
			ChannelFiles.ErrorMSG(ServerData.body, FIELD_ID);
			ChannelFiles.Debug('ERROR: ' + ServerData.body);
			ChannelFiles.HTML5.CleanQueue(FIELD_ID);
		}

	}

	// Request was bad..do something about it
	else {
		ChannelFiles.LastError = xhr.responseText;
		ChannelFiles.ErrorMSG(ChannelFiles.LANG.xhr_status_error, FIELD_ID);
		ChannelFiles.Debug("Upload request failed, no HTTP 200 Return Code! \n RETURNED RESPONSE: \n" + xhr.responseText);
		FileQueue.removeClass('Uploading').addClass('Error');
		ChannelFiles.HTML5.CleanQueue(FIELD_ID);
	}
};

//********************************************************************************* //

ChannelFiles.HTML5.CleanQueue = function(FIELD_ID) {

	// Empty the input field
	jQuery(document.getElementById('cf_upload_btn_' + FIELD_ID)).val('');

	// Remove all queue files
	jQuery('#ChannelFiles_'+FIELD_ID).find('.FilesQueue').find('.Queued').slideUp('slow', function(){ jQuery(this).remove(); });

	// Also here
	ChannelFiles.Fields['Field_'+FIELD_ID].FilesDropped = null;

	jQuery('#ChannelFiles_' + FIELD_ID).trigger('UploadComplete');
};

//********************************************************************************* //











//********************************************************************************* //

ChannelFiles.SWFUPLOAD.Init = function(FIELD_ID) {

	// When the field is hidden by default, the Flash object's width is 0 so you cannot do anything with it
	// Here we force the width, by getting the width of the parent
	var ButtonWith = 120;
	if (jQuery('#ChannelFilesSelect_'+FIELD_ID).is(':visible') != false){
		ButtonWith = (jQuery('#ChannelFilesSelect_'+FIELD_ID).parent().width() + 10);
	}

	ChannelFiles.SWF[FIELD_ID] = new SWFUpload({

		// Backend Settings
		flash_url : ChannelFiles.ThemeURL + 'swfupload.swf',
		upload_url: ChannelFiles.UPLOAD_URL,
		post_params: {
			ajax_method: 'upload_file',
			field_id: FIELD_ID,
			site_id: ChannelFiles.site_id,
			key: ChannelFiles.Fields['Field_'+FIELD_ID].key,
			XID: EE.XID,
			flash_upload: 'yes'
		},
		file_post_name: 'channel_files_file',
		prevent_swf_caching: true,
		assume_success_timeout: 0,

		// File Upload Settings
		file_size_limit : 0,	// Unlimited
		file_types : ChannelFiles.Fields['Field_'+FIELD_ID].settings.file_extensions,
		file_types_description : 'All Files',
		file_upload_limit : 0,
		file_queue_limit : 0,

		// Event Handler Settings
		swfupload_preload_handler : function(){},
		swfupload_load_failed_handler : function(){},
		file_dialog_start_handler : function(){},
		file_queued_handler : ChannelFiles.SWFUPLOAD.QueuedHandler,
		file_queue_error_handler : function(){},
		file_dialog_complete_handler : ChannelFiles.SWFUPLOAD.DialogCompleteHandler,
		upload_resize_start_handler : function(){},
		upload_start_handler : ChannelFiles.SWFUPLOAD.StartHandler,
		upload_progress_handler : ChannelFiles.SWFUPLOAD.ProgressHandler,
		upload_error_handler : function(file, error, message){
			// Sometimes we cancel the upload because of an error, no need to display "Cancelled error"
			if (error == '-270') return;
			if (error == '-280') return;

			jQuery('#ChannelFiles_' + FIELD_ID).find('.FilesQueue .Uploading:first').removeClass('Uploading').addClass('Error');
			ChannelFiles.ErrorMSG('Upload Failed:' + error + ' MSG:' + message, FIELD_ID);
			ChannelFiles.Debug('Upload Failed:' + error + ' MSG:' + message);
		},
		upload_success_handler : ChannelFiles.SWFUPLOAD.SuccessHandler,
		upload_complete_handler : function(){},

		// Button Settings
		button_image_url : '', // Relative to the SWF file
		button_placeholder_id : 'ChannelFilesSelect_'+FIELD_ID,
		button_width: ButtonWith,
		button_height: 16,
		button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
		button_cursor: SWFUpload.CURSOR.HAND,
		button_action: SWFUpload.BUTTON_ACTION.SELECT_FILES,

		// Custom Settings
		custom_settings : {
			field_id : FIELD_ID
		},

		// Debug Settings
		debug: false
	});

	// Cancel Upload
	jQuery('#ChannelFiles_' + FIELD_ID).find('a.StopUpload').click(function(){

		jQuery('#ChannelFiles_' + FIELD_ID).find('tr.FilesQueue div.File').not('div.Done').each(function(index,elem){
			var Elem = jQuery(elem);
			ChannelFiles.SWF[FIELD_ID].cancelUpload(Elem.attr('id'), true);
			Elem.fadeOut(1400, function(){
				Elem.remove();
				jQuery('#ChannelFiles_' + FIELD_ID).find('div.UploadProgress').hide();
			});
			ChannelFiles.SWFUPLOAD.CleanQueue(FIELD_ID);
		});

		jQuery(this).hide();

		return false;
	});
};

//********************************************************************************* //

ChannelFiles.SWFUPLOAD.QueuedHandler = function(File) {

	// Attempt to add file to Queue
	if (ChannelFiles.AddFileToQueue(this.customSettings.field_id, File.name, File.id, '') == false){

		// If fails, cancel this upload
		this.cancelUpload(File.id, false);
		return false;
	}
};

//********************************************************************************* //

ChannelFiles.SWFUPLOAD.DialogCompleteHandler = function(FilesSelected, FilesQueued, TotalFilesQueued) {
	// Reset Errors
	ChannelFiles.LastError = '';
	ChannelFiles.UploadError = false;



	// Start Upload!
	this.startUpload();
};

//********************************************************************************* //

ChannelFiles.SWFUPLOAD.StartHandler = function(File) {

	var FIELD_ID = this.customSettings.field_id;

	// Was there an error? Stop! And cancel all
	if (ChannelFiles.UploadError == true) {
		ChannelFiles.SWFUPLOAD.CleanQueue(FIELD_ID);
		return false;
	}

	ChannelFiles.LastError = '';

	// Add the UploadingClass
	jQuery('#' + File.id).removeClass('Queued').addClass('Uploading');

	// Show StopUpload
	jQuery('#ChannelFiles_' + FIELD_ID).find('.StopUpload').show();


	jQuery('#ChannelFiles_' + FIELD_ID).trigger('UploadStart');
};

//********************************************************************************* //

ChannelFiles.SWFUPLOAD.ProgressHandler = function(file, bytesLoaded, bytesTotal) {
	ChannelFiles.UploadProgress(this.customSettings.field_id, file.sizeUploaded, file.size, file.averageSpeed);
};

//********************************************************************************* //

ChannelFiles.SWFUPLOAD.SuccessHandler = function(File, serverData, response) {

	// Store the current file queue
	var FileQueue = jQuery('#' + File.id);
	var FIELD_ID = this.customSettings.field_id;

	try {
		// Parse the JSON, if it failed we have error
		var rData = JSON.parse(serverData);
	}
	catch(errorThrown) {
		ChannelFiles.LastError = serverData;
		ChannelFiles.ErrorMSG(ChannelFiles.LANG.xhr_reponse_error, FIELD_ID);
		ChannelFiles.Debug("Server response was not as expected, probably a PHP error. \n RETURNED RESPONSE: \n" + serverData);
		FileQueue.removeClass('Uploading').addClass('Error');
		ChannelFiles.SWFUPLOAD.CleanQueue(FIELD_ID);
		return false;
	}


	// Was it an success?
	if (rData.success == 'yes') {

		// Mark it as done
		FileQueue.removeClass('Uploading').addClass('Done');

		// Hide it?
		var TempCurrentFile = FileQueue;
		setTimeout(function(){
			if (TempCurrentFile.hasClass('Done') == false) return;
			TempCurrentFile.slideUp('slow');
		}, 2000);

		// Add the new file to the table
		ChannelFiles.AddNewFile(rData, FIELD_ID);

		// Hide the progressbox! when done
		if (jQuery('#ChannelFiles_' + FIELD_ID).find('.FilesQueue .Queued:first').length < 1) jQuery('#ChannelFiles_' + FIELD_ID).find('.UploadProgress').css('display', 'none');
	}

	// Upload uploaded but returned success=no
	else {
		FileQueue.removeClass('Uploading').addClass('Error');
		ChannelFiles.ErrorMSG(rData.body, FIELD_ID);
		ChannelFiles.Debug('ERROR: ' + rData.body);
		ChannelFiles.SWFUPLOAD.CleanQueue(FIELD_ID);
	}

	// Hide StopUpload
	jQuery('#ChannelFiles_' + FIELD_ID).find('.StopUpload').hide();

};

//********************************************************************************* //

ChannelFiles.SWFUPLOAD.CleanQueue = function(FIELD_ID) {

	jQuery('#ChannelFiles_'+FIELD_ID).find('.FilesQueue').find('.Queued').each(function(i, Element){
		// Cancel the file
		this.cancelUpload(Element.id, false);

		// Kill it
		jQuery(this).remove();
	});

	jQuery('#ChannelFiles_' + FIELD_ID).trigger('UploadComplete');
};

//********************************************************************************* //

ChannelFiles.SWFUPLOAD.CompleteHandle = function(){
	var FIELD_ID = this.customSettings.field_id;
	jQuery('#ChannelFiles_' + FIELD_ID).trigger('UploadComplete');
};

//********************************************************************************* //

ChannelFiles.RefreshFiles = function(draft){

	if (ChannelFiles.Refreshing == true) return;

	ChannelFiles.Refreshing = true;

	var Params = {};
	Params.draft = (draft == true) ? 'yes' : 'no';
	Params.ajax_method = 'refresh_files';
	Params.entry_id = $('input[name=entry_id]').val();

	// Loop over all fields
	ChannelFiles.CFields.each(function(i, e){
		Params.field_id = $(e).attr('rel');

		$(e).find('.AssignedFiles').empty();

		$.post(ChannelFiles.AJAX_URL, Params, function(rData){

			delete ChannelFiles.CFields;

			ChannelFiles.InitFields();

			for (var File in rData.files){

				// Is this the last one? So we can trigger sync..
				var Sync = ((rData.files.length -1) == File) ? true : false;

				ChannelFiles.AddNewFile(rData.files[File], rData.files[File].field_id, Sync);
			}

			ChannelFiles.Refreshing = false;

			// Grab the new field_id
			$('#ChannelFiles_'+Params.field_id).find('.temp_key').attr('value', ChannelFiles.Fields['Field_'+Params.field_id].key);

		}, 'json');
	});
};

//********************************************************************************* //

ChannelFiles.OpenFileReplace = function(e){
	e.preventDefault();

	// Grab the json
	var ItemObj = $(e.target).closest('.File');
	var jsondata = ItemObj.find('textarea.FileData').html();
	jsondata = JSON.parse(jsondata);

	$.colorbox({title: 'Replace File', iframe: true, width:300, height:300, href:ChannelFiles.AJAX_URL+'&ajax_method=display_replace_file_ui&file_id='+jsondata.file_id});
};

//********************************************************************************* //









//********************************************************************************* //
ChannelFiles.ErrorMSG = function (Msg, FIELD_ID){
	ChannelFiles.UploadError = true;

	var CFFIELD = jQuery('#ChannelFiles_' + FIELD_ID).find('.UploadProgress');
	CFFIELD.find('.percent').html('<span style="color:brown; font-weight:bold;">' + Msg + '</span>');
	CFFIELD.find('.speed, .uploadedBytes, .totalBytes').empty();
};

//********************************************************************************* //

ChannelFiles.Debug = function(msg){
	try {
		console.log(msg);
	}
	catch (e) {	}
};

//********************************************************************************* //
