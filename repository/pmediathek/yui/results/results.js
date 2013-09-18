YUI.add('moodle-repository_pmediathek-results', function(Y) {
    M.repository_pmediathek = M.repository_pmediathek || {};

    M.repository_pmediathek.results = {
        init: function() {
            Y.one('#content').delegate('click', function(e) { this.toggle_result(e.currentTarget); }, '.resultheading', this);
            Y.one('#content').delegate('click', this.insert_result, '.insertlink', this);
        },

        toggle_result: function(result) {
            var details;

            details = result.ancestor('.searchresult').one('.details');
            if (details.hasClass('show')) {
                details.removeClass('show');
            } else {
                details.addClass('show');
            }
        },

        insert_result: function(e) {
            var resource, pos;

            e.preventDefault();

            resource = e.currentTarget.get('href');
            pos = resource.indexOf('?');
            if (pos !== -1) {
                resource = resource.substr(pos + 1);
                resource = resource.replace(/&amp;/g, '&');
                resource = Y.QueryString.parse(resource);
                parent.M.core_filepicker.select_file(resource);
            }

            return false;
        }
    };

}, '@VERSION@', {
    requires: ['base', 'querystring']
});