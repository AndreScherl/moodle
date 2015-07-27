(function($) {

	$.equalizer = function() {

		var $equalizer = $('[data-equalizer]');
		var elementHeights;
		var $selector;

		$equalizer.each(function() {
			elementHeights = [];

			$selector = $(this).find('[data-equalizer-watch]');

			// Get an array of all element heights
			elementHeights = $selector.map(function() {
				return $(this).height();
			}).get();

			// Math.max takes a variable number of arguments
			// `apply` is equivalent to passing each height as an argument
			var maxHeight = Math.max.apply(null, elementHeights);

			// Set each height to the max height
			$selector.css({'min-height': maxHeight});

		});

	}

})(jQuery);