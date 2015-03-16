$(document).ready(function(){
	$( "#start-date-rec" ).datepicker({
		dateFormat: 'dd/mm/yy',
		minDate: new Date(),
	});
	$("#deportivos-form").validate({
		// Specify the validation rules
    rules: {
      FechaIni: "required",
      CodSede: "required",
      CodEd: "required",
    },
    // Specify the validation error messages
    messages: {
        FechaIni: {
            required: "Debes seleccionar una fecha",
        },
        CodSede: {
            required: "Debes seleccionar una sede",
        },
        CodEd: {
            required: "Debes seleccionar un espacio",
        },
        email: "Please enter a valid email address",
        agree: "Please accept our policy"
    },
	});
	$("#computadoras-form").validate({
		// Specify the validation rules
    rules: {
      FechaIni: "required",
      CodSede: "required",
      CodEd: "required",
    },
    // Specify the validation error messages
    messages: {
        FechaIni: {
            required: "Debes seleccionar una fecha",
        },
        CodSede: {
            required: "Debes seleccionar una sede",
        },
        CodEd: {
            required: "Debes seleccionar un espacio",
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
	      FechaIni: "required",
	      CodSede: "required",
	      CodEd: "required",
	    },
	    // Specify the validation error messages
	    messages: {
	        FecIni: {
	            required: "Debes seleccionar una fecha",
	        },
	        CodSede: {
	            required: "Debes seleccionar una sede",
	        },
	        CodEd: {
	            required: "Debes seleccionar un espacio",
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