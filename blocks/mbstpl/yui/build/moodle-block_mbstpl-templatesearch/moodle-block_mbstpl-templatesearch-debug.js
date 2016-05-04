YUI.add('moodle-block_mbstpl-templatesearch', function (Y, NAME) {

M.block_mbstpl = M.block_mbstpl || {};
M.block_mbstpl.templatesearch = {};

M.block_mbstpl.templatesearch.init = function (opts) {

    var limitfrom;
    var limitnum;
    var loadmorediv;

    function loadMoreResults() {

        var data = {};
        
        data.action = 'loadmoreresults';
        data.limitfrom = limitfrom;
        data.limitnum = limitnum;

        var spinner = M.util.add_spinner(Y, loadmorediv);

        var formobject = {
            id: 'mbstpl-search-form',
            useDisabled: true
        };

        Y.io(opts.ajaxurl, {
            method: 'POST',
            form: formobject,
            data: data,
            on: {
                start: function () {

                    spinner.show();
                },
                success: function (id, resp) {
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
                failure: function () {
                    spinner.hide();
                }
            }
        });

    }

    function onLoadMoreResults(results) {

        if (results.limitfrom + results.limitnum >= results.total) {
            loadmorediv.hide();
        }

        Y.one('#mbstpl-search-listing').append(results.html);

        limitfrom = limitfrom + limitnum;
    }

    function initialize() {

        limitfrom = Number(opts.limitfrom);
        limitnum = Number(opts.limitnum);

        loadmorediv = Y.one('#mbstpl-search-loadmoreresults');
        if (loadmorediv) {
            loadmorediv.on('click', function (e) {
                e.preventDefault();
                loadMoreResults();
            });
        }


    }

    initialize();
};

}, '@VERSION@', {"requires": ["base", "node", "io-form"]});
