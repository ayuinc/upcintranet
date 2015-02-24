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

    function delCokkies (){
        var cookies = document.cookie.split(";");
 
        for (var i = 0; i < cookies.length; i++) {
            var cookie = cookies[i];
            var eqPos = cookie.indexOf("=");
            var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
            document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
        }
    }
    // dynamic site-menu height
    // targetHeight = $('.site-content').height() + $('.internal-footer').height();
    // $('.site-menu').css("height",targetHeight + "px");
});