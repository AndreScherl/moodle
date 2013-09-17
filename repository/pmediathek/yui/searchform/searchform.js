YUI.add('moodle-repository_pmediathek-searchform', function(Y) {
    M.repository_pmediathek = M.repository_pmediathek || {};

    M.repository_pmediathek.searchform = {
        subjects: false,

        init: function(opts) {
            this.subjects = opts.subjects;

            // Unhide the main drop-down list.
            var mainselect;
            mainselect = Y.one('#fitem_id_examtype') || Y.one('#fitem_id_schooltype');
            mainselect.addClass('show');
            mainselect.one('select').after('change', this.update_selection, this);
        },

        update_selection: function(e) {
            var val, select, key, subject, opt;

            val = e.currentTarget.get('value');
            if (val) {
                select = Y.one('#id_subject');
                select.all('option').remove(true);
                if (this.subjects[val] !== undefined) {
                    for (key in this.subjects[val]) {
                        if (this.subjects[val].hasOwnProperty(key)) {
                            subject = this.subjects[val][key];
                            opt = Y.Node.create('<option></option>');
                            opt.set('value', key);
                            opt.setHTML(subject);
                            select.appendChild(opt);
                        }
                    }
                }

                this.show_elements();
            } else {
                this.hide_elements();
            }
        },

        show_elements: function() {
            Y.all('.fitem').addClass('show');
        },

        hide_elements: function() {
            Y.all('.fitem').each(function(el) {
                var id = el.get('id');
                if (id !== 'fitem_id_examtype' && id !== 'fitem_id_schooltype') {
                    el.removeClass('show');
                }
            });
        }
    };

}, '@VERSION@', {
    requires: ['base']
});