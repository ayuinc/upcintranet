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
    }
  });	

  $.ajax({
    url: hostname +'mis-estudios/todos-cursos-alumno',
    type: "GET",
    success: function(data){

      $('#cargador-cursos-alumno').remove();
      $( "#cursos-alumno" ).append( data );
    }
  });	

});
