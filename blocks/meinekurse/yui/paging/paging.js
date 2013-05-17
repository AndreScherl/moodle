YUI.add('moodle-block_meinekurse-paging', function(Y) {
    M.block_meinekurse = M.block_meinekurse || {};
    M.block_meinekurse.paging = {
        opts: null,
        waitimg: null,
        lastsortby: null,
        lastnumcourses: null,

        init: function(opts) {
            var selects, tablinks;

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

                this.lastsortby = meinekurse_sortby;
                this.lastnumcourses = meinekurse_numcourses;

                this.do_course_search(resultel, meinekurse_sortby, meinekurse_numcourses);

                return false;
            }, this);

            this.setup_hover();
            this.setup_paging();

            // Set up the tabs to notify the server when the tab has changed.
            tablinks = Y.all('.block_meinekurse .mycoursestabs ul.ui-tabs-nav li a');
            tablinks.on('click', function(e) {
                var tabnum, tabid, form, resultel, meinekurse_sortby, meinekurse_numcourses, needsresend;
                tabnum = e.currentTarget.get('href');
                tabnum = tabnum.match(/#school(.*)tab/);
                if (tabnum.length >= 2) {
                    // Let the server know the currently-selected school.
                    tabnum = parseInt(tabnum[1], 10);
                    var params = {
                        action: 'setschool',
                        schoolid: tabnum,
                        sesskey: M.cfg.sesskey
                    };
                    Y.io(M.cfg.wwwroot+'/blocks/meinekurse/ajax.php', {
                        data: params
                    });

                    // Check if the sort criteria was changed on another tab and update the course list for this tab, if needed.
                    tabid = 'school' + tabnum + 'tab';
                    form  = Y.one('.block_meinekurse #' + tabid + ' form');
                    meinekurse_sortby = form.one('.meinekurse_sortby').get('value');
                    meinekurse_numcourses = form.one('.meinekurse_numcourses').get('value');

                    needsresend = (this.lastsortby !== null && this.lastsortby !== meinekurse_sortby);
                    needsresend = needsresend || (this.lastnumcourses !== null && this.lastnumcourses !== meinekurse_numcourses);
                    if (needsresend) {
                        resultel = form.ancestor().one('.courseandpaging');
                        this.do_course_search(resultel, this.lastsortby, this.lastnumcourses);

                        form.one('.meinekurse_sortby').set('value', this.lastsortby);
                        form.one('.meinekurse_numcourses').set('value', this.lastnumcourses);
                    }
                }

            }, this);
        },

        do_course_search: function(resultel, sortby, numcourses, page) {
            var url, data, self;

            resultel.one('.coursecontainer').setContent(this.waitimg);

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
                            self.setup_paging(resultel);
                        }
                    }
                }
            });
        },

        setup_hover: function(parentel) {
            var rows, containers;
            //Mouseover event hook for table rows
            if (parentel === undefined) {
                rows = Y.all('.mycoursestabs table.meinekursetable tr');
            } else {
                rows = parentel.all('table.meinekursetable tr');
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
            if (parentel === undefined) {
                containers = Y.all('.mycoursestabs .coursecontainer');
            } else {
                containers = parentel.all('.coursecontainer');
            }
            containers.on('mouseleave', function(e) {
                var div, content;

                div = e.currentTarget;
                content = div.one('div.coursecontent');
                content.setContent('');
                content.setStyle('height', 'auto');
                div.all('table.meinekursetable tr').removeClass('hover');
            });
        },

        setup_paging: function(parentel) {
            var paginglinks;
            if (parentel === undefined) {
                paginglinks = Y.all('.mycoursestabs .courseandpaging .paging a');
            } else {
                paginglinks = parentel.all('.paging a');
            }

            paginglinks.on('click', function(e) {
                var link, linkparams, resultel;

                e.preventDefault();
                e.stopPropagation();

                link = e.currentTarget.get('href');
                link = link.substring(link.indexOf('?') + 1);
                linkparams = Y.QueryString.parse(link);

                resultel = e.currentTarget.ancestor('.courseandpaging');
                this.do_course_search(resultel, undefined, undefined, linkparams.meinekurse_page);

                return false;
            }, this);
        }
    }
}, '@VERSION@', {
    requires: ['node', 'event', 'io', 'json', 'querystring']
});