YUI.add('moodle-repository_pmediathek-searchform', function(Y) {
    M.repository_pmediathek = M.repository_pmediathek || {};

    M.repository_pmediathek.searchform = {
        subjects: false,
        resourcemap: false,

        init: function(opts) {
            this.subjects = opts.subjects;
            this.resourcemap = opts.resourcemap;

            // Unhide the main drop-down list.
            var mainselect, subjselect;
            mainselect = Y.one('#fitem_id_examtype') || Y.one('#fitem_id_school');
            mainselect.addClass('show');

            // Set up the handler for changes and call immediately (in case it already has a value).
            mainselect = mainselect.one('select');
            mainselect.after('change', function(e) { this.update_selection(e.currentTarget); }, this);
            this.update_selection(mainselect);

            subjselect = Y.one('#fitem_id_subject').one('select');
            subjselect.after('change', function(e) { this.update_resources(); }, this);
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

                this.update_resources();

                this.show_elements();
            } else {
                this.hide_elements();
            }
        },

        update_resources: function() {
            var mainselect, mainval, subjselect, subjval, resselect, resval, list, opt, stillexists, key;

            mainselect = Y.one('#fitem_id_examtype') || Y.one('#fitem_id_school');
            mainselect = mainselect.one('select');
            subjselect = Y.one('#fitem_id_subject').one('select');
            resselect = Y.one('#fitem_id_type').one('select');

            mainval = mainselect.get('value');
            subjval = subjselect.get('value');
            resval = resselect.get('value');

            stillexists = false;
            resselect.all('option').remove(true);
            list = this.resourcemap[mainval][subjval];
            if (list !== undefined) {
                if (list[''] !== undefined) {
                    opt = Y.Node.create('<option></option>');
                    opt.set('value', '');
                    opt.setHTML(list['']);
                    resselect.appendChild(opt);
                    if (key === resval) {
                        stillexists = true;
                    }
                }
                for (key in list) {
                    if (key !== '') {
                        if (list.hasOwnProperty(key)) {
                            opt = Y.Node.create('<option></option>');
                            opt.set('value', key);
                            opt.setHTML(list[key]);
                            resselect.appendChild(opt);
                            if (key === resval) {
                                stillexists = true;
                            }
                        }
                    }
                }
            }
            if (stillexists) {
                resselect.set('value', resval); // Keep the 'resource' selection if it is still available.
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