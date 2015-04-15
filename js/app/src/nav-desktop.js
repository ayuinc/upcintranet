$(document).ready(function(){
	// $('.nav-main > li.menu').removeClass('open active-menu');
	var $navMain = $('.nav-main > li.menu');
	$navMain.hover(function(){
		$(this).siblings().removeClass('open active-menu');
		$(this).toggleClass('open');
		$(this).toggleClass('active-menu');
	});
});