'use strict';

var mobileNavScript = 'http://104.131.41.84/js/app/src/nav-mobile.js',
		desktopNavScript = 'http://104.131.41.84/js/app/src/nav-desktop.js';

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