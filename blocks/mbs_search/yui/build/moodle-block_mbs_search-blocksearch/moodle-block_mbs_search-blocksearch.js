YUI.add('moodle-block_mbs_search-blocksearch', function (Y, NAME) {

M.block_mbs_search = M.block_mbs_search || {};
M.block_mbs_search.blocksearch = {

    init: function(opts) {

        var searchbox, resultel, schoolcatidbox;

        searchbox = Y.one('#mbs_search_form #searchtext');
        schoolcatidbox = Y.one('#mbs_search_form #search_schoolcatid');

        searchbox.plug(Y.Plugin.AutoComplete, {

            resultFilters: null,
            resultHighligher: 'phraseMatch',
            maxResults: 2 * (opts.lookupcount + 1),

            source: function(searchtext, callback) {

                if (searchtext.length < 3) {
                    return;
                }

                var schoolcatid = 0;

                if (schoolcatidbox && schoolcatidbox.get('checked')) {
                    schoolcatid = schoolcatidbox.get('value');
                }

                Y.io(opts.url, {

                    data: {
                        action: 'blockschoolsearch',
                        searchtext: searchtext,
                        schoolcatid : schoolcatid
                    },
                    on: {
                        success: function(id, resp) {
                            var result;
                            try {
                                result = Y.JSON.parse(resp.responseText);
                            } catch (e) {
                                return;
                            }
                            if (result.error !== 0) {
                                alert(result.error);
                            } else {
                                callback(result.results);
                            }
                        }
                    }
                });
            },
            resultFormatter: function(search, results) {
                return Y.Array.map(results, function(result) {
                    return result.raw;
                });
            }
        });

        searchbox.ac.on('select', function(e) {
            var linkel;
            e.preventDefault(); // Make sure the default action is not done.
            if (e.itemNode.get('tagname') === 'a') {
                return;
            }
            linkel = e.itemNode.one('a');
            window.location = linkel.get('href');
        });

        if (schoolcatidbox) {

           schoolcatidbox.on('click', function (e) {
               searchbox.ac.sendRequest();
           });
        }

        // Attach the autocomplete results box to the body tag (to avoid overflow:hidden clipping).
        resultel = searchbox.next('.yui3-aclist');
        resultel.appendTo('body');
    }
};


}, '@VERSION@', {
    "requires": [
        "base",
        "node",
        "io-base",
        "json",
        "autocomplete",
        "autocomplete-highlighters",
        "autocomplete-filters",
        "node-event-simulate"
    ]
});
