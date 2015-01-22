// $(document).ready(function(){
// 	var $navMain = $('.nav-main > li');
// 	$navMain.on('tap', function(){
// 		$(this).toggleClass('active-menu');
// 		// $(this).toggleClass('active-menu');
// 	});

$(document).ready(function(){
	var $navMain = $('.nav-main > li.menu');
	$navMain.on('click', function(){
			$(this).toggleClass('open');
			$(this).toggleClass('active-menu');
	});
});