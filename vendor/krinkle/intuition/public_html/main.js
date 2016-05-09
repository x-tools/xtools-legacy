jQuery(function ($) {
	var $tab;

	$('.nav-tabs').on('click', '[data-toggle="tab"]', function (e) {
		e.preventDefault();
		e.stopPropagation();

		$(this).tab('show');
		if (history.replaceState) {
			history.replaceState( null, document.title, this.href );
		}
	});

	if (location.hash.indexOf('#tab') === 0) {
		$tab = $('.nav-tabs a').filter(function () {
			return $(this).attr('href') === location.hash;
		});
		if ($tab.length) {
			$tab.first().tab('show');
		}
	}
});
