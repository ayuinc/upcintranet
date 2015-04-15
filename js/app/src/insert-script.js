'use strict';

var mobileNavScript = 'http://upcintranet-590402458.us-east-1.elb.amazonaws.com/js/app/src/nav-mobile.js',
		desktopNavScript = 'http://upcintranet-590402458.us-east-1.elb.amazonaws.com/js/app/src/nav-desktop.js';

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