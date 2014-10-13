$(document).ready(function(){
	var freeTime = $('.row-libre');
	var scheduleDisplay = $('#horas-libres');
	scheduleDisplay.click(function(){
		freeTime.toggle();
	});
});