
$(document).ready(function() {
	var hostname = <?php  echo $_GET['url']; ?>; 
	//var	hostname =  window.location.origin;
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

});
