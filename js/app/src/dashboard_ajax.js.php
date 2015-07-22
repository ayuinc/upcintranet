$(document).ready(function() {

	var href = window.location.protocol + "://" + window.location.host + "/" + window.location.pathname;
	var codigo_alumno = href.substr(href.lastIndexOf('/') + 1);
	

	var hostname = <?php  echo $_GET['url']; ?>; 
	$.get(hostname +'includes/dashboard-miscursos', function(data, status){
    $('#cargador-cursos').remove();
    $( "#notas" ).append( data );
  	$('.show-curso-detail').click(function () {
			$(this).parent().find(".curso-faltas").toggle();
			$(this).parent().find(".curso-promedio").toggle();
			$(this).hide();
		});
		$('.curso-faltas').click(function () {
			$(this).parent().find(".show-curso-detail").toggle();
			$(this).parent().find(".curso-promedio").toggle();
			$(this).hide();
		});
		$('.curso-promedio').click(function () {
			$(this).parent().find(".show-curso-detail").toggle();
			$(this).parent().find(".curso-faltas").toggle();
			$(this).hide();
		});
  });

  $.get(hostname +'includes/dashboard-mispagos', function(data, status){
    $('#cargador-pagos').remove();
    $( "#boleta" ).append( data );
  	//deudas
		var debts = $('#pagos-pdtes');
		var debtsPlaceholder = $("#pagos-placeholder");
		debtsPlaceholder.click(function(){
			debts.toggle();
			debtsPlaceholder.toggle();
		});
		debts.click(function(){
			debts.toggle();
			debtsPlaceholder.toggle();
		});
  });  

  $.get(hostname +'includes/dashboard-horario', function(data, status){
    $('#cargador-horario').remove();
    $( "#horario" ).append( data );
  }); 

  $.get(hostname +'includes/dashboard-misreservas', function(data, status){
    $('#cargador-reservas').remove();
    $( "#reservas" ).append( data );
  }); 
  
  $.get(hostname +'includes/rss-noticias', function(data, status){
		$('#cargador-noticias').remove();
    $( "#noticias" ).append( data );
  });	

  $.get(hostname +'includes/rss-vida-universitaria', function(data, status){
		$('#cargador-vida').remove();
    $( "#vida-universitaria" ).append( data );
  });

  $.get(hostname +'includes/dashboard-horario-docente', function(data, status){
		$('#cargador-horario-docente').remove();
    $( "#horario-docente" ).append( data );
  });

  $.get(hostname +'includes/dashboard-miscursos-docente', function(data, status){
		$('#cargador-cursos-docente').remove();
    $( "#cursos-docente" ).append( data );
  });	

  $.get(hostname +'includes/dashboard-noticias-docentes', function(data, status){
		$('#cargador-noticias-docente').remove();
    $( "#noticias-docente" ).append( data );
  });

  $.get(hostname +'includes/rss-tice', function(data, status){
		$('#cargador-noticias-tice').remove();
    $( "#noticias-tice" ).append( data );
  });

  $.ajax({
    url: hostname +'includes/dashboard-padre-suhorario',
    type: "GET",
    data: ({codigo_alumno: codigo_alumno}),
    success: function(data){

      $('#cargador-padre-horario').remove();
	    $( "#padre-horario" ).append( data );
    }
  });

	$.ajax({
    url: hostname +'includes/dashboard-padre-suscursos',
    type: "GET",
    data: ({codigo_alumno: codigo_alumno}),
    success: function(data){

      $('#cargador-padre-cursos').remove();
      $( "#padre-cursos" ).append( data );
    }
  });

  $.ajax({
    url: hostname +'includes/dashboard-padre-mispagos',
    type: "GET",
    data: ({codigo_alumno: codigo_alumno}),
    success: function(data){

      $('#cargador-padre-pagos').remove();
      $( "#padre-pagos" ).append( data );
    }
  });	

  $.get(hostname +'includes/rss-noticias', function(data, status){
		$('#cargador-padre-noticias').remove();
    $( "#padre-noticias" ).append( data );
  });

});
