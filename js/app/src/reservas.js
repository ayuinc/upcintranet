jQuery.validator.addMethod("require_from_group", function (value, element, options) {
  var numberRequired = options[0];
  var selector = options[1];
  var fields = $(selector, element.form);
  var filled_fields = fields.filter(function () {
    // it's more clear to compare with empty string
    return ($(this).val() !== "" && $(this).val() !== null);
  });
  var empty_fields = fields.not(filled_fields);
  // we will mark only first empty field as invalid
  if (filled_fields.length < numberRequired && empty_fields[0] === element) {
    return false;
  }
  return true;
  // {0} below is the 0th item in the options field
}, jQuery.validator.format("Debes seleccionar un espacio"));

$.validator.addMethod("peruvianDate", function (value, element) {
  return value.match(/^(\d{1,2})(\/|-)(\d{1,2})(\/|-)(\d{4})$/);
}, "Ingrese una fecha válida");

$(document).ready(function () {
  $('input[name="FecIni"]').keypress(function (event) {
    event.preventDefault();
  });
  $('input[name="FechaIni"]').keypress(function (event) {
    event.preventDefault();
  });
  var result = "";
  var max_horas =  parseInt($('input[name=maxHoras]').val(), 10);
  var min_horas =  parseInt($('input[name=minHoras]').val(), 10);
  for (var i = min_horas; i <= max_horas; i++) {
    result +='<option value="'+i+'">'+i+':00</option>';
  };

  $('#hora-reserva').html(result);

  $(".datepicker").datepicker({
    dateFormat : 'dd/mm/yy',
    minDate : new Date(),
    maxDate : '+1D',
  });

  $('.datepicker').datepicker({
        format: "dd/mm/yyyy",
    })
    //Listen for the change even on the input
    .change(dateChanged)
    .on('changeDate', dateChanged);

  $("#deportivos-form").validate({
    // Ignore not visible fields 
    // ignore:":not(:visible)",
    ignore : [],
    // Specify the validation rules
    rules: {
      CodED : {require_from_group : [1, '.espacios-deportivos']},
      CodED1 : {require_from_group : [1, '.espacios-deportivos']},
      CodED2 : {require_from_group : [1, '.espacios-deportivos']},
      CodActiv : "required",
      FechaIni: {required : true,
                  peruvianDate:true},
      CodSede: "required"
    },
    // Specify the validation error messages
    messages: {
      FechaIni: {
        required: "Debes seleccionar una fecha",
      },
      CodSede: {
        required: "Debes seleccionar una sede",
      },
      CodED: {
        required: "Debes seleccionar un espacio",
      },
      CodED1: {
        required: "Debes seleccionar un espacio",
      },
      CodED2: {
        required: "Debes seleccionar un espacio",
      },

    },
    errorPlacement: function (error, element) {
      if (element.hasClass('selectpicker')) {
        var shadowElement = $('[data-id="' + element.attr('id') + '"]').parent();
        if (shadowElement.is(':visible')) {
          error.insertAfter(shadowElement);
        } else {
          return;
        }
      } else if (element.is(':visible')) {
        error.insertAfter(element);
      }
      return;
    }
  });
  $("#computadoras-form").validate({
  // Specify the validation rules
    rules: {
      CodSede: "required",
      CanHoras: "required",
      HoraIni: "required",
      FecIni: {required : true,
                  peruvianDate:true}
    },
    // Specify the validation error messages
    messages: {
      FecIni: {
        required: "Debes seleccionar una fecha",
      },
      CodSede: {
        required: "Debes seleccionar una sede",
      },
      CanHoras: {
        required: "Debes seleccionar la cantidad de horas",
      },
      HoraIni: {
        required: "Debes seleccionar la hora inicial",
      },
    },
  });
  $("#cubiculos-form").validate({
  // Specify the validation rules
    rules: {
      CodSede: "required",
      FecIni:  {required : true,
                peruvianDate:true},
      HoraIni: "required",
      CanHoras: "required",
    },
    // Specify the validation error messages
    messages: {
      FecIni: {
        required: "Debes seleccionar una fecha",
      },
      CodSede: {
        required: "Debes seleccionar una sede",
      },
      CanHoras: {
        required: "Debes seleccionar la cantidad de horas",
      },
      HoraIni: {
        required: "Debes seleccionar la hora inicial",
      },
    },
  });

  //add require from group method

});



