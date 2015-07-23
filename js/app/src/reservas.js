jQuery.validator.addMethod("require_from_group", function(value, element, options) {
  var numberRequired = options[0];
  var selector = options[1];
  var fields = $(selector, element.form);
  var filled_fields = fields.filter(function() {
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

$(document).ready(function () {
  var result = "";
  var max_horas =  parseInt($('input[name=maxHoras]').val());
  var min_horas =  parseInt($('input[name=minHoras]').val());
  for (var i = min_horas; i <= max_horas; i++) {
    result +='<option value="'+i+'">'+i+':00</option>';
  };

  $('#hora-reserva').html(result);
  $(".datepicker").datepicker({
    dateFormat : 'dd/mm/yy',
    minDate : new Date(),
    maxDate : '+1D',
  });
  $("#deportivos-form").validate({
    // Ignore not visible fields 
    // ignore:":not(:visible)",
    ignore : [],
    // Specify the validation rules
    rules: {
      FechaIni: "required",
      CodSede: "required",
      CodED : {require_from_group : [1, '.espacios-deportivos']},
      CodED1 : {require_from_group : [1, '.espacios-deportivos']},
      CodED2 : {require_from_group : [1, '.espacios-deportivos']},
      CodActiv : "required",

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
      FecIni: "required",
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
      FecIni: "required",
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



$(window).load(function(){
  $('[id^="sede-"]').hide(); 
  $('[data-id^="sede-"]').parent().hide();
  $('[id^="actividad-"]').hide();
  $('[data-id^="actividad-"]').parent().hide();
});

$(document).ready(function() {
  
  codActivities0 = [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14]; 
  activities0 = [81,82,101,121,161,162,181,183,184,185,186,187,188,189,190];
  sedehide('R', 'CodED2');
  sedehide('L', 'CodED1')
  $.map( activities0, function( n, i ) {
    $('#actividad-'+n).hide();
    $('#actividad-'+n).attr('name', 'CodActiv'+codActivities0[i]);
  });

});   

$('#CodSede').change(function(){
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

$('#sede-L').change(function() {
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

$('#sede-R').change(function() {
  if($('#sede-R').val()=='121') { 

    codActivities0 = ["",4,5,6,7,8,9,10,11,12,13,14]; 
    activities0 = [121,161,162,181,183,184,185,186,187,188,189,190];
    firstactivityshow(activities0, codActivities0);       
  } 
  if($('#sede-R').val()=='161') { 
    $('#actividad-161').show();

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
