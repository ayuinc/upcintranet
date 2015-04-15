$(document).ready(function(){
    //user-avatar
    var $dropdown = $('.dropdown-menu > li.dditem');
    $dropdown.hover(function(){
        $('#ie-avatar').toggleClass('open');
    });
    //
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
    //
    //pdf and mail features
    $('#pdf-button img').hide();
    $('#mail-button img').hide();
    $('#pdf-button-trigger').click(function() {
        $("#pdf-button").click();
    });
    $('#mail-button-trigger').click(function() {
        $("#mail-button").click();
    });
    //acordion
    $('#oaCollapse').on('show.bs.collapse', function () {
       $(".otras-acciones-arrow").addClass("open");
    });
    $('#oaCollapse').on('hide.bs.collapse', function () {
       $(".otras-acciones-arrow").removeClass("open");
    });
    //
    function delCokkies (){
        var cookies = document.cookie.split(";");
 
        for (var i = 0; i < cookies.length; i++) {
            var cookie = cookies[i];
            var eqPos = cookie.indexOf("=");
            var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
            document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT";
        }
    }
    //tutorial
    
});