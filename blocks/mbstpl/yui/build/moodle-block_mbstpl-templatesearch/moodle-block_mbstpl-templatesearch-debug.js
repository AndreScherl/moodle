YUI.add('moodle-block_mbstpl-templatesearch', function (Y, NAME) {

M.block_mbstpl = M.block_mbstpl || {};
M.block_mbstpl.templatesearch = {

	/**
	 * Initiate the module.
	 */
	init : function() {
		inputAction = function(e) {
			var keyword = e.currentTarget.getDOMNode().value;
			/*
			 * The autocomplete only kicks in after 3 characters to avoid unnecessary
			 * server load.
			 */
			if (keyword.length >= 3) {
				M.block_mbstpl.templatesearch.requestSuggestions(keyword, e.currentTarget);
			} else {
				// Remove previous suggestions.
				Y.all('.mbstpl-suggestion').remove();
			}
		};

		// Set autocomplete triggers.
		Y.all('input[type=text]').each(function(inputField) {
			inputField.on('keyup', inputAction);
			inputField.on('focus', inputAction);
			inputField.on('blur', function() {
				/*
				 * The timeout is essential, otherwise the box is removed before the
				 * click action gets triggered.
				 * */
				setTimeout(function() {
					// Remove previous suggestions.
					Y.all('.mbstpl-suggestion').remove();
				}, 100);
			});
		});

		// Set layout change listener.
		Y.all('.mbstpl-list-controller img').on('click', function(e) {
			e.preventDefault();
			// Set desired layout for next search query
			var field = Y.one('.mbstpl-search-form input[name="layout"]'),
			layout = e.target.getAttribute('l');
			field.set('value', layout);

			// Change CSS classes on list items to reflect the selected layout.
			var listitems = Y.all('.mbstpl-list-item');
			listitems.set('className');
			listitems.addClass('mbstpl-list-item');
			listitems.addClass('mbstpl-list-item-' + layout);
		});
	},

	/**
	 * Make a request to the back-end for suggestions.
	 */
	requestSuggestions : function(keyword, inputField) {
		// Set up request params.
		var request = {
			method : "POST",
			sync : false,
			timeout : 5000,
			data : { 'sesskey' : M.cfg.sesskey, 'keyword' : keyword, 'field' : inputField.getDOMNode().name },
			on : {
				success : function(id, data) {M.block_mbstpl.templatesearch.renderAutocomplete(id, data, inputField);},
				failure : function(id, data) { Y.log(data);Y.log(e);}

			}
		};

		// Send request.
		Y.io(M.cfg.wwwroot + '/blocks/mbstpl/autocomplete.php', request);
	},

	/**
	 * Put the suggestion into the input box.
	 */
	selectSuggestion: function(e, inputField) {
		var value = e.currentTarget.getHTML();
		inputField.set('value', value);
		Y.all('.mbstpl-suggestion').remove();
	},

	/**
	 * Render a "drop-down" menu for suggestions.
	 */
	renderAutocomplete: function(id, data, inputField) {
		// Remove previous suggestions.
		Y.all('.mbstpl-suggestion').remove();

		var suggestions = JSON.parse(data.response),
		w = inputField.getDOMNode().offsetWidth,
		x = inputField.getX();
		y = inputField.getY() + inputField.getDOMNode().offsetHeight;

		sendOnclick = function(e) {
			M.block_mbstpl.templatesearch.selectSuggestion(e, inputField);
		};

		// Render boxes under each other.
		for (var i = 0; i < suggestions.length; i++) {
			var nodeContent = '<div class="mbstpl-suggestion" style=" top:' + y +
			'px; left:' + x + 'px;width:' + w + 'px">' + suggestions[i] + '</div>',
			suggestionNode = Y.Node.create(nodeContent);
			suggestionNode.on('click', sendOnclick);
			Y.one('body').append(suggestionNode),
			y += suggestionNode.getDOMNode().offsetHeight;
		}
	}
};

}, '@VERSION@', {"requires": ["base", "node"]});
