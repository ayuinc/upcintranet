$(document).ready(function() {

	var href = window.location.protocol + "://" + window.location.host + "/" + window.location.pathname;
	var codigo_alumno = href.substr(href.lastIndexOf('/') + 1);
	var hostname = <?php  echo $_GET['url']; ?>;

	$.ajax({
    url: hostname +'sus-estudios/notas-padre-curso',
    type: "GET",
    data: ({codigo_alumno: codigo_alumno}),
    success: function(data){

      $('#cargador-cursos-padre').remove();
      $( "#cursos-padre" ).append( data );
      $(".go-to-top").click(function() {
        var target = 10;
        $('html, body').animate({
            scrollTop: target
        }, 200);
      });
    }
  });	

  $.ajax({
    url: hostname +'sus-estudios/todos-cursos-hijo',
    type: "GET",
    data: ({codigo_alumno: codigo_alumno}),
    success: function(data){

      $('#cargador-todos-cursos-hijo').remove();
      $( "#todos-cursos-hijo" ).append( data );
      $(".curso-link-padres").click(function() {
        var id = $(this).attr('data-curso-id');
        var target = $("#" + id).offset().top - 150;
        $('html, body').animate({
            scrollTop: target
        }, 200);
      });
    }
  });	

});
