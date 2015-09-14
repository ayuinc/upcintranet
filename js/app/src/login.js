$(document).ready(function () {
  if ($('.site-content').hasClass("login")) {
    $('.site-wrapper').addClass("grid-bg");
  }
  if ($('.site-content').hasClass("dashboard")) {
    $('.site-wrapper').addClass("grid-bg");
  }
  //validate login form
  $(".login-form").validate({
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
    submitHandler: function ($this) {
    // do other things for a valid form
      document.cookie = "onLogin=true; ";
    // TERMINOS Y CONDICIONES 
      var form = $this;
      // event.preventDefault();
      // var hostname = ''; 
      // var codigo = $("input[name='codigo']").val();

      // $.ajax({
      //   url: hostname +'includes/terminos_condiciones_get',
      //   type: "POST",
      //   data: ({codigo: codigo}),
      //   success: function(data){
      //     var result = data.slice(0,2);
      //     if (result == 'no') {
      //       $('#condicionesModal').modal('show');
      //       $( "#aceptar" ).click(function() {
      //         if ($('#checkbox').is(':checked')) {
      //           $.ajax({
      //             url: hostname +'/includes/terminos_condiciones_set',
      //             type: "POST",
      //             data: ({codigo: codigo}),
      //             success: function(data){
      //               form.submit();      
      //             }
      //           });
      //         }  
      //       });
      //     }else 
      //       form.submit();       
          
      //   }
      // });
      // END TERMINOS Y CONDICIONES
      form.submit();  
    }
  });
});

$(document).ready(function () {
  $('#checkbox').change(function () {
    if($('#checkbox').is(':checked')){
      $('#aceptar').attr("disabled", false);
    }else{
      $('#aceptar').attr("disabled", true);
    }
   
  });

});


function validateSource() {
  if( getCookie("onLogin") === false) {
    var url = "/";
    $(location).attr('href', url);
  }
}