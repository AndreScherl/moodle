/*global M*/
M.block_meinesuche = M.block_meinesuche || {};
M.block_meinesuche.blocksearch = {
    init: function(opts) {
        var searchbox, resultel, searchtypeel;

        searchtypeel = Y.one('#meinesuche_school_form #searchtype_course');
        searchbox = Y.one('#meinesuche_school_form #schoolname');
        searchbox.plug(Y.Plugin.AutoComplete, {
            resultFilters: null,
            resultHighligher: 'phraseMatch',
            maxResults: 11,
            source: function(search, callback) {
                
                if (search.length < 3) {
                    return;
                }
                
                var searchtype;
                searchtype = 'school';
                if (searchtypeel.get('checked')) {
                    searchtype = 'course';
                }
                Y.io(opts.url, {
                    data: {
                        action: 'blockschoolsearch',
                        search: search,
                        searchtype: searchtype
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

        // Attach the autocomplete results box to the body tag (to avoid overflow:hidden clipping).
        resultel = searchbox.next('.yui3-aclist');
        resultel.appendTo('body');

        Y.all('#meinesuche_school_form .searchtype input').on('click', function(e) {
            
            var val = e.currentTarget.get('value');
            M.util.set_user_preference('block_meinesuche_searchtype', val);
            
            // Display the search context as placeholder text of input field
            var placeholder = e.currentTarget.getAttribute('data-action');
            searchbox.set('placeholder', placeholder);
        });
    }
};
