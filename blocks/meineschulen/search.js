M.block_meineschulen_search = {
    init_course_search: function(Y, opts) {
        var searchform, waitimg;

        waitimg = '<img src="' + M.util.image_url('i/ajaxloader', 'moodle') + '" />';

        searchform = Y.one('#meineschulen_search_form');
        searchform.on('submit', function (e) {
            var searchtext;
            e.preventDefault();
            e.stopPropagation();

            searchtext = Y.Lang.trim(this.one('#meineschulen_search_text').get('value'));
            if (searchtext) {
                send_course_search(searchtext);
            }
            return false;
        });

        update_sort_links();

        function send_course_search(searchtext, sortby, sortdir) {
            var url, resultel, data;

            resultel = Y.one('#meineschulen_search_results');
            resultel.setContent(waitimg);

            data = {
                id: opts.schoolid,
                action: 'search',
                search: searchtext
            };
            if (sortby !== undefined) {
                data.sortby = sortby;
            }
            if (sortdir !== undefined) {
                data.sortdir = sortdir;
            }

            url = M.cfg.wwwroot + '/blocks/meineschulen/ajax.php';
            Y.io(url, {
                data: data,
                on: {
                    success: function (id, resp) {
                        var details;
                        details = Y.JSON.parse(resp.responseText);
                        if (details && details.error === 0 && details.results) {
                            resultel.setContent(details.results);
                            update_sort_links();
                        }
                    }
                }
            });
        }

        function update_sort_links() {
            // Adjust the sortorder links to submit via AJAX
            Y.all('#meineschulen_search_results table th a').on('click', function (e) {
                var link, linkparams;

                e.preventDefault();
                e.stopPropagation();

                link = e.currentTarget.get('href');
                link = link.substring(link.indexOf('?') + 1);
                linkparams = Y.QueryString.parse(link);

                send_course_search(linkparams.search, linkparams.sortby, linkparams.sortdir);

                return false;
            });
        }
    },

    init_school_search: function(Y) {
        var searchform, waitimg;

        waitimg = '<img src="' + M.util.image_url('i/ajaxloader', 'moodle') + '" />';

        searchform = Y.one('#meineschulen_school_form');
        searchform.on('submit', function (e) {
            e.preventDefault();
            e.stopPropagation();
            check_send_school_search(false);
            return false;
        });

        update_sort_links();
        check_send_school_search(true); // Catch situations where the back button has been pressed and the search needs repeating.

        function check_send_school_search(onload) {
            var searchtext, schooltype, pagequery;

            searchtext = Y.Lang.trim(Y.one('#meineschulen_school_form #schoolname').get('value'));
            schooltype = Y.one('#meineschulen_school_form #schooltype').get('selectedIndex');
            schooltype = Y.one('#meineschulen_school_form #schooltype').get('options').item(schooltype).get('value');
            if (searchtext) {
                if (onload) {
                    pagequery = window.location.href;
                    pagequery = pagequery.substring(pagequery.indexOf('?') + 1);
                    if (pagequery) {
                        pagequery = Y.QueryString.parse(pagequery);
                        if (pagequery.schoolname === searchtext) {
                            // Don't repeat the search if the search has already been performed via the page params.
                            return;
                        }
                    }
                }
                send_school_search(searchtext, schooltype);
            }
        }

        function send_school_search(searchtext, schooltype, sortby, sortdir) {
            var resultel, url, data;
            resultel = Y.one('#meineschulen_school_results');
            resultel.setContent(waitimg);

            data = {
                action: 'schoolsearch',
                search: searchtext,
                schooltype: schooltype
            };
            if (sortby !== undefined) {
                data.sortby = sortby;
            }
            if (sortdir !== undefined) {
                data.sortdir = sortdir;
            }

            url = M.cfg.wwwroot + '/blocks/meineschulen/ajax.php';
            Y.io(url, {
                data: data,
                on: {
                    success: function (id, resp) {
                        var details;
                        details = Y.JSON.parse(resp.responseText);
                        if (details && details.error === 0 && details.results) {
                            resultel.setContent(details.results);
                            update_sort_links();
                        }
                    }
                }
            });
        }

        function update_sort_links() {
            // Adjust the sortorder links to submit via AJAX
            Y.all('#meineschulen_school_results table th a').on('click', function (e) {
                var link, linkparams;

                e.preventDefault();
                e.stopPropagation();

                link = e.currentTarget.get('href');
                link = link.substring(link.indexOf('?') + 1);
                linkparams = Y.QueryString.parse(link);

                send_school_search(linkparams.schoolname, linkparams.schooltype, linkparams.sortby, linkparams.sortdir);

                return false;
            });
        }

    }
};