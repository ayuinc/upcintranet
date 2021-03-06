$(document).ready(function() {

    var href = window.location.protocol + "://" + window.location.host + window.location.pathname;
    var codigo_alumno = href.substr(href.lastIndexOf('/') + 1);
    var hostname = <?php  echo $_GET['url']; ?>;

    $.ajax({
        url: hostname +'includes/mis-estudios-cicloactual',
        type: "GET",
        data: ({codigo_alumno: codigo_alumno}),
        success: function(data){

            $('#cargador-ciclo-actual-estudiante').remove();
            $( "#mi-ciclo-actual" ).append( $(data) );
            if($('input.session-expired-redirect').size()!=0){
                window.location = '/general/session-expired';
            }
        }
    });
    
    $.ajax({
        url: hostname +'includes/mis-estudios-cursosalumno',
        type: "GET",
        data: ({codigo_alumno: codigo_alumno}),
        success: function(data){
            
            $('#cargador-cursos-estudiante').remove();
            $( "#cursos-alumno" ).append( $(data) );
            if($('input.session-expired-redirect').size()!=0){
                window.location = '/general/session-expired';
            }
        }
    });

});
