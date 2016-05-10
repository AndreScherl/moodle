M.local_mbs = M.local_mbs || {};
M.local_mbs.lookupset = {
    init: function (opts) {

        var resultel, moreresults, noresults, lessletters;
        var formelement = Y.one('#id_' + opts.name);
        var searchbox = Y.one('#id_' + opts.name + '_search');
        var list = Y.one('#id_' + opts.name + '_list');

        searchbox.plug(Y.Plugin.AutoComplete, {
            resultFilters: null,
            resultHighligher: 'phraseMatch',
            maxResults: opts.lookupcount,
            source: function (searchtext, callback) {

                var index = searchtext.lastIndexOf(']');
                searchtext = searchtext.substr(index + 1, searchtext.length);

                if (searchtext.length < 3) {
                    lessletters.show();
                    moreresults.hide();
                    noresults.hide();
                    return;
                }

                var params = {
                    searchtext: searchtext
                };

                // Fetch additional params, when available.
                for (var i = 0; i < opts.ajaxparamnames.length; i++) {
                    var name = opts.ajaxparamnames[i];
                    var el = Y.one('#' + name);
                    if (el) {
                        params[name] = el.get('value');
                    }
                }

                Y.io(opts.url, {
                    data: params,
                    on: {
                        success: function (id, resp) {
                            var result;
                            try {
                                result = Y.JSON.parse(resp.responseText);
                            } catch (e) {
                                return;
                            }
                            if (result.error !== 0) {
                                alert(result.error);
                            } else {
                                if (result.results.length > opts.lookupcount) {
                                    moreresults.show();
                                    noresults.hide();
                                    lessletters.hide();
                                } else if (result.results.length === 0) {
                                    lessletters.hide();
                                    noresults.show();
                                } else {
                                    moreresults.hide();
                                    noresults.hide();
                                    lessletters.hide();
                                }
                                callback(result.results);
                            }
                        }
                    }
                });
            },
            resultFormatter: function (search, results) {
                return Y.Array.map(results, function (result) {
                    return result.raw;
                });
            }
        });

        searchbox.ac.on('select', function (e) {

            e.preventDefault();

            var clickeditem = e.itemNode.one('span');

            var id = clickeditem.get('id');
            var content = clickeditem.getContent();

            var html = content;
            html += "&nbsp;<span class='flookupset-delete'>";
            html += "<img src='" + M.util.image_url('t/delete') + "' alt = '" + M.str.moodle.delete + "' title='" + M.str.moodle.delete + "'/>";
            html += "</span>";
            html += "<input type='hidden' name='" + opts.nameselected + "[" + id + "]' value='" + content + "'/>";

            // Append a new list node.
            list.append(Y.Node.create('<li>' + html + '</li>'));

            // Set the value of the hidden formelement to indicate filled list for require rule!
            formelement.set('value', 'filled');
            formelement.simulate('change');

            searchbox.ac.hide();
            searchbox.set('value', '');

        });

        list.delegate('click', function (e) {

            e.currentTarget.ancestor().remove(true);

            if (!list.hasChildNodes()) {
                // Remove the value of the hidden formelement to indicate empty list for require rule!
                formelement.set('value', '');
            }

            formelement.simulate('change');
        }, 'span');

        resultel = searchbox.next('.yui3-aclist');
        // Attach the autocomplete results box to the body tag (to avoid overflow:hidden clipping).
        resultel.appendTo('body');
        
        // Conditionally show divs with more explanation.
        moreresults = Y.Node.create('<div class="moreresults">' + M.util.get_string('lookupsetmoreresults', 'local_mbs') + '</div>');
        moreresults.hide();
        resultel.one('.yui3-aclist-content').append(moreresults);
        
        noresults = Y.Node.create('<div class="noresults">' + M.util.get_string('lookupsetnoresults', 'local_mbs') + '</div>');
        noresults.hide();
        searchbox.ancestor().append(noresults);
        
        lessletters = Y.Node.create('<div class="toolessletters">' + M.util.get_string('lookupsetlessletters', 'local_mbs') + '</div>');
        lessletters.hide();
        searchbox.ancestor().append(lessletters);
    }
};
