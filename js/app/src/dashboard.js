$(document).ready(function(){
	//mensaje
	localStorage.setItem("dismiss", false);
	$(".msge-row-x").click(function(){
		if (localStorage['dismiss'] == false) {
			$(".msge-row").hide();
			localStorage.setItem("dismiss", true);
		}
	})
	//deudas
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
	//faltas
	$('.curso-faltas').click(function () {
		$(this).parent().find(".show-curso-detail").toggle();
		$(this).parent().find(".curso-promedio").toggle();
		$(this).hide();
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