// $(document).ready(function(){
// 	var $navMain = $('.nav-main > li');
// 	$navMain.on('tap', function(){
// 		$navMain.each(function(index, item){
// 			$(this).removeClass('open');
// 		});
// 		$(this).addClass('open');
// 	});
// });

$(document).ready(function(){
	var $navMain = $('.nav-main > li.menu');
	$navMain.on('click', function(){
			$(this).toggleClass('open');
			$(this).toggleClass('active-menu');
	});
});