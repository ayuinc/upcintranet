$(document).ready(function(){
  if ($('.site-content').hasClass("login")) {
  	$('.site-wrapper').addClass("grid-bg");
  };
  if ($('.site-content').hasClass("dashboard")) {
  	$('.site-wrapper').addClass("grid-bg");
  };
  //validate login form
	$(".login-form").validate({
	  // Specify the validation rules
    rules: {
      codigo: "required",
      contrasena: "required",
    },
    // Specify the validation error messages
    messages: {
      codigo: {
          required: "Debes ingresar tu código",
      },
      contrasena: {
          required: "Debes ingresa tu contraseña",
      },
    },
	});
});