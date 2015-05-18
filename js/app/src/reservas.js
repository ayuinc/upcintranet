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
    ignore:":not(:visible)",

	  // Specify the validation rules
    rules: {
      FechaIni: "required",
      CodSede: "required",
      CodED: "required",
      CodED1: "required",
      CodED2: "required",
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