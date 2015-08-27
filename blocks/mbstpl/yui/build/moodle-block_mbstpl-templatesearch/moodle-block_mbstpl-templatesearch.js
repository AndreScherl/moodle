YUI.add('moodle-block_mbstpl-templatesearch', function (Y, NAME) {

M.block_mbstpl = M.block_mbstpl || {};
M.block_mbstpl.templatesearch = {

	init : function() {
		Y.one('input#id_keyword').on('keyup', function(e) {
			var keyword = e.currentTarget.getDOMNode().value;

			// Kick in after 3 chars.
			if (keyword.length >= 3) {
				M.block_mbstpl.templatesearch.requestSuggestions(keyword);
			} else {
				Y.all('.mbstpl-suggestion').remove();
			}
		});

		Y.all('mbstpl-list-controller img').on('click', function(e) {
			// TODO: Update hidden layout field and repost the search form.
			e.preventDefault();
		});
	},

	requestSuggestions : function(keyword) {
		var request = {
			method : "POST",
			sync : false,
			timeout : 5000,
			data : { 'sesskey' : M.cfg.sesskey, 'keyword' : keyword },
			on : {
				success : M.block_mbstpl.templatesearch.renderAutocomplete,
			}
		}
		Y.io(M.cfg.wwwroot + '/blocks/mbstpl/autocomplete.php', request);
	},

	selectSuggestion: function(e) {
		var value = e.currentTarget.getHTML();
		Y.one('input#id_keyword').set('value', value);
		Y.all('.mbstpl-suggestion').remove();
	},

	renderAutocomplete: function(id, data) {
		Y.all('.mbstpl-suggestion').remove();
		var suggestions = JSON.parse(data.response),
		inputField = Y.one('input#id_keyword'),
		w = inputField.getDOMNode().offsetWidth,
		x = inputField.getX();
		y = inputField.getY() + inputField.getDOMNode().offsetHeight;

		for (var i = 0; i < suggestions.length; i++) {
			var nodeContent = '<div class="mbstpl-suggestion" style=" top:' + y + 
			'px; left:' + x + 'px;width:' + w + 'px">' + suggestions[i] + '</div>',
			suggestionNode = Y.Node.create(nodeContent);
			suggestionNode.on('click', M.block_mbstpl.templatesearch.selectSuggestion);
			Y.one('body').append(suggestionNode),
			y += suggestionNode.getDOMNode().offsetHeight;
		}
	}
};

}, '@VERSION@', {"requires": ["base", "node"]});
