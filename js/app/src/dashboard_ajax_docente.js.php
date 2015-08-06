$(document).ready(function() {

	var href = window.location.protocol + "://" + window.location.host + window.location.pathname;
	var codigo_alumno = href.substr(href.lastIndexOf('/') + 1);
	var hostname = <?php  echo $_GET['url']; ?>;

  $.get(hostname +'includes/dashboard-horario-docente', function(data, status){
		$('#cargador-horario-docente').remove();
    $( "#horario-docente" ).append( data );
  });

  $.get(hostname +'includes/dashboard-miscursos-docente', function(data, status){
		$('#cargador-cursos-docente').remove();
    $( "#cursos-docente" ).append( data );
  });	

$.get(hostname +'includes/rss-noticias', function(data, status){
		$('#cargador-noticias-docente').remove();
    $( "#noticias-docente" ).append( data );
  });

  $.get(hostname +'includes/rss-tice', function(data, status){
		$('#cargador-noticias-tice').remove();
    $( "#noticias-tice" ).append( data );
  });

});
