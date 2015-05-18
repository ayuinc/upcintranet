$(document).ready(function(){
  //selects
  // $('.selectpicker').selectpicker();
  //dropdowns
	$(".datepicker").datepicker({
		dateFormat: 'dd/mm/yy',
		minDate: new Date(),
    maxDate: '+1D',
	});
	$("#deportivos-form").validate({
    // Ignore not visible fields 
    // ignore:":not(:visible)",
      ignore : [],
	  // Specify the validation rules
    rules: {
      FechaIni: "required",
      CodSede: "required",
      CodED: {require_from_group:[1, 'data-id^=CodED']}
      CodED1:{require_from_group:[1, 'data-id^=CodED']}
      CodED2: {require_from_group:[1, 'data-id^=CodED']}
      CodActiv: "required",

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
    errorPlacement: function(error, element) {
      if(element.hasClass('selectpicker')){
        shadowElement = $('[data-id="'+element.attr('id')+'"]').parent();
        if(shadowElement.is(':visible')){
          error.insertAfter(shadowElement); 
        }else{
         return;
        }
      }else if(element.is(':visible')){
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
});