M.block_mbstpl = M.block_mbstpl || {};
M.block_mbstpl.starrating = {

	/**
	 * Initiate the module.
	 */
	init: function(radioids, freeze) {

		var radioselector = 'input[type=radio]#',
			radios = Y.all(radioselector + radioids.join(',' + radioselector)),
			size = radios.size(),
			labels = {};

		function radioValue(radio) {
			var value = parseInt(radio.get('value'), 10);
			if (value) {
				return value;
			}

			// if the value doesn't exist, the form is frozen.
			// and we can extract the value from the generated id
			var id = radio.get('id'),
				matches = id.match(/^.+_(\d+)$/);

			if (matches) {
				return parseInt(matches[1], 10);
			}

			return null;
		}

		function toggleRating(label, on) {
			label.removeClass(on ? 'emptystar' : 'fullstar');
			label.addClass(on ? 'fullstar' : 'emptystar');
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
				toggleRating(labels[r], r <= rating);
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
				parent = radio.ancestor(),
				label = Y.Node.create('<label for="' + id + '"></label>');

			if (!freeze) {
				label.on('mouseover', setStarsFromRadio);
				label.on('mouseout', resetStars);
			}

			label.addClass('star');
			parent.addClass('templaterating');
			label.appendTo(parent);
			labels[value] = label;

			radio.setStyle('display', 'none');

			toggleRating(label, value <= startrating);
		}

	}

};
