$(document).ready(function(){
	var $navMain = $('.nav-main > li');
	$navMain.on('tap', function(){
		$(this).toggleClass('open');
	});