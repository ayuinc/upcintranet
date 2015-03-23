// ********************************************************************************* //
var ChannelFiles = ChannelFiles ? ChannelFiles : {};
ChannelFiles.prototype = {};

// ********************************************************************************* //

ChannelFiles.Init = function(){

	ChannelFiles.CFField = jQuery('.ChannelFilesField');
	ChannelFiles.CFField.tabs();

	ChannelFiles.CFField.find('.TestLocation').click(ChannelFiles.TestLocation);

	ChannelFiles.CFField.find('.cf_upload_type').live('change', ChannelFiles.ToggleLocation);
	ChannelFiles.CFField.find('.cf_upload_type').trigger('change');

	ChannelFiles.CFField.find('.cf_entry_id_folder select').change(function(){
		if ($(this).val() == 'no') ChannelFiles.CFField.find('.cf_prefix_entry_id').show();
		else ChannelFiles.CFField.find('.cf_prefix_entry_id').hide();
	});

};

//********************************************************************************* //

ChannelFiles.TestLocation = function(Event){
	var params = $('#CFLocSettings').find(':input').serializeArray();
	params.push({name:'ajax_method', value:'test_location'});

	$.colorbox({
		href: ChannelFiles.AJAX_URL,
		data: params
	});

	return false;
};

//********************************************************************************* //

ChannelFiles.ToggleLocation = function(Event){

	Value = jQuery(Event.target).val();

	ChannelFiles.CFField.find('.CFLocSettings').find('.CFUpload_local,.CFUpload_s3,.CFUpload_cloudfiles,.CFUpload_ftp,.CFUpload_sftp').hide();
	ChannelFiles.CFField.find('.CFLocSettings .CFUpload_' + Value).show();

};

//********************************************************************************* //
