// $(document).ready(function(){
// });

$(window).load(function(){
  //your code here
	var $navMain = $('.nav-main > li.menu');
	$navMain.each(function(){
		$(this).removeClass('open');
		$(this).removeClass('active-menu');
	});
	// $navMain.hover(function(){
	// 	$(this).toggleClass('open');
	// 	$(this).toggleClass('active-menu');
	// });
});