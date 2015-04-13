YUI.add('moodle-block_meinesuche-courseoverview', function (Y, NAME) {

/*global M*/
M.block_meinesuche = M.block_meinesuche || {};
M.block_meinesuche.courseoverview = {
    init: function(opts) {
        var base, sel;

        base = Y.one('#course_overview_id-'+opts.id);
        if (!base) {
            return;
        }
        sel = base.one('.course_overview-selector select');
        if (!sel) {
            return;
        }

        sel.on('change', function(e) {
            var selected;

            e.preventDefault();

            selected = sel.get('value');
            if (selected.match(/courseoverview=standard/)) { // Standard selected.
                M.util.set_user_preference('meinesuche_courseoverview', 'standard');
                base.one('.course_overview-standard').addClass('selected');
                base.one('.course_overview-treeview').removeClass('selected');
            } else { // Treeview selected.
                M.util.set_user_preference('meinesuche_courseoverview', 'treeview');
                base.one('.course_overview-treeview').addClass('selected');
                base.one('.course_overview-standard').removeClass('selected');
            }
        });

        base.all('.info').each(function(node) {
            node.on('click', function() {
                var params = {
                    sesskey : M.cfg.sesskey,
                    catid: node.one('h3').getAttribute('data-catid'),
                    open: node.get('parentNode').hasClass('collapsed')
                };
                Y.io(M.cfg.wwwroot+'/blocks/course_overview/brain.php', {
                    data: build_querystring(params),
                    on:   {
                        success: function() {
                            return true;
                        }
                    }
                });
            });
        });
    }
};


}, '@VERSION@', {"requires": ["base", "node", "yui2-treeview"]});
