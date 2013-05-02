M.block_meineschulen_search = {
    init: function(Y, opts) {
        var searchform, waitimg;

        waitimg = '<img src="' + M.util.image_url('i/ajaxloader', 'moodle') + '" />';

        searchform = Y.one('#meineschulen_search_form');
        searchform.on('submit', function (e) {
            var url, searchtext, resultel;
            e.preventDefault();
            e.stopPropagation();

            searchtext = Y.Lang.trim(this.one('#meineschulen_search_text').get('value'));
            if (searchtext) {
                resultel = Y.one('#meineschulen_search_results');
                resultel.setContent(waitimg);

                url = M.cfg.wwwroot + '/blocks/meineschulen/ajax.php';
                Y.io(url, {
                    data: {
                        id: opts.schoolid,
                        action: 'search',
                        search: searchtext
                    },
                    on: {
                        success: function (id, resp) {
                            var details;
                            details = Y.JSON.parse(resp.responseText);
                            if (details && details.error === 0 && details.results) {
                                resultel.setContent(details.results);
                            }
                        }
                    }
                });
            }
            return false;
        });
    }
};