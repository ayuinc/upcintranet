$(document).ready(function(){
	var $navMain = $('.nav-main > li');
	$navMain.on('tap', function(){
		$(this).toggleClass('active-menu');
		// $(this).toggleClass('active-menu');
	});