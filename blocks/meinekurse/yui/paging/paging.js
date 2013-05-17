YUI.add('moodle-block_meinekurse-paging', function(Y) {
    M.block_meinekurse = M.block_meinekurse || {};
    M.block_meinekurse.paging = {
        opts: null,
        waitimg: null,

        init: function(opts) {
            var selects, waitimg;

            this.opts = opts;
            this.waitimg = '<img src="' + M.util.image_url('i/ajaxloader', 'moodle') + '" />';

            selects = Y.all('.block_meinekurse .content form select');
            selects.on('change', function (e) {
                var form, meinekurse_sortby, meinekurse_numcourses, url, resultel;
                e.preventDefault();
                e.stopPropagation();

                form = e.currentTarget.ancestor('form');
                resultel = form.ancestor().one('.courseandpaging');
                meinekurse_sortby = form.one('.meinekurse_sortby').get('value');
                meinekurse_numcourses = form.one('.meinekurse_numcourses').get('value');

                this.do_course_search(resultel, meinekurse_sortby, meinekurse_numcourses);

                return false;
            }, this);

            this.setup_hover();
        },

        do_course_search: function(resultel, sortby, numcourses, page) {
            var url, data, self;

            resultel.setContent(this.waitimg);

            data = {
                action: 'getcourses',
                pageurl: this.opts.pageurl
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

            self = this;
            url = M.cfg.wwwroot + '/blocks/meinekurse/ajax.php';
            Y.io(url, {
                data: data,
                on: {
                    complete: function (id, resp) {
                        var details;
                        details = Y.JSON.parse(resp.responseText);
                        if (details && details.error == 0 && details.content) {
                            resultel.setContent(details.content);
                            self.setup_hover(resultel);
                        }
                    }
                }
            });
        },

        setup_hover: function(resultel) {
            var rows, containers;
            //Mouseover event hook for table rows
            if (resultel === undefined) {
                rows = Y.all('.mycoursestabs table.meinekursetable tr');
            } else {
                rows = resultel.all('table.meinekursetable tr');
            }
            rows.on('mouseenter', function(e) {
                var row, rowcontent, containerdiv, detailsdiv;

                row = e.currentTarget;
                row.siblings().removeClass('hover');
                row.addClass('hover');
                rowcontent = row.one('td.moddesc-hidden').getContent();

                containerdiv = row.ancestor('div.coursecontainer');
                detailsdiv = containerdiv.one('.coursecontent');
                detailsdiv.setContent(rowcontent);
                detailsdiv.setStyle('height', 'auto');
                if (detailsdiv.getComputedStyle('height') < containerdiv.getComputedStyle('height')) {
                    detailsdiv.setStyle('height', containerdiv.getComputedStyle('height'));
                }
            });

            //Mouseout event hook for table rows
            if (resultel === undefined) {
                containers = Y.all('.mycoursestabs .coursecontainer');
            } else {
                containers = resultel.all('.coursecontainer');
            }
            containers.on('mouseleave', function(e) {
                var div, content;

                div = e.currentTarget;
                content = div.one('div.coursecontent');
                content.setContent('');
                content.setStyle('height', 'auto');
                div.all('table.meinekursetable tr').removeClass('hover');
            });
        }
    }
}, '@VERSION@', {
    requires: ['node', 'event', 'io', 'json']
});