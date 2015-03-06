;(function(global, doc){

	if (/D=cp&C=content_edit/.test(global.location.href)) 
	{
		global.calendarFilterSettings = {
			'act' 			: '<?=$act?>',
			'dialogTitle' 	: '<?=$lang_dialog_title?>',
			'dialogMessage'	: '<?=$lang_dialog_message?>',
			'dialogOk'		: '<?=$lang_ok?>',
		};

		var parent 	= doc.getElementsByTagName('script')[0].parentNode || 
						doc.getElementsByTagName('head')[0] || 
						doc.getElementsByTagName('body')[0],
			src 	= '<?=$src?>',
			cssSrc 	= '<?=$css_src?>',
			script  = doc.createElement('script'),
			css 	= doc.createElement('link');

			script.setAttribute('type', 'text/javascript'); 
			script.setAttribute('src', src); 
			parent.appendChild(script);

			css.setAttribute('type', 'text/css'); 
			css.setAttribute('href', cssSrc); 
			css.setAttribute('rel', 'stylesheet'); 
			css.setAttribute('media', 'screen'); 
			parent.appendChild(css);
	}
}(window, document));


