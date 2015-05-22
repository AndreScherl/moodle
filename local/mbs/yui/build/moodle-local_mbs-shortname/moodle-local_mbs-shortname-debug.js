YUI.add('moodle-local_mbs-shortname', function (Y, NAME) {

/*global M*/
M.local_mbs = M.local_mbs || {};
M.local_mbs.shortname = {

    parent: null,
    shortnamefield: null,
    id: null,
    timeoutid: null,

    init: function() {
        "use strict";

        this.id = Y.one('input[name=id]').get('value');
        this.shortnamefield = Y.one('input#id_shortname');
        this.parent = this.shortnamefield.ancestor();

        this.shortnamefield.on('valuechange', this.queueShortnameCheck, this);
    },

    queueShortnameCheck: function() {
        "use strict";
        var self;
        if (this.timeoutid) {
            window.clearTimeout(this.timeoutid);
            this.timeoutid = null;
        }
        self = this;
        this.timeoutid = window.setTimeout(function() {
            self.checkShortname();
        }, 800); // Wait for a gap in the typing, before sending request.
    },

    checkShortname: function() {
        "use strict";
        var shortname;
        this.parent.all('#shortnameerror').remove(); // Remove any existing error messages.
        shortname = this.shortnamefield.get('value');
        if (!shortname) {
            return;
        }
        Y.io(M.cfg.wwwroot+'/local/mbs/ajax.php', {
            data: {
                'id': this.id,
                'shortname': shortname,
                'action': 'checkshortname'
            },
            context: this,
            on: {
                success: function(e, o) {
                    var response;
                    response = Y.JSON.parse(o.responseText);
                    if (response.response === 'Exists') {
                        this.parent.prepend('<span id="shortnameerror"><span class="error">'+response.error+'</span><br /></span>');
                    }
                }
            }
        });
    }
};


}, '@VERSION@', {"requires": ["base", "json", "event-valuechange"]});
