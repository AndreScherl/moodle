/*global M*/
M.local_profilepicture = {

    dialog: null,
    Y: null,
    outel: null,
    imageel: null,

    init: function(Y, opts) {
        this.Y = Y;
        this.outel = Y.one('#'+opts.elname);
        this.outel.set('name', opts.elname); // So that it does not conflict with the noscript 'select' element.
        this.imageel = Y.one('#'+opts.imageel);
        this.dialog = new Y.Panel({
            width: 400,
            zIndex: 200,
            srcNode: '#local_profilepicture',
            centered: true,
            render: false,
            modal: true
        });

        Y.one('#'+opts.buttonname).on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.dialog.render();
            this.dialog.show();
            return false;
        }, this);

        Y.all('#local_profilepicture .image').on('click', this.imageselected, this);
    },

    imageselected: function(e) {
        var filename, src;

        e.preventDefault();
        e.stopPropagation();

        this.dialog.hide();
        filename = e.currentTarget.one('input').get('value');
        src = e.currentTarget.one('img').get('src');
        this.outel.set('value', filename);
        this.imageel.setContent('<img src="'+src+'" class="userpicture" width="64" height="64" />');

        return false;
    }
};