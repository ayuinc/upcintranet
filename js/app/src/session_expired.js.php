var site_url = <?php  echo $_GET['url']; ?>; 

$(document).ready(function(){
	setTimeout(function () {
       window.location.href = site_url + "general/session-expired";
    }, 30*60*1000);
});