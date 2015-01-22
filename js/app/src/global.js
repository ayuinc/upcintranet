$(document).ready(function(){
    $('.child').hide();
    $('.parent').click(function() {
        $(this).siblings('.parent').find('ul').slideUp();
        $(this).find('.child').slideToggle();
    });
    $('.search-trigger').click(function(){
    	$('.search-bar').toggleClass('hidden');
    	$('.search-icon').toggleClass('hidden');
    })
});