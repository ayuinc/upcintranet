$(document).ready(function(){
	var $navMain = $('.nav-main > li');
	$navMain.hover(function(){
		$(this).toggleClass('open');
	});
});