$(document).ready(function(){
	$( "#datepicker-end" ).datepicker({
		dateFormat: 'dd/mm/yy',
	});
	$( "#datepicker-start" ).datepicker({
		dateFormat: 'dd/mm/yy',
	});
	$( "#start-date-rec" ).datepicker({
		dateFormat: 'dd/mm/yy',
	});
	$( "#end-date-rec" ).datepicker({
		dateFormat: 'dd/mm/yy',
	});

});

$(window).load(function() {
	$('.flexslider').flexslider();
});