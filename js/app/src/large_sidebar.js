$(document).ready(function(){
    // dynamic site-menu height
    // if ($('.site-content').height() > 571) {
	    targetHeight = $('.site-content').height() + 49;
	    $('.site-menu').css("height",targetHeight + "px");
    // }
});