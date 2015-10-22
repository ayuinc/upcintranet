$(document).ready(function() {

	var href = window.location.protocol + "://" + window.location.host + window.location.pathname;
	var codigo_alumno = href.substr(href.lastIndexOf('/') + 1);
	var hostname = <?php  echo $_GET['url']; ?>; 

  $.ajax({
    url: hostname +'includes/dashboard-padre-suhorario',
    type: "GET",
    data: ({codigo_alumno: codigo_alumno}),
    success: function(data){

      $('#cargador-padre-horario').remove();
	    $( "#padre-horario" ).append( data );
      if($('input.session-expired-redirect').size()!=0){
        window.location = '/general/session-expired';
      }
    }
  });

	$.ajax({
    url: hostname +'includes/dashboard-padre-suscursos',
    type: "GET",
    data: ({codigo_alumno: codigo_alumno}),
    success: function(data){

      $('#cargador-padre-cursos').remove();
      $( "#padre-cursos" ).append( data );
      if($('input.session-expired-redirect').size()!=0){
        window.location = '/general/session-expired';
      }
    }
  });

  $.ajax({
    url: hostname +'includes/dashboard-padre-mispagos',
    type: "GET",
    data: ({codigo_alumno: codigo_alumno}),
    success: function(data){

      $('#cargador-padre-pagos').remove();
      $( "#padre-pagos" ).append( data );
      if($('input.session-expired-redirect').size()!=0){
        window.location = '/general/session-expired';
      }
    }
  });	

  $.get(hostname +'includes/rss-noticias', function(data, status){
		$('#cargador-padre-noticias').remove();
    $( "#padre-noticias" ).append( data );
    
  });

});
