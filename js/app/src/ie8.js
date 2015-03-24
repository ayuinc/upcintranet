$(document).ready(function(){
	$menuWidth = $('.site-menu').width();
	$menuWidthPx = $menuWidth + "px";
	console.log($menuWidthPx);
	$('.site-content').css('width', '100%').css('width', "-=" + $menuWidthPx);
});