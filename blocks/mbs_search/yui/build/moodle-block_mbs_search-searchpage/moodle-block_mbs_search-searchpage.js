YUI.add('moodle-block_mbs_search-searchpage', function (Y, NAME) {

M.block_mbs_search = M.block_mbs_search || {};
M.block_mbs_search.initsearchpage = function (opts) {

    var limitfrom;
    var limitnum;
    var spinnernode;

    function loadMoreResults() {

        var data = {};
        data.action = 'loadmoreresults';
        data.searchtext = Y.one('#mbs_searchpage_form #searchtext').get('value');
        data.limitfrom = limitfrom;
        data.limitnum = limitnum;
        data.filterby = Y.one('#mbs_searchpage_form #menufilterby').get('value');

        var spinner = M.util.add_spinner(Y, spinnernode);

        Y.io(opts.url, {

            data: data ,
            on: {
                start : function() {

                    spinner.show();
                },
                success: function(id, resp) {
                    var result;
                    try {
                        result = Y.JSON.parse(resp.responseText);
                    } catch (e) {
                        return;
                    }
                    spinner.hide();
                    if (result.error !== 0) {
                        alert(result.error);
                    } else {
                        onLoadMoreResults(result.results);
                    }
                },
                failure: function() {
                    spinner.hide();
                }
            }
        });

    }

    function onLoadMoreResults(results) {

        if (results.limitfrom + results.limitnum >= results.total) {
            Y.one('#loadmoreresults').hide();
        }

        Y.one('#mbs_search_resultlist').append(results.html);

        limitfrom =limitfrom + limitnum;
    }

    function initialize() {

        limitfrom = Number(opts.limitfrom);
        limitnum = Number(opts.limitnum);

        spinnernode = Y.one('#loadmoreresults');
        
        Y.one('#mbs_searchpage_form #menufilterby').on('change', function(e) {
            Y.one('#mbs_searchpage_form').submit();
        });

        if (spinnernode) {
            spinnernode.set('href', '#');
        }

        Y.one('#mbs_search_result').delegate('click',
            function(e) {
                e.preventDefault();
                loadMoreResults();
            }, '#loadmoreresults'
            );
    }

    initialize();
};


}, '@VERSION@');
