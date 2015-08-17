$(document).ready(function(){
  if ($('.site-content').hasClass("login")) {
  	$('.site-wrapper').addClass("grid-bg");
  };
  if ($('.site-content').hasClass("dashboard")) {
  	$('.site-wrapper').addClass("grid-bg");
  };
  //validate login form
	$(".login-form").validate({
    // var form = this; 
	  // Specify the validation rules
    rules: {
      codigo: "required",
      contrasena: "required",
    },
    // Specify the validation error messages
    messages: {
      codigo: {
          required: "Debes ingresar tu código de usuario",
      },
      contrasena: {
          required: "Debes ingresar tu contraseña",
      },
    },
    submitHandler: function($this) {
    // do other things for a valid form
    document.cookie = "onLogin=true; ";
    // form.submit();
  }
	});
});


function validateSource(){
  if(getCookie("onLogin")==false){
    var url = "/";    
    $(location).attr('href',url);
  }
}