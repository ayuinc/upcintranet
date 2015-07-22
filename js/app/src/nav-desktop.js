$(document).ready(function(){
	// $('.nav-main > li.menu').removeClass('open active-menu');
	var $navMain = $('.nav-main > li.menu');
	$navMain.hover(function(){
		$(this).siblings().removeClass('open active-menu');
		$(this).toggleClass('open');
		$(this).toggleClass('active-menu');
	});
	// Function for smooth proportional scroll to vertical side menu
	$(window).scroll(function(){
	    var scrollPercentage = $(window).scrollTop() /  ($(document).height() - $(window).height());
		var availableHeight = $(window).height() - $('.site-header .container-fluid').height()

		if(availableHeight< $('.site-menu').height()){
			$('.site-menu').css('top', -($('.site-menu').height() - availableHeight)*(scrollPercentage));
		}
		
	});
});