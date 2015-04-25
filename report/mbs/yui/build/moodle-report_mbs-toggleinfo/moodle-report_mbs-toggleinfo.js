YUI.add('moodle-report_mbs-toggleinfo', function (Y, NAME) {

M.report_mbs = M.report_mbs || {};
M.report_mbs.toggleinit = function (data) {

    function initialize() {

        var infobuttons = Y.all('img[id^="info"]');

        if (infobuttons) {

            infobuttons.each(

                function(node, index) {

                    var courseid = Number(node.get('id').split('_')[1]);

                    node.on('click',
                        function (e) {

                            e.preventDefault();
                            Y.one('#content_' + courseid).toggleView();
                        });
                });
        }
    }

    initialize();
};

}, '@VERSION@', {"requires": ["base", "node"]});
