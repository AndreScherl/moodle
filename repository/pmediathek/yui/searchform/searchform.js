YUI.add('moodle-repository_pmediathek-searchform', function(Y) {
    M.repository_pmediathek = M.repository_pmediathek || {};

    M.repository_pmediathek.searchform = {
        subjects: false,

        init: function(opts) {
            this.subjects = opts.subjects;

            // Unhide the main drop-down list.
            var mainselect;
            mainselect = Y.one('#fitem_id_examtype') || Y.one('#fitem_id_school');
            mainselect.addClass('show');

            // Set up the handler for changes and call immediately (in case it already has a value).
            mainselect = mainselect.one('select');
            mainselect.after('change', function(e) { this.update_selection(e.currentTarget); }, this);
            this.update_selection(mainselect);
        },

        update_selection: function(target) {
            var val, select, key, subject, opt, oldsubject, stillexists;

            val = target.get('value');
            if (val) {
                select = Y.one('#id_subject');
                oldsubject = select.get('value');
                stillexists = false;
                select.all('option').remove(true);
                if (this.subjects[val] !== undefined) {
                    for (key in this.subjects[val]) {
                        if (this.subjects[val].hasOwnProperty(key)) {
                            subject = this.subjects[val][key];
                            opt = Y.Node.create('<option></option>');
                            opt.set('value', key);
                            opt.setHTML(subject);
                            select.appendChild(opt);
                            if (key === oldsubject) {
                                stillexists = true;
                            }
                        }
                    }
                }
                if (stillexists) {
                    select.set('value', oldsubject); // Keep the 'subject' selection if it is still available.
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
                if (id !== 'fitem_id_examtype' && id !== 'fitem_id_school') {
                    el.removeClass('show');
                }
            });
        }
    };

}, '@VERSION@', {
    requires: ['base']
});