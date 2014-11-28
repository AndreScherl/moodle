/*global M*/
YUI.add('moodle-block_meinek_old-paging', function(Y) {
    "use strict";
    M.block_meinek_old = M.block_meinek_old || {};
    M.block_meinek_old.paging = {
        opts: null,
        waitimg: null,
        lastsortby: null,
        lastnumcourses: null,

        init: function(opts) {
            var selects, tablinks, sortdirs;

            this.opts = opts;
            this.waitimg = '<img src="' + M.util.image_url('i/ajaxloader', 'moodle') + '" />';

            selects = Y.all('.block_meinek_old .content form select');
            selects.on('change', function (e) {
                var form, meinek_old_sortby, meinek_old_numcourses, resultel, meinek_old_otherschoolid;
                e.preventDefault();
                e.stopPropagation();

                form = e.currentTarget.ancestor('form');
                resultel = form.ancestor().one('.courseandpaging');

                meinek_old_sortby = form.one('.meinek_old_sortby').get('value');
                meinek_old_numcourses = form.one('.meinek_old_numcourses').get('value');
                meinek_old_otherschoolid = form.one('.meinek_old_otherschoolid');
                if (meinek_old_otherschoolid) {
                    meinek_old_otherschoolid = meinek_old_otherschoolid.get('value');
                } else {
                    meinek_old_otherschoolid = undefined;
                }

                this.lastsortby = meinek_old_sortby;
                this.lastnumcourses = meinek_old_numcourses;

                this.do_course_search(resultel, meinek_old_sortby, meinek_old_numcourses, undefined, undefined,
                    meinek_old_otherschoolid);

                return false;
            }, this);

            sortdirs = Y.all('.block_meinek_old .sorticon');
            sortdirs.on('click', function(e) {
                var resultel, sortdir, form;
                e.preventDefault();
                form = e.currentTarget.ancestor('form');
                resultel = form.ancestor().one('.courseandpaging');

                sortdir = 'desc';
                if (e.currentTarget.hasClass('sortdesc')) {
                    sortdir = 'asc';
                }
                this.do_course_search(resultel, undefined, undefined, undefined, undefined, undefined, sortdir);

            }, this);

            this.setup_hover();
            this.setup_paging();

            // Set up the tabs to notify the server when the tab has changed.
            tablinks = Y.all('.block_meinek_old .mycoursestabs ul.ui-tabs-nav li a');
            tablinks.on('click', function(e) {
                var tabnum, tabid, form, resultel, meinek_old_sortby, meinek_old_numcourses, needsresend;
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
                    Y.io(M.cfg.wwwroot+'/blocks/meinek_old/ajax.php', {
                        data: params
                    });

                    // Check if the sort criteria was changed on another tab and update the course list for this tab, if needed.
                    tabid = 'school' + tabnum + 'tab';
                    form  = Y.one('.block_meinek_old #' + tabid + ' form');
                    meinek_old_sortby = form.one('.meinek_old_sortby').get('value');
                    meinek_old_numcourses = form.one('.meinek_old_numcourses').get('value');

                    needsresend = (this.lastsortby !== null && this.lastsortby !== meinek_old_sortby);
                    needsresend = needsresend || (this.lastnumcourses !== null && this.lastnumcourses !== meinek_old_numcourses);
                    if (needsresend) {
                        resultel = form.ancestor().one('.courseandpaging');
                        this.do_course_search(resultel, this.lastsortby, this.lastnumcourses, undefined, tabnum);

                        form.one('.meinek_old_sortby').set('value', this.lastsortby);
                        form.one('.meinek_old_numcourses').set('value', this.lastnumcourses);
                    }
                }

            }, this);
        },

        do_course_search: function(resultel, sortby, numcourses, page, schoolid, otherschoolid, sortdir) {
            var url, data, self;

            resultel.one('.coursecontainer').setContent(this.waitimg);

            data = {
                action: 'getcourses',
                pageurl: this.opts.pageurl
            };
            if (sortby !== undefined) {
                data.meinek_old_sortby = sortby;
            }
            if (numcourses !== undefined) {
                data.meinek_old_numcourses = numcourses;
            }
            if (page !== undefined) {
                data.meinek_old_page = page;
            }
            if (schoolid !== undefined) {
                data.meinek_old_schoolid = schoolid;
            }
            if (otherschoolid !== undefined) {
                data.meinek_old_otherschoolid = otherschoolid;
            }
            if (sortdir !== undefined) {
                data.meinek_old_sortdir = sortdir;
            }

            self = this;
            url = M.cfg.wwwroot + '/blocks/meinek_old/ajax.php';
            Y.io(url, {
                data: data,
                on: {
                    complete: function (id, resp) {
                        var details, updateel;
                        if (resp === null) {
                            return;
                        }
                        details = Y.JSON.parse(resp.responseText);
                        if (details && details.error == 0 && details.content) {
                            updateel = resultel;
                            if (schoolid !== undefined && details.schoolid != schoolid) {
                                // Result is for the wrong schoolid - update that tab, then send this query again.
                                updateel = Y.one('.block_meinek_old #' + details.schoolid + ' .courseandpaging');
                                self.do_course_search(resultel, sortby, numcourses, page, schoolid, otherschoolid);
                            } else if (otherschoolid !== undefined && details.otherschoolid != otherschoolid) {
                                // Result is for the wrong schoolid - update that tab, then send this query again.
                                updateel = Y.one('.block_meinek_old #' + details.schoolid + ' .courseandpaging');
                                self.do_course_search(resultel, sortby, numcourses, page, schoolid, otherschoolid);
                            }
                            if (updateel) {
                                updateel.setContent(details.content);
                                self.setup_hover(updateel);
                                self.setup_paging(updateel);
                            }
                            if (details.schoolid === -2) {
                                if (details.name) {
                                    Y.one('#school-2tablink').setContent(details.name);
                                }
                            }
                            self.set_sort_icon(details.sortdir);
                        }
                    }
                }
            });
        },

        set_sort_icon: function(dir) {
            var ascicons, descicons;

            ascicons = Y.all('.block_meinek_old .sortasc');
            descicons = Y.all('.block_meinek_old .sortdesc');
            if (dir === 'asc') {
                ascicons.removeClass('sorthidden');
                descicons.addClass('sorthidden');
            } else {
                ascicons.addClass('sorthidden');
                descicons.removeClass('sorthidden');
            }
        },

        setup_hover: function(parentel) {
            var rows, containers;
            //Mouseover event hook for table rows
            if (parentel === undefined) {
                rows = Y.all('.mycoursestabs table.meinek_oldtable tr');
            } else {
                rows = parentel.all('table.meinek_oldtable tr');
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
                if (content) {
                    content.setContent('');
                    content.setStyle('height', 'auto');
                }
                div.all('table.meinek_oldtable tr').removeClass('hover');
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
                this.do_course_search(resultel, undefined, undefined, linkparams.meinek_old_page, linkparams.meinek_old_school);

                return false;
            }, this);
        }
    };
}, '@VERSION@', {
    requires: ['node', 'event', 'io', 'json', 'querystring']
});