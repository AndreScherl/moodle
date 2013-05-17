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

                resultel.setContent(waitimg);

                url = M.cfg.wwwroot + '/blocks/meinekurse/ajax.php';
                Y.io(url, {
                    data: {
                        action: 'getcourses',
                        meinekurse_sortby: meinekurse_sortby,
                        meinekurse_numcourses: meinekurse_numcourses,
                        pageurl: opts.pageurl
                    },
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

                return false;
            });
        },
    }
}, '@VERSION@', {
    requires: ['node', 'event', 'io', 'json']
});