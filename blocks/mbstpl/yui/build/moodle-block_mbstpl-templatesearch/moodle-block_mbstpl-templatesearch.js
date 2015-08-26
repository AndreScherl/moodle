YUI.add('moodle-block_mbstpl-templatesearch', function (Y, NAME) {

M.block_mbstpl = M.block_mbstpl || {};
M.block_mbstpl.templatesearch = {

	init : function() {
		Y.one('input#id_keyword').on('keyup', function(e) {
			var keyword = e.currentTarget.getDOMNode().value;

			// Kick in after 3 chars.
			if (keyword.length > 3) {
				M.block_mbstpl.templatesearch.requestSuggestions(keyword);
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

	renderAutocomplete: function(id, data) {
		try {
			// TODO: Render suggestion items.
			var suggestions = JSON.parse(data.response);
			console.log(suggestions);
		} catch (err) {
		}
	}
};

}, '@VERSION@', {"requires": ["base", "node"]});
