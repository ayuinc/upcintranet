(function($) {


ContentElements.bind('playa', 'display', function(element){
	var $field = $('.playa', element);

	// ignore if we can't find that field
	if (! $field.length) return;

	var opts = $field.data('ce_options');

	if (typeof opts == "undefined")
	{
		opts = {};
	}

	if ($field.hasClass('playa-dp')) {
		new PlayaDropPanes($field, opts);
	} else {
		new PlayaSingleSelect($field, opts);
	}
});


})(jQuery);
