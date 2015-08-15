$('#form-login').submit( function(event) {
  
  var form = this;
  event.preventDefault();
  var hostname = <?php  echo $_GET['url']; ?>; 
  var codigo = $("input[name='codigo']").val();

  $.ajax({
    url: hostname +'includes/terminos_condiciones_get',
    type: "POST",
    data: ({codigo: codigo}),
    success: function(data){
      var result = data.slice(0,2);
      if (result == 'no') {
        $('#condicionesModal').modal('show');
        $( "#aceptar" ).click(function() {
          if ($('#checkbox').is(':checked')) {
            alert(result);
            $.ajax({
              url: hostname +'includes/terminos_condiciones_set',
              type: "POST",
              data: ({codigo: codigo}),
              success: function(data){
                form.submit();      
              }
            });
          }  
        });
      }else if(result == 'si'){
        form.submit();       
      } 
    }
  });
}); 
