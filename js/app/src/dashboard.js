$(document).ready(function(){
	//mensaje
	$(".msge-row-x").click(function(){
		document.cookie="closed-alert=true";
		$(".msge-row").hide();
	})
	  if ($('.site-content').hasClass("dashboard")) {
	    $('.site-wrapper').addClass("grid-bg");
	  }
	$("#lnk_int_CerrarSesion").click(function(){
		document.cookie="closed-alert=false";
	})

	// menu height
	targetHeight = $('.site-content').height() + 49;
	$('.site-menu').css("height",targetHeight + "px");

	if(getCookie('closed-alert') == 'true'){
		$(".msge-row").hide();
	}

	$('.dashboard-modal').modal('toggle');


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