$(window).load(function (){
  $('[id^="sede-"]').hide(); 
  $('[data-id^="sede-"]').parent().hide();
  $('[id^="actividad-"]').hide();
  $('[data-id^="actividad-"]').parent().hide();
});

$(document).ready(function () {
  
  codActivities0 = [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14]; 
  activities0 = [81,82,101,121,161,162,181,183,184,185,186,187,188,189,190];
  sedehide('R', 'CodED2');
  sedehide('L', 'CodED1')
  $.map( activities0, function( n, i ) {
    $('#actividad-'+n).hide();
    $('#actividad-'+n).attr('name', 'CodActiv'+codActivities0[i]);
  });

  // IF MOBILE
  var isMobile = false; //initiate as false
  // device detection
  if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|ipad|iris|kindle|Android|Silk|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(navigator.userAgent) 
      || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(navigator.userAgent.substr(0,4))) isMobile = true;
  // if( /Android|webOS|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
   // isMobile = true;
  // }
  if(isMobile){
    if(document.getElementById('listado-reservas') !== undefined && document.getElementById('listado-reservas') !== null){
      $('html, body').animate({
          scrollTop: $("#listado-reservas").offset().top - $('#mobile-nav').height() - 10
      }, 2000);
    }
  }
});   

$('#CodSede').change(function (){
  if($('#CodSede').val()=='L') { 
    sedeshow('L', 'CodED');
    sedehide('R', 'CodED2')
    codActivities0 = [3,4,5,6,7,8,9,10,11,12,13,14]; 
    activities0 = [121,161,162,181,183,185,186,187,188,189,190];
    everyactivityhide(activities0, codActivities0); 
  
  }
  if($('#CodSede').val()=='R') { 
    sedeshow('R', 'CodED');
    sedehide('L', 'CodED1')
    codActivities0 = [0,1,2];
    activities0 = [81,82,101];
    everyactivityhide(activities0, codActivities0); 
   }                  
});

$('#sede-L').change(function () {
  if($('#sede-L').val()=='81') { 

    codActivities0 = ["",1,2]; 
    activities0 = [81,82,101];
    firstactivityshow(activities0, codActivities0);       
  } 
  if($('#sede-L').val()=='82') { 

    codActivities0 = ["",1,2]; 
    activities0 = [82, 81, 101];
    firstactivityshow(activities0, codActivities0);   
  } 
  if($('#sede-L').val()=='101') { 

    codActivities0 = ["",1,2]; 
    activities0 = [101,81,82];
    firstactivityshow(activities0, codActivities0);   
  }                         
});

