$(document).ready(function(){
    // dynamic site-menu height
    targetHeight = $('.site-content').height() + $('.internal-footer').height();
    $('.site-menu').css("height",targetHeight + "px");
});