$(document).ready(function() {
	$.get('/includes/dashboard-miscursos', function(data, status){
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

  $.get('/includes/dashboard-mispagos', function(data, status){
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

  $.get('/includes/dashboard-horario', function(data, status){
    $('#cargador-horario').remove();
    $( "#horario" ).append( data );
  }); 

  $.get('/includes/dashboard-misreservas', function(data, status){
    $('#cargador-reservas').remove();
    $( "#reservas" ).append( data );
  }); 
  
});