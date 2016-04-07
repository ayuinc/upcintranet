$(document).ready(function(){
    //user-avatar

    var $dropdown = $('.dropdown-menu > li.dditem.ddmain');
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

    // Responsive Mobile Menu
    $('#menu-mobile').click(function(){
        $(this).toggleClass('is-active');
        $('#overlay').toggleClass('open');
        $('#overlay').toggleClass('close');
    });
    // search feature
    $('.search-trigger').click(function(){
        // $('.search-bar').toggleClass('hidden');
        
        $('form#form-buscar').submit();
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


});