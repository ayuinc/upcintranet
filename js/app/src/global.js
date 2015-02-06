$(document).ready(function(){
    $('.search-trigger').click(function(){
    	$('.search-bar').toggleClass('hidden');
    })
    targetHeight = $('.site-content').height() + $('.internal-footer').height() - 18;
    $('.site-menu').css("height",targetHeight + "px");
});