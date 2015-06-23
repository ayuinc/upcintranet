'use strict';

var site_url = <?php  echo $_GET['url']; ?>; 

var mobileNavScript = site_url + 'js/app/src/nav-mobile.js',
		desktopNavScript = site_url + 'js/app/src/nav-desktop.js';

if (WURFL.is_mobile) {
	addScript(mobileNavScript);
} else {
	addScript(desktopNavScript);
}

function addScript(deviceScript){
	var script = document.createElement('script'),
			src = deviceScript;

	script.src = src;
	document.body.appendChild(script);
}