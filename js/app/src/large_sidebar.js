$(document).ready(function(){
    // dynamic site-menu height
    // if ($('.site-content').height() > 571) {
	    // targetHeight = $('.site-content').height() - 100;
	    // $('.site-menu').css("height",targetHeight + "px");

	    $('.site-menu').height($(document).height());
	    $('.site-content').height($(document).height());
	    // $('.site-menu').css("height", document.height);
    // }
});