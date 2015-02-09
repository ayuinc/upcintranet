$(document).ready(function(){
    $('.search-trigger').click(function(){
    	$('.search-bar').toggleClass('hidden');
    })
    targetHeight = $('.site-content').height() + $('.header-no-global').height() + $('.header-global').height();
    if(targetHeight < 700) {
    	$('.site-menu').css("height", "635px");
    } else {
    	$('.site-menu').css("height",targetHeight + "px");
    };
    // targetHeight = $('.site-content').height() + $('.internal-footer').height() - 18;
});