(function(){

	$(document).ready(function(){
		var clickToggle = $('.toggle-nav-link'),
				closeOverlay = $('#off-canvas-close-overlay'),
				hoverToggle = $('.toggle-nav'),
				toggles = {
					clickable: true,
					hoverable: false
				},
				offCanvas = toggles.clickable && toggles.hoverable ?
										makeOffCanvas();

		offCanvas();

		function makeOffCanvas() {
			$(clickToggle).click(function(e){
				e.preventDefault();
				toggleNav();
			});
		}

	});

})();

/*========================================
=            CUSTOM FUNCTIONS            =
========================================*/
function toggleNav() {  
  if ($('#site-wrapper').hasClass('show-nav')) {
    $('#site-wrapper').removeClass('show-nav');
  } else if ($('#site-wrapper').hasClass('homepage-on')){
  	$('#site-wrapper').removeClass('homepage-on');
  } else if ($('#site-wrapper').hasClass('show-notes')) {
    $('#site-wrapper').removeClass('show-notes');
    $('header').removeClass('notes-opened');
    $('#site-wrapper').addClass('show-nav');
  } else {
    $('#site-wrapper').addClass('show-nav');
  }
}