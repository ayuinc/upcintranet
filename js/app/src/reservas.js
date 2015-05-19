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