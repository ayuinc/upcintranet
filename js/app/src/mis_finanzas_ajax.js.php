$(document).ready(function() {

	var href = window.location.protocol + "://" + window.location.host + "/" + window.location.pathname;
	var codigo_alumno = href.substr(href.lastIndexOf('/') + 1);
	var hostname = <?php  echo $_GET['url']; ?>;
	
	$("#consultar-saldo").validate({
	    rules:{
	       numeroTarjeta:{
	             required:true,
	             number: true,
	             minlength:16,
	             maxlength:16
	       }
	    },
	    messages:{
	           numeroTarjeta:{
	              required: "Ingrese su número.",
	              number: "Ingrese solamente números.",
	              minlength: "Debe ingresar 16 caracteres.",
	              maxlength: "Debe ingresar 16 caracteres.",
	           }
	    },
	    submitHandler: function(form){
	           console.log("hola");
	           
	           
	           $.get("https://jsonplaceholder.typicode.com/users", function(data, status) {
	               console.log(data);
	               swal("Tu saldo es: ", data[0].address.geo.lng);
	           
	           });
	           
	           
	    }
	    
	});
	
	$("#bloquear-tarjeta").validate({
	    rules:{
	       internetpassword:{
	             required:true,
	             number: true,
	             minlength:6,
	             maxlength:6
	       }
	    },
	    messages:{
	           internetpassword:{
	              required: "Ingrese su número.",
	              number: "Ingrese solamente números.",
	              minlength: "Debe ingresar 6 caracteres.",
	              maxlength: "Debe ingresar 6 caracteres.",
	           }
	    },
	    submitHandler: function(form){
	           console.log("vamo a bloquear");
	           
	           $.get(hostname + "includes/bloquear", function(data, status) {
	               console.log(data);
	               swal(data.mensaje);
	           
	           });
	           
	           
	    }
	    
	});


});