$('#sede-R').change(function () {
  if($('#sede-R').val()=='121') { 

    codActivities0 = ["",4,5,6,7,8,9,10,11,12,13,14]; 
    activities0 = [121,161,162,181,183,184,185,186,187,188,189,190];
    firstactivityshow(activities0, codActivities0);       
  } 
  if($('#sede-R').val()=='161') { 
    codActivities0 = ["",3,5,6,7,8,9,10,11,12,13,14]; 
    activities0 = [161,121,162,181,183,184,185,186,187,188,189,190];
    firstactivityshow(activities0, codActivities0);     
  } 
  if($('#sede-R').val()=='162') { 

    codActivities0 = ["", 3,4,6,7,8,9,10,11,12,13,14]; 
    activities0 = [162,121,161,181,183,184, 185, 186,187,188,189,190];
    firstactivityshow(activities0, codActivities0);     
  } 
  if($('#sede-R').val()=='181') { 

    codActivities0 = ["",3,4,5,7,8,9,10,11,12,13,14]; 
    activities0 = [181, 121,161,162,183,184,185,186,187,188,189,190];
    firstactivityshow(activities0, codActivities0);       
  }                   
  if($('#sede-R').val()=='183') { 
    codActivities0 = ["",3,4,5,6,8,9,10,11,12,13,14]; 
    activities0 = [183,121,161,162,181,182,184,185,186,187,188,189,190];
    firstactivityshow(activities0, codActivities0);       
  }                 
  if($('#sede-R').val()=='184') { 
    codActivities0 = ["", 3,4,5,6,7,9,10,11,12,13,14]; 
    activities0 = [184,121,161,162,181,183,185,186,187,188,189,190];
    firstactivityshow(activities0, codActivities0);         
  }                 
  if($('#sede-R').val()=='185') { 
    codActivities0 = ["",3,4,5,6,7,8,10,11,12,13,14]; 
    activities0 = [185, 121,161,162,181,183,184,186,187,188,189,190];
    firstactivityshow(activities0, codActivities0);         
  }         
  if($('#sede-R').val()=='186') { 

    codActivities0 = ["", 3,4,5,6,7,8,9,11,12,13,14]; 
    activities0 = [186,121,161,162,181,183,184,185,187,188,189,190];
    firstactivityshow(activities0, codActivities0);     
  }       
  if($('#sede-R').val()=='187') { 
    codActivities0 = ["", 3,4,5,6,7,8,9,10,12,13,14]; 
    activities0 = [187, 121,161,162,181,183, 184, 185, 186, 188, 189,190];
    firstactivityshow(activities0, codActivities0);       
  }       
  if($('#sede-R').val()=='188') { 
    codActivities0 = ["",3,4,5,6,7,8,9,10,11,13,14]; 
    activities0 = [188, 181, 121, 161, 162, 181, 183, 184,185,186,187,189,190];   
    firstactivityshow(activities0, codActivities0);       
  }       
  if($('#sede-R').val()=='189') { 

    codActivities0 = ["",3,4,5,6,7,8,9,10,11,12,14];  
    activities0 = [189,121,161,162,181,183,184,185,186,187,188,190];
    firstactivityshow(activities0, codActivities0);     
  }       
  if($('#sede-R').val()=='190') { 

    codActivities0 = ["", 3,4,5,6,7,8,9,10,11,12,13];  
    activities0 = [190, 121,161,162,181,183,184,185,186,187,188,189];
    firstactivityshow(activities0, codActivities0);   
  }             
});   

function sedeshow(a, b){
  $('#sede-'+a).attr('name', b);
  $('[data-id="sede-'+a+'"]').parent().show();
  $('#sede-'+a).addClass('espacios-deportivos');
}

function sedehide(a,b){
  $('#sede-'+a).hide();
  $('#sede-'+a).attr('name', b);
  $('[data-id="sede-'+a+'"]').parent().hide();
}

function activityhide(a, b){
  $('#actividad-'+a).hide();
  $('#actividad-'+a).attr('name', 'CodActiv'+b);
  $('[data-id="actividad-'+a+'"]').parent().hide();
}

function activityshow(a, b){
  $('#actividad-'+a).attr('name', 'CodActiv'+b);
  $('[data-id="actividad-'+a+'"]').parent().show();
}

function everyactivityhide(a, b){
  $.map( a, function( n, i ) {
  $('#actividad-'+n).hide();
  $('#actividad-'+n).attr('name', 'CodActiv'+b[i]);
  $('[data-id="actividad-'+n+'"]').parent().hide();
});
}

function firstactivityshow(a, b){
  activityshow(a[0], b[0]);
  a.shift();
  b.shift();
  everyactivityhide(a,b);
}

function dateChanged(ev) {
  var dt = new Date();
  var time = dt.getHours();
  var max_horas =  parseInt($('input[name=maxHoras]').val());
  if($('input[name="FechaIni"]').val()!= "" || $('input[name="FecIni"]').val()!= "")
  {
    var fecha = ($('input[name="FechaIni"]').length > 0 ) ? $('input[name="FechaIni"]').val() : $('input[name="FecIni"]').val();
    var today = $.datepicker.formatDate('dd/mm/yy', new Date() );
    if(fecha==today){
      $('select[name="HoraIni"] option').each(function(index){
        $(this).removeAttr('selected');
        if($(this).val()< time){
         $(this).prop('disabled', 'true');
        }
      });
        $('select[name="HoraIni"] option[value="'+(time+1)+'"]').prop('selected', 'true');

    }else{
      $('select[name="HoraIni"] option:disabled').each(function(index){
        $(this).removeAttr('selected');
        $(this).removeAttr('disabled');
        if(index == 0){
          $(this).prop('selected', true);
        }
      });
    }

    $('select[name="HoraIni"]').selectpicker('render');
  }
}
