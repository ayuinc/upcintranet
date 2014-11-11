$(document).ready(function(){
	var $navMain = $('.nav-main > li.menu');
	$navMain.hover(function(){
		$(this).toggleClass('open');
		$(this).toggleClass('active-menu');
	});
});