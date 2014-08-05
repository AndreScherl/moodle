M.block_quickcourselist = {

    sesskey: null,

    init: function(Y, instanceid, sesskey) {
        this.Y = Y;
        this.sesskey = sesskey;
        this.instanceid = instanceid;

        this.progress = Y.one('#quickcourseprogress');
        this.xhr = null;

        Y.one('#quickcourselistsearch').on('keyup', function(e) {
            var searchstring = e.target.get('value');
            this.search(searchstring);
        }, this);
        Y.one('#quickcourseform').on('submit', function(e) {
            e.preventDefault();
            var searchstring = e.target.getById('quickcourselistsearch').get('value');
            this.search(searchstring);
        }, this);
    },

    search: function(string) {

        //awag erst ab 3 Buchstaben suchen
        if ((string.length < 3) && (string.length > 0)) return;

        var Y = this.Y;
        uri = M.cfg.wwwroot+'/blocks/quickcourselist/quickcourse.php';
        if (this.xhr != null) {
            this.xhr.abort();
        }
        this.progress.setStyle('visibility', 'visible');
        this.xhr = Y.io(uri, {
            data: 'course='+string+'&instanceid='+this.instanceid+'&sesskey='+this.sesskey,
            context: this,
            on: {
                success: function(id, o) {
                    var courses = Y.JSON.parse(o.responseText);
                    list = Y.Node.create('<ul />');
                    if (courses.length > 0) {
                        Y.Array.each(courses, function(course) {
                            Y.Node.create('<li><a href="'+M.cfg.wwwroot+'/course/view.php?id='+course.id+'">'+course.shortname+' '+course.fullname+'</a></li>').appendTo(list);
                        });
                    }
                    Y.one('#quickcourselist').replace(list);
                    list.setAttribute('id', 'quickcourselist');
                    this.progress.setStyle('visibility', 'hidden');
                },
                failure: function(id, o) {
                    if (o.statusText != 'abort') {
                        this.progress.setStyle('visibility', 'hidden');
                        if (o.statusText !== undefined) {
                            var list = Y.Node.create('<p>'+o.statusText+'</p>');
                            Y.one('#quickcourselist').replace(list);
                            list.set('id', 'quickcourselist');
                        }
                    }
                }
            }
        });
    }
}

M.block_quickcategorylist  = {

    sesskey: null,

    init: function(Y, instanceid, sesskey) {
        this.Y = Y;
        this.sesskey = sesskey;
        this.instanceid = instanceid;

        this.progress = Y.one('#quickcategoryprogress');
        this.xhr = null;

        Y.one('#quickcategorylistsearch').on('keyup', function(e) {
            var searchstring = e.target.get('value');
            this.search(searchstring);
        }, this);
        Y.one('#quickcategoryform').on('submit', function(e) {
            e.preventDefault();
            var searchstring = e.target.getById('quickcategorylistsearch').get('value');
            this.search(searchstring);
        }, this);
    },

    search: function(string) {

        //awag erst ab 3 Buchstaben suchen
        if ((string.length < 3) && (string.length > 0)) return;

        var Y = this.Y;
        uri = M.cfg.wwwroot+'/blocks/quickcourselist/quickcategory.php';
        if (this.xhr != null) {
            this.xhr.abort();
        }
        this.progress.setStyle('visibility', 'visible');
        this.xhr = Y.io(uri, {
            data: 'searchterm='+string+'&instanceid='+this.instanceid+'&sesskey='+this.sesskey,
            context: this,
            on: {
                success: function(id, o) {
                    var categorys = Y.JSON.parse(o.responseText);
                    list = Y.Node.create('<ul />');
                    if (categorys.length > 0) {
                        Y.Array.each(categorys, function(category) {
                            Y.Node.create('<li><a href="'+M.cfg.wwwroot+'/course/category.php?id='+category.id+'">'+category.name+'</a></li>').appendTo(list);
                        });
                    }
                    Y.one('#quickcategorylist').replace(list);
                    list.setAttribute('id', 'quickcategorylist');
                    this.progress.setStyle('visibility', 'hidden');
                },
                failure: function(id, o) {
                    if (o.statusText != 'abort') {
                        this.progress.setStyle('visibility', 'hidden');
                        if (o.statusText !== undefined) {
                            var list = Y.Node.create('<p>'+o.statusText+'</p>');
                            Y.one('#quickcategorylist').replace(list);
                            list.set('id', 'quickcategorylist');
                        }
                    }
                }
            }
        });
    }
}
