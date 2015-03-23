// ********************************************************************************* //
var ChannelFiles = ChannelFiles ? ChannelFiles : new Object();
ChannelFiles.prototype = {}; // Get Outline Going
//********************************************************************************* //

$(document).ready(function() {

	if ( $('#DownloadLog').length > 0) ChannelFiles.InitDownloadLog();

});

//********************************************************************************* //

ChannelFiles.InitDownloadLog = function(){
	
	var asInitVals = new Array();

	ChannelFiles.DataTable = $('#DownloadLog').dataTable({
		bJQueryUI: true, // Jquery UI Themes
		sPaginationType: 'full_numbers', // Number pagination
		aaSorting: [[4,'desc']], // Initial Sorting
		bProcessing: true,
		bServerSide: true,
		sAjaxSource: ChannelFiles.AJAX_URL + '&ajax_method=ajax_download_log'

	});
	
	$("#DownloadLog tfoot input").keyup( function () {
		/* Filter on the column (the index) of this element */
		ChannelFiles.DataTable.fnFilter( this.value, $("#DownloadLog tfoot input").index(this) );
	} );	
	
	
	/*
	 * Support functions to provide a little bit of 'user friendlyness' to the textboxes in 
	 * the footer
	 */
	$("#DownloadLog tfoot input").each( function (i) {
		asInitVals[i] = this.value;
	} );
	
	$("#DownloadLog tfoot input").focus( function () {
		if ( this.className == "search_init" )
		{
			this.className = "";
			this.value = "";
		}
	} );
	
	$("#DownloadLog tfoot input").blur( function (i) {
		if ( this.value == "" )
		{
			this.className = "search_init";
			this.value = asInitVals[$("tfoot input").index(this)];
		}
	} );

	// Add DatePickers
	$('#DownloadLog input[name=search_date_from]').datepicker({
		dateFormat: $.datepicker.W3C,
		onClose: function(){
			/* Filter on the column (the index) of this element */
			ChannelFiles.DataTable.fnFilter( this.value, $("#DownloadLog tfoot input").index(this) );
		}
	});
	
	// Add DatePickers
	$('#DownloadLog input[name=search_date_to]').datepicker({
		dateFormat: $.datepicker.W3C,
		onClose: function(){
			/* Filter on the column (the index) of this element */
			ChannelFiles.DataTable.fnFilter( this.value, $("#DownloadLog tfoot input").index(this) );
		}
	});

};

//********************************************************************************* //