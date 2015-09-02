$(document).ready(function() {

	var href = window.location.protocol + "://" + window.location.host + window.location.pathname;
	var codigo_alumno = href.substr(href.lastIndexOf('/') + 1);
	var hostname = <?php  echo $_GET['url']; ?>; 

  $.get(hostname +'includes/dashboard-horario', function(data, status){
    $('#cargador-horario').remove();
    $( "#horario" ).append( data );
  });

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
		if($('input.session-expired-redirect').size()!=0){
			window.location = '/general/session-expired';
		}
  });

  $.get(hostname +'includes/dashboard-misreservas', function(data, status){
    $('#cargador-reservas').remove();
    $( "#reservas" ).append( data );
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
  
  $.get(hostname +'includes/rss-noticias', function(data, status){
		$('#cargador-noticias').remove();
    $( "#noticias" ).append( data );
  });	

  $.get(hostname +'includes/rss-vida-universitaria', function(data, status){
		$('#cargador-vida').remove();
    $( "#vida-universitaria" ).append( data );
  });

});
