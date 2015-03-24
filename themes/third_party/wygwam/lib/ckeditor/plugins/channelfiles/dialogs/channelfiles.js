( function(){

	// Dialog Object
	// http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.dialog.dialogDefinition.html
	var channelfiles_dialog = function(editor){

		DialogElements = new Array();
		DialogSizes = new Array();


		//********************************************************************************* //

		return {

			// The dialog title, displayed in the dialog's header. Required.
			title: 'Channel Files',

			// The minimum width of the dialog, in pixels.
			minWidth: '600',

			// The minimum height of the dialog, in pixels.
			minHeight: '400',

			// Buttons
			buttons: [CKEDITOR.dialog.okButton, CKEDITOR.dialog.cancelButton] /*array of button definitions*/,

			// On OK event
			onOk: function(Event){
				var Wrapper = jQuery(CKEDITOR.dialog.getCurrent().definition.dialog.parts.dialog.$);

				if ( Wrapper.find('table.FilesTable input[type=radio]:checked').length == 0) return;

				var Selected = Wrapper.find('table.FilesTable input[type=radio]:checked').closest('tr');
				var Filename = Selected.find('td:eq(1)').text();
				var Filetitle = Selected.find('td:eq(2)').text();
				var FIELD_ID = Selected.closest('table.CFTable').attr('rel');
				var DIR = Selected.find('input[type=radio]').attr('rel');

				var Target = Wrapper.find('.WCF_Files_Options input[name=target]:checked').val();
				var Text = Wrapper.find('.WCF_Files_Options input[name=text]:checked').val();

				var Element = editor.document.createElement('a');
				Element.setAttribute('href', ChannelFiles.SIMPLE_FILE_URL + '&fid='+FIELD_ID+'&d='+DIR+'&f='+Filename);
				Element.setAttribute('title', Filetitle);
				Element.setAttribute('class', 'cf-file');
				if (Target == 'new') Element.setAttribute('target', '_blank');
				Element.setText( ((Text == 'title') ? Filetitle : Filename)   );
				console.log(Element);
				editor.insertElement(Element);
			},

			// On Cancel Event
			onCancel: function(){

				var Wrapper = jQuery(CKEDITOR.dialog.getCurrent().definition.dialog.parts.dialog.$);

			},

			// On Load Event
			onLoad: function(){},

			// On Show Event
			onShow: function(){

				// Grab the FilesWrapper
				var FileWrapper = jQuery(this.getElement().$).find('.WCF_Files').css({height:'300px', overflow:'scroll'});
				var HTML = '<div class="CFField">';

				// Loop over all Fields
				for (Field in ChannelFiles.Fields){
					var FIELD_ID = Field.substr(6);

					HTML += '<strong>' + ChannelFiles.Fields[Field].field_label + '</strong>';
					HTML += '<table cellspacing="0" cellpadding="0" border="0" class="CFTable FilesTable" rel="'+FIELD_ID+'">';
					HTML += '<thead><tr><th>Insert</th><th>Filename</th><th>Title</th><th>Filesize</th></tr></thead><tbody>';

					// Loop over all files in that field!
					jQuery('#ChannelFiles_'+FIELD_ID).find('tbody.AssignedFiles').find('tr.File textarea.FileData').each(function(i, el){

						var jsondata = jQuery(el).html();
						jsondata = JSON.parse(jsondata);

						if (jsondata.file_id == 0){
							var Dir = 'CFKEYDIR';
						} else {
							var Dir = ChannelFiles.entry_id;
						}

						if (jsondata.link_entry_id > 0) {
							Dir = jsondata.link_entry_id;
						}

						HTML += '<tr><td><input name="CF_RADIO_CHOICE" type="radio" rel="'+Dir+'"><td>' + jsondata.filename + '</td><td>' + jsondata.title + '</td><td>' + SWFUpload.speed.formatBytes(jsondata.filesize) + '</td></tr>';
					});

					HTML += '</tbody></table>';
				}

				HTML += '</div>';

				FileWrapper.append(HTML);
			},

			// On Hide Event
			onHide: function(){
				jQuery(this.getElement().$).find('.WCF_Files').empty();
			},

			// Can dialog be resized?
			resizable: CKEDITOR.DIALOG_RESIZE_NONE,

			// Content definition, basically the UI of the dialog
			contents:
			[
				 {
					id: 'cf_files',  /* not CSS ID attribute! */
					label: 'Files',
					className : 'cfweeeej',
					elements: [
					    {
						   type : 'html',
						   html : '<p>Please select an file.</p>'
						},
						{
							type : 'html',
							 html : '<div class="WCF_Files"></div>'
						},
						{
							type : 'html',
							 html : '<div class="WCF_Files_Options"> <table border="0" style="width:70%">' +
							 	'<thead><tr> <th><strong>Target</strong></th> <th><strong>Text</strong></th> </tr></thead><tbody>' +
							 	'<tr> <td><input name="target" type="radio" value="same" checked>Same Window</td> <td><input name="text" type="radio" value="filename" checked>Filename</td> </tr>' +
							 	'<tr> <td><input name="target" type="radio" value="new">New Window</td> <td><input name="text" type="radio" value="title">File Title</td> </tr>' +
							 	'</tbody></table> </div>'
						}
					]
				 }
			]
		};

		//********************************************************************************* //


	};

	// Add the Dialog
	CKEDITOR.dialog.add('channelfiles', function(editor) {
		return channelfiles_dialog(editor);
	});

})();
