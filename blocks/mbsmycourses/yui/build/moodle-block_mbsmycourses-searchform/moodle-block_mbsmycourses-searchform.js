YUI.add('moodle-block_mbsmycourses-searchform', function (Y, NAME) {

M.block_mbsmycourses = M.block_mbsmycourses || {};
M.block_mbsmycourses.searchform = function (data) {

    function initialize() {

        var form = Y.one('#filter_form');

        Y.one('#mbsmycourses_filterschool').on('change',
                function (e) {
                    form.submit();
                }

        );

        Y.one('#mbsmycourses_sorttype').on('change',
                function (e) {
                    form.submit();
                }

        );

        Y.all('#mbsmycourses_viewtype input').on('click',
                function (e) {
                    form.submit();
                }

        );

        var shownewsform = Y.one('#mbsmycourses_shownewsform');
        var shownews = Y.one('#mbsmycourses_shownews');

        if (shownews) {
            shownews.on('click',
                    function () {
                        var show = shownews.get('checked');
                        if (show) {
                            Y.one('#mbsmycourses_shownewshidden').disabled = true;
                        } else {
                            Y.one('#mbsmycourses_shownewshidden').disabled = false;
                        }
                        shownewsform.submit();
                    }
            );
        }

    }

    initialize();
};

}, '@VERSION@', {"requires": ["base", "node", "io"]});
