function PrintElem(elem)
{
    Popup($(elem).html());
}

function Popup(data) 
{
    var mywindow = window.open('', 'Imprimir', 'height=800,width=1200');
    mywindow.document.write('<html><head><title>Imprimir página - INTRANET UPC</title>');
    mywindow.document.write('<link rel="stylesheet" href="{site_url}stylesheets/main.min.css">');
    mywindow.document.write('<link rel="stylesheet" type="text/css" href="https://s3-sa-east-1.amazonaws.com/webfontsupc/fuentes.css">');
    mywindow.document.write('</head><body >');
    mywindow.document.write(data);
    mywindow.document.write('</body></html>');

    mywindow.document.close(); // necessary for IE >= 10
    mywindow.focus(); // necessary for IE >= 10
    console.log("mywindow", mywindow);
    // mywindow.print();
    // mywindow.close();

    return true;
}