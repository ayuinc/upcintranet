$(document).ready(function(){


		$(".curso-link").click(function() {
			var id = $(this).attr('data-curso-id');
		  var target = $("#curso-" + id).offset().top - 150;
		  $('html, body').animate({
		      scrollTop: target
		  }, 200);
		});
		$(".go-to-top").click(function() {
		  var target = 10;
		  $('html, body').animate({
		      scrollTop: target
		  }, 200);
		});
		// PADRES
		$(".curso-link-padres").click(function() {
			var id = $(this).attr('data-curso-id');
		  var target = $("#" + id).offset().top - 150;
		  $('html, body').animate({
		      scrollTop: target
		  }, 200);
		});
	
});
