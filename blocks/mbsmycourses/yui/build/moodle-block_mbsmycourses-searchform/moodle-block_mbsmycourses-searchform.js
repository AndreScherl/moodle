YUI.add('moodle-block_mbsmycourses-searchform', function (Y, NAME) {

M.block_mbsmycourses = M.block_mbsmycourses || {};
M.block_mbsmycourses.searchform = function (data) {

    function initialize() {

        var form = Y.one('#filter_form');

        Y.one('#mbsmycourses_filterschool').on('change',

            function(e) {
                form.submit();
            }

            );

        Y.one('#mbsmycourses_sorttype').on('change',

            function(e) {
                form.submit();
            }

            );

        Y.all('#mbsmycourses_viewtype input').on('click',

            function(e) {
                form.submit();
            }

            );

    }

    initialize();
};

}, '@VERSION@', {"requires": ["base", "node", "io"]});
