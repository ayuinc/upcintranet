$(document).ready(function(){
    $('.child').hide();
    $('.parent').click(function() {
        $(this).siblings('.parent').find('ul').slideUp();
        $(this).find('.child').slideToggle();
    });
    $('.search-trigger').click(function(){
    	$('.search-bar').toggleClass('hidden');
    })
    targetHeight = $('.site-content').height() + $('.internal-footer').height() + 56;
    $('.site-menu').css("height",targetHeight + "px");
});