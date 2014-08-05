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
        update_paging_links();
        check_send_school_search(true); // Catch situations where the back button has been pressed and the search needs repeating.

        function check_send_school_search(onload) {
            var searchtype, searchtext, schooltype, numberofresults;

            searchtype = 'school';
            if (Y.one('#meineschulen_school_form #searchtype_course').get('checked')) {
                searchtype = 'course';
            }
            searchtext = Y.Lang.trim(Y.one('#meineschulen_school_form #schoolname').get('value'));
            schooltype = Y.one('#meineschulen_school_form #schooltype').get('selectedIndex');
            schooltype = Y.one('#meineschulen_school_form #schooltype').get('options').item(schooltype).get('value');
            schooltype = parseInt(schooltype, 10);
            numberofresults = Y.one('#meineschulen_school_form #numberofresults').get('selectedIndex');
            numberofresults = Y.one('#meineschulen_school_form #numberofresults').get('options').item(numberofresults).get('value');
            numberofresults = parseInt(numberofresults, 10);

            if (onload) {
                if (!search_params_changed(searchtype, searchtext, schooltype, numberofresults)) {
                    return; // No change, so do not refresh the results.
                }
            }

            send_school_search(searchtype, searchtext, schooltype, numberofresults);
        }

        function search_params_changed(searchtype, searchtext, schooltype, numberofresults) {
            var pagequery, queryparams, pos, defaultquery, i;

            defaultquery = {
                searchtype: 'school',
                schoolname: "",
                schooltype: -1,
                numberofresults: 20
            };
            pagequery = window.location.href;
            pos = pagequery.indexOf('?');
            if (pos !== -1) {
                pagequery = pagequery.substring(pos + 1);
                if (!pagequery) {
                    queryparams = {};
                } else {
                    queryparams = Y.QueryString.parse(pagequery);
                }
            } else {
                queryparams = {};
            }
            for (i in defaultquery) {
                if (defaultquery.hasOwnProperty(i)) {
                    if (queryparams[i] === undefined) {
                        queryparams[i] = defaultquery[i];
                    }
                }
            }

            if (queryparams.searchtype !== searchtype) {
                return true;
            }
            if (queryparams.schoolname !== searchtext) {
                return true;
            }
            queryparams.schooltype = parseInt(queryparams.schooltype, 10);
            if (queryparams.schooltype !== schooltype) {
                return true;
            }
            queryparams.numberofresults = parseInt(queryparams.numberofresults, 10);
            if (queryparams.numberofresults !== numberofresults) {
                return true;
            }

            return false;
        }

        function send_school_search(searchtype, searchtext, schooltype, numberofresults, sortby, sortdir, page) {
            var searchouter, resultel, url, data;

            searchouter = Y.one('.meineschulen_content .meineschulen_school_results');
            searchouter.removeClass('hidden');

            resultel = Y.one('#meineschulen_school_results');
            resultel.setContent(waitimg);

            data = {
                action: 'schoolsearch',
                search: searchtext,
                schooltype: schooltype,
                searchtype: searchtype
            };
            if (numberofresults !== undefined) {
                data.numberofresults = numberofresults;
            }
            if (sortby !== undefined) {
                data.sortby = sortby;
            }
            if (sortdir !== undefined) {
                data.sortdir = sortdir;
            }
            if (page !== undefined) {
                data.page = page;
            }

            url = M.cfg.wwwroot + '/blocks/meineschulen/ajax.php';
            Y.io(url, {
                data: data,
                on: {
                    success: function (id, resp) {
                        var details;
                        details = Y.JSON.parse(resp.responseText);
                        if (details && details.error === 0) {
                            resultel.setContent(details.results);
                            update_sort_links();
                            update_paging_links();
                            if (!details.results) {
                                searchouter.addClass('hidden');
                            }
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

                send_school_search(linkparams.searchtype, linkparams.schoolname, linkparams.schooltype, linkparams.numberofresults, linkparams.sortby, linkparams.sortdir);

                return false;
            });
        }

        function update_paging_links() {
            // Adjust the paging links to submit via AJAX
            Y.all('#meineschulen_school_results .paging a').on('click', function (e) {
                var link, linkparams;

                e.preventDefault();
                e.stopPropagation();

                link = e.currentTarget.get('href');
                link = link.substring(link.indexOf('?') + 1);
                linkparams = Y.QueryString.parse(link);

                send_school_search(linkparams.searchtype, linkparams.schoolname, linkparams.schooltype, linkparams.numberofresults, linkparams.sortby, linkparams.sortdir, linkparams.page);

                return false;
            });
        }

    }
};