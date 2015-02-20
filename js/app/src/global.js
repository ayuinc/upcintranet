$(document).ready(function(){
    // dropdown in header
    var $dropdown = $('.dropdown-menu > li.dditem');
    $dropdown.hover(function(){
        $('.dropdown-toggle img').toggleClass('open');
    });
    // mobile menu
    $('.child').hide();
    $('.parent').click(function() {
        $(this).siblings('.parent').find('ul').slideUp();
        $(this).find('.child').slideToggle();
    });
    // search feature
    $('.search-trigger').click(function(){
        $('.search-bar').toggleClass('hidden');
    })
    // dynamic site-menu height
    // targetHeight = $('.site-content').height() + $('.internal-footer').height();
    // $('.site-menu').css("height",targetHeight + "px");
});