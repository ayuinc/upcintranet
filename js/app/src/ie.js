$(document).ready(function(){
	$menuWidth = $('.site-menu').width();
	$menuWidthPx = $menuWidth + "px";
	$('.site-content').css('width', '100%').css('width', "-=" + $menuWidthPx);
	//user-avatar
	var $dropdown = $('.dropdown-menu > li.dditem');
	$dropdown.hover(function(){
	    $('#ie-avatar').toggleClass('open');
	});
	//
});