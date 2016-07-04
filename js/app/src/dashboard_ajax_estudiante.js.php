$(document).ready(function () {

    var href = window.location.protocol + "://" + window.location.host + window.location.pathname;
    var codigo_alumno = href.substr(href.lastIndexOf('/') + 1);
    var hostname = <?php  echo $_GET['url']; ?>;
    $.ajaxSetup({
        timeout:60000 // in milliseconds
    });

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

    }).fail(function() {
        $('#cargador-encuestas').remove();
        $("#encuestas").append('<div class="panel-body" style="height:100px; background-color:#fff"><div class"panel-table"><div class="tr p-21 zizou-14">En estos momentos tenemos una concurrencia alta, por favor recarga esta pestaña desde <a href="#"> aquí</a></div></div></div>');
    });

    $.get(hostname + 'includes/actualizar-datos', function(data, status){

        $('#cargador-modal-actualizar').remove();
        $('#actualizacion').append(data.html);
        $('#modalactualizar').modal('toggle');
        $('#form-actualizar').validate({

            rules:{
                email: {
                    "required" : data.obligatorio,
                    "email" : true,
                    maxlength: 50
                },
                phone : {
                    "required" : data.obligatorio,
                    "number": true,
                    maxlength: 15
                 },
                nombreApoderado : {
                    "required"  : data.ApodObligatorio,
                    maxlength: 80
                },
                apellidoPatApoderado : {
                    "required" : data.ApodObligatorio,
                    maxlength: 50
                },
                apellidoMatApoderado : {
                    "required" : data.ApodObligatorio,
                    maxlength: 50
                },
                emailApoderado : {
                    "required" : data.ApodEmailOblig,
                    "email" : true,
                    maxlength: 50,
                    minlength: 9
                },
                phoneApoderado : {
                    "required" : data.ApodMovilOblig,
                    "number" : true,
                    maxlength: 15,
                    minlength: 6
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
            },
            submitHandler: function(form){
                $.post(hostname + 'includes/actualizar-datos-post', {
                    phone : $('#form-actualizar #phone').val(),
                    email : $('#form-actualizar #email').val(),
                    nombreApoderado : $('#form-actualizar #nombreApoderado').val(),
                    apellidoPatApoderado : $('#form-actualizar #apellidoPatApoderado').val(),
                    apellidoMatApoderado : $('#form-actualizar #apellidoMatApoderado').val(),
                    phoneApoderado : $('#form-actualizar #phoneApoderado').val(),
                    emailApoderado : $('#form-actualizar #emailApoderado').val(),
                    tipo : $('#form-actualizar #tipoApoderado').val()
                }, function(data, status){
                    if(!data){
                        swal("Actualización de datos", "Se guardaron tus datos satisfactoriamente.", "success");
                    }else{
                        swal("Uups...", data, "error");
                    }
                });

            }
        });
        
        $('#tipoApoderado').on( 'change', function(){
            $.post(hostname + 'includes/actualizar-datos-apoderado', {tipo: $('#tipoApoderado').val()} , function (dataApoderado, status) {
                validateApoderadoFields(dataApoderado.NombreApoderado, $('#form-actualizar #nombreApoderado'));
                validateApoderadoFields(dataApoderado.ApellidoPaterApoderado, $('#form-actualizar #apellidoPatApoderado'));
                validateApoderadoFields(dataApoderado.ApellidoMaterApoderado, $('#form-actualizar #apellidoMatApoderado'));
            });

        });

    }).fail(function(data){
        swal("", "No se puedo conectar", "error");
    });


    function validateApoderadoFields(data, field){
        if(data != null && data != undefined){
            $(field).val(data);
            $(field).prop('readonly', true);
        }else {
            $(field).val("");
            $(field).prop('readonly', false);
        }
    }

});
