var site_url = <?php  echo $_GET['url']; ?>; 

$(document).ready(function(){
	setTimeout(function () {
       window.location.href = site_url + "general/session-expired";
    }, 30*60*1000);
    if($('input.session-expired-redirect').size()!=0){
       window.location.href = site_url + "general/session-expired";
    }
});