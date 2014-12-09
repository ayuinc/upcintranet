$(document).ready(function(){
	var freeTime = $('.row-libre');
	var scheduleDisplay = $('#horas-libres');
	scheduleDisplay.click(function(){
		freeTime.toggle();
	});
	var debts = $('#pagos-pdtes');
	var debtsPlaceholder = $("#pagos-placeholder");
	debtsPlaceholder.click(function(){
		debts.toggle();
		debtsPlaceholder.toggle();
  });
  debts.click(function(){
		debts.toggle();
		debtsPlaceholder.toggle();
  });
  $('.show-curso-detail').click(function () {
		$(this).parent().find(".col-sm-2").toggle();
		$(this).parent().find(".col-sm-4").toggle();
	});
	$('.curso-faltas').click(function () {
		$(this).parent().find(".col-sm-4").toggle();
		$(this).parent().find(".col-sm-2").toggle();
	});
	$('.curso-promedio').click(function () {
		$(this).parent().find(".col-sm-4").toggle();
		$(this).parent().find(".col-sm-2").toggle();
	});
	$('#search-phr').click(function(){
		$('#search-submit').click();
	});
});