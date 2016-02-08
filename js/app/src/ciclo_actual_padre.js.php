$(document).ready(function() {

	var href = window.location.protocol + "://" + window.location.host + window.location.pathname;
	var codigo_alumno = href.substr(href.lastIndexOf('/') + 1);
	var hostname = <?php  echo $_GET['url']; ?>; 
  
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

});
