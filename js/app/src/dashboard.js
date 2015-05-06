$(document).ready(function(){
	//mensaje
	$(".msge-row-x").click(function(){
		document.cookie="closed-alert=true;"+(Date()+1800)+"; path=/";
		$(".msge-row").hide();
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

	if(getCookie('closed-alert') == 'true'){
		$(".msge-row").hide();
	}

});

function getCookie(cname) {
	var name = cname + "=";
	var ca = document.cookie.split(';');
	for(var i=0; i<ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1);
		if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
	}
	return "";
}