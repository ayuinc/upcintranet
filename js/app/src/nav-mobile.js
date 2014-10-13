$(document).ready(function(){
	var $navMain = $('.nav-main > li');
	$navMain.on('tap', function(){
		$navMain.each(function(index, item){
			$(this).removeClass('open');
		});
		$(this).addClass('open');
	});
});