$(document).ready(function(){
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
		$(this).parent().find(".col-xs-2").toggle();
		$(this).parent().find(".col-xs-4").toggle();
	});
	$('.curso-faltas').click(function () {
		$(this).parent().find(".col-xs-4").toggle();
		$(this).parent().find(".col-xs-2").toggle();
	});
	$('.curso-promedio').click(function () {
		$(this).parent().find(".col-xs-4").toggle();
		$(this).parent().find(".col-xs-2").toggle();
	});
});