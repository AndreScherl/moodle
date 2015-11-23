M.block_mbstpl = M.block_mbstpl || {};
M.block_mbstpl.starrating = {

	/**
	 * Initiate the module.
	 */
	init: function(radioname) {

		var radios = Y.all('form input[name=' + radioname + ']'),
			size = radios.size(),
			emptystar = M.cfg.wwwroot + '/blocks/mbstpl/pix/emptystar.png',
			fullstar = M.cfg.wwwroot + '/blocks/mbstpl/pix/fullstar.png',
			labels = {};

		function radioValue(radio) {
			return parseInt(radio.get('value'), 10);
		}

		/*
		 * Get the current rating (based on the DOM state)
		 */
		function getRating() {
			for (var i = 0, radio; i < size; i++) {
				radio = radios.item(i);
				if (radio.get('checked')) {
					return radioValue(radio);
				}
			}

			return 0;
		}

		/*
		 * Update the stars to a rating
		 */
		function setStars(rating) {
			for (var r in labels) {
				labels[r].one('img').setAttribute('src', r <= rating ? fullstar : emptystar);
			}
		}

		/*
		 * Resets the stars to the current rating
		 */
		function resetStars() {
			var rating = getRating();
			setStars(rating);
		}

		/*
		 * To be used as a callback for mouseout on a star label
		 */
		function setStarsFromRadio() {
			var radio = Y.one(this.getDOMNode().control);
			var rating = radioValue(radio);
			setStars(rating);
		}

		// Build all stars as labels and hide radio buttons.
		for (var i = 0, startrating = getRating(); i < size; i++) {

			var radio = radios.item(i),
				value = radioValue(radio),
				id = radio.getAttribute('id'),
				label = Y.Node.create('<label for="' + id + '"><img src="' + (value <= startrating ? fullstar : emptystar) + '"></label>');

			label.on('mouseover', setStarsFromRadio);
			label.on('mouseout', resetStars);
			label.setData('value', value);
			label.appendTo(radio.ancestor());
			labels[value] = label;

			radio.setStyle('display', 'none');
		}

	}

};
