M.block_meineschulen_search = {
    init_course_search: function(Y, opts) {
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
    },

    init_school_search: function(Y) {
        var searchform, waitimg;

        waitimg = '<img src="' + M.util.image_url('i/ajaxloader', 'moodle') + '" />';

        searchform = Y.one('#meineschulen_school_form');
        searchform.on('submit', function (e) {
            var url, searchtext, schooltype, resultel;
            e.preventDefault();
            e.stopPropagation();

            searchtext = Y.Lang.trim(this.one('#schoolname').get('value'));
            schooltype = this.one('#schooltype').get('selectedIndex');
            schooltype = this.one('#schooltype').get('options').item(schooltype).get('value');
            if (searchtext) {
                resultel = Y.one('#meineschulen_school_results');
                resultel.setContent(waitimg);

                url = M.cfg.wwwroot + '/blocks/meineschulen/ajax.php';
                Y.io(url, {
                    data: {
                        action: 'schoolsearch',
                        search: searchtext,
                        schooltype: schooltype
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