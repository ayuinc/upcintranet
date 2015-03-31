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
		$(this).parent().find(".curso-faltas").toggle();
		$(this).parent().find(".curso-promedio").toggle();
		$(this).hide();
	});
	$('.curso-faltas').click(function () {
		$(this).parent().find(".show-curso-detail").toggle();
		$(this).parent().find(".curso-promedio").toggle();
		$(this).hide();
		// $(this).parent().find(".col-xs-2").toggle();
	});
	$('.curso-promedio').click(function () {
		$(this).parent().find(".show-curso-detail").toggle();
		$(this).parent().find(".curso-faltas").toggle();
		$(this).hide();
	});
	// menu height
	targetHeight = $('.site-content').height() + 49;
	$('.site-menu').css("height",targetHeight + "px");
});