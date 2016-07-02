$(document).ready(function () {

    var href = window.location.protocol + "://" + window.location.host + window.location.pathname;
    var codigo_alumno = href.substr(href.lastIndexOf('/') + 1);
    var hostname = <?php  echo $_GET['url']; ?>;

    $.get(hostname + 'includes/dashboard-horario', function (data, status) {
        $('#cargador-horario').remove();
        $("#horario").append(data);
    });

    $.get(hostname + 'includes/dashboard-miscursos', function (data, status) {
        $('#cargador-cursos').remove();
        $("#notas").append(data);
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
        if ($('input.session-expired-redirect').size() != 0) {
            window.location = '/general/session-expired';
        }
    });

    $.get(hostname + 'includes/dashboard-misreservas', function (data, status) {
        $('#cargador-reservas').remove();
        $("#reservas").append(data);
        $("#codigo2Activar").validate({
            rules: {
                codigo: "required",
            },
            messages: {
                codigo: {
                    required: "Debes ingresar el código de compañero.",
                }
            }
        });
        $(".activar").on('click', function(){
            if($("#codigo2Activar #codigo").valid()){

                $.get(hostname + 'includes/dashboard-activar-reserva', {codreserva: $(this).data('reservaid'), codigo2:$("#codigo2Activar #codigo").text()}, function(data, status){
                    if(!data){
                        swal("", "Tu Reserva se ha activado con éxito.", "success");
                    }else{
                        swal("", data, "error");
                    }
                });
            }
        });
    });

    $.get(hostname + 'includes/dashboard-mispagos', function (data, status) {
        $('#cargador-pagos').remove();
        $("#boleta").append(data);
        //deudas
        var debts = $('#pagos-pdtes');
        var debtsPlaceholder = $("#pagos-placeholder");
        debtsPlaceholder.click(function () {
            debts.toggle();
            debtsPlaceholder.toggle();
        });
        debts.click(function () {
            debts.toggle();
            debtsPlaceholder.toggle();
        });
    });

    $.get(hostname + 'includes/rss-noticias', function (data, status) {
        $('#cargador-noticias').remove();
        $("#noticias").append(data);
    });

    $.get(hostname + 'includes/rss-vida-universitaria', function (data, status) {
        $('#cargador-vida').remove();
        $("#vida-universitaria").append(data);
    });

    $.get(hostname + 'includes/dashboard-misencuestas', function (data, status) {
        $('#cargador-encuestas').remove();
        $("#encuestas").append(data);
    });

    $.get(hostname + 'includes/actualizar-datos', function(data, status){

        $('#cargador-modal-actualizar').remove();
        $('#actualizacion').append(data.html);
        $('#modalactualizar').modal('toggle');
        $('#form-actualizar').validate({

            rules:{
                email: {
                    "required" : data.obligatorio,
                    "email" : true
                },
                phone : {
                    "required" : data.obligatorio,
                    "number": true
                 },
                nombreApoderado : {
                    "required"  : data.ApodObligatorio
                },
                apellidoPatApoderado : {
                    "required" : data.ApodObligatorio
                },
                apellidoMatApoderado : {
                    "required" : data.ApodObligatorio
                },
                emailApoderado : {
                    "required" : data.ApodEmailOblig,
                    "email" : true
                },
                phoneApoderado : {
                    "required" : data.ApodMovilOblig,
                    "number" : true
                }

            }, messages:{
                email: {
                    "required" : "Ingrese su email personal",
                    "email" : "El texto ingresado no es un correo válido"
                },
                phone : {
                    "required" : "Ingrese su número de teléfono celular",
                    "number": "Solo ingrese números"
                },
                nombreApoderado : {
                    "required"  : "Ingrese el nombre del Apoderado"
                },
                apellidoPatApoderado : {
                    "required" : "Ingrese el apellido paterno del Apoderado"
                },
                apellidoMatApoderado : {
                    "required" : "Ingrese el apellido materno del Apoderado"
                },
                emailApoderado : {
                    "required" : "Ingrese el correo electrónico personal del Apoderado",
                    "email" : "Ingrese un correo válido"
                },
                phoneApoderado : {
                    "required" : "Ingrese el número de teléfono móvil del Apoderado",
                    "number" : "Sólo ingrese números"
                }
            }
        });
        
        $('#tipoApoderado').on( 'change', function(){
            $.post(hostname + 'includes/actualizar-datos-apoderado', {tipo: $('#tipoApoderado').val()} , function (dataApoderado, status) {
                
                $('#form-actualizar #nombreApoderado').val(dataApoderado.NombreApoderado);
                $('#form-actualizar #apellidoPatApoderado').val(dataApoderado.ApellidoPaterApoderado);
                $('#form-actualizar #apellidoMatApoderado').val(dataApoderado.ApellidoMaterApoderado);

            });

        });

    }).fail(function(data){
        swal("", data, "error");
    });

});
