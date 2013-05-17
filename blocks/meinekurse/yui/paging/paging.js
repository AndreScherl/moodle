YUI.add('moodle-block_meinekurse-paging', function(Y) {
    M.block_meinekurse = M.block_meinekurse || {};
    M.block_meinekurse.paging = {
        init: function(opts) {
            var selects, waitimg;

            waitimg = '<img src="' + M.util.image_url('i/ajaxloader', 'moodle') + '" />';
            selects = Y.all('.block_meinekurse .content form select');
            selects.on('change', function (e) {
                var form, meinekurse_sortby, meinekurse_numcourses, url, resultel;
                e.preventDefault();
                e.stopPropagation();

                form = e.currentTarget.ancestor('form');
                resultel = form.ancestor().one('.courseandpaging');
                meinekurse_sortby = form.one('.meinekurse_sortby').get('value');
                meinekurse_numcourses = form.one('.meinekurse_numcourses').get('value');

                do_course_search(resultel, meinekurse_sortby, meinekurse_numcourses);

                return false;
            });

            function do_course_search(resultel, sortby, numcourses, page) {
                var url, data;

                resultel.setContent(waitimg);

                data = {
                    action: 'getcourses',
                    pageurl: opts.pageurl
                };
                if (sortby !== undefined) {
                    data.meinekurse_sortby = sortby;
                }
                if (numcourses !== undefined) {
                    data.meinekurse_numcourses = numcourses;
                }
                if (page !== undefined) {
                    data.meinekurse_page = page;
                }

                url = M.cfg.wwwroot + '/blocks/meinekurse/ajax.php';
                Y.io(url, {
                    data: data,
                    on: {
                        complete: function (id, resp) {
                            var details;
                            details = Y.JSON.parse(resp.responseText);
                            if (details && details.error == 0 && details.content) {
                                resultel.setContent(details.content);
                            }
                        }
                    }
                });
            }
        }
    }
}, '@VERSION@', {
    requires: ['node', 'event', 'io', 'json']
});