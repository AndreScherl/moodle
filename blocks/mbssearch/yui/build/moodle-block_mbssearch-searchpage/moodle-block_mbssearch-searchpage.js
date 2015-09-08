YUI.add('moodle-block_mbssearch-searchpage', function (Y, NAME) {

M.block_mbssearch = M.block_mbssearch || {};
M.block_mbssearch.initsearchpage = function (opts) {

    var limitfrom;
    var limitnum;
    var schoolcatid;
    var spinnernode;

    function loadMoreResults() {

        var data = {};
        data.action = 'loadmoreresults';
        data.searchtext = Y.one('#mbssearchpage_form #searchtext').get('value');
        data.limitfrom = limitfrom;
        data.limitnum = limitnum;
        data.schoolcatid = schoolcatid;
        data.filterby = Y.one('#mbssearchpage_form #menufilterby').get('value'); 

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

        Y.one('#mbssearch_resultlist').append(results.html);

        limitfrom = limitfrom + limitnum;
    }

    function initialize() {

        limitfrom = Number(opts.limitfrom);
        limitnum = Number(opts.limitnum);
        schoolcatid = Number(opts.schoolcatid);
        
        spinnernode = Y.one('#loadmoreresults');
        
        Y.one('#mbssearchpage_form #menufilterby').on('change', function(e) {
            Y.one('#mbssearchpage_form').submit();
        });

        if (spinnernode) {
            spinnernode.set('href', '#');
        }

        Y.one('#mbssearch_result').delegate('click',
            function(e) {
                e.preventDefault();
                loadMoreResults();
            }, '#loadmoreresults'
            );
    }

    initialize();
};


}, '@VERSION@');
