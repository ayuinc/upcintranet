$(document).ready(function() {

	var href = window.location.protocol + "://" + window.location.host + "/" + window.location.pathname;
	var codigo_alumno = href.substr(href.lastIndexOf('/') + 1);
	var hostname = <?php  echo $_GET['url']; ?>;

	$.ajax({
    url: hostname +'mis-estudios/notas-alumno-curso',
    type: "GET",
    success: function(data){
      
      $('#cargador-notas-detalladas-alumno').remove();
      $( "#notas-detalladas-alumno" ).append( data );
      $(".go-to-top").click(function() {
        var target = 10;
        $('html, body').animate({
            scrollTop: target
        }, 200);
      });
    }
  });	

  $.ajax({
    url: hostname +'mis-estudios/todos-cursos-alumno',
    type: "GET",
    success: function(data){
      
      $('#cargador-cursos-alumno').remove();
      $( "#cursos-alumno" ).append( data );

      $(".curso-link").click(function() {
        var id = $(this).attr('data-curso-id');
        var target = $("#curso-" + id).offset().top - 150;
        $('html, body').animate({
            scrollTop: target
        }, 200);
      });
    }
  });	

});
