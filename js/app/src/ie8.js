$(document).ready(function(){
	$menuWidth = $('.site-menu').width();
	$menuWidthPx = $menuWidth + "px";
	// console.log($menuWidthPx);
	$('.site-content').css('width', '100%').css('width', "-=" + $menuWidthPx);
	$("#ie-avatar").hover(function(){
		$(this).attr("src","http://10.0.0.21/assets/img/user_ie8_info.png");
	});
});