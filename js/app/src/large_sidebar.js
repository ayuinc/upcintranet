$(document).ready(function(){
    // dynamic site-menu height
    // if ($('.site-content').height() > 571) {
	    targetHeight = $('.site-content').height() - 100;
	    $('.site-menu').css("height",targetHeight + "px");
    // }
});