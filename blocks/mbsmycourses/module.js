M.block_mbsmycourses = M.block_mbsmycourses || {};

M.block_mbsmycourses.add_handles = function(Y) {
    M.block_mbsmycourses.Y = Y;
    var MOVEICON = {
        pix: "i/move_2d",
        component: 'moodle'
    };

    YUI().use('dd-constrain', 'dd-proxy', 'dd-drop', 'dd-plugin', function(Y) {
        //Static Vars
        var goingUp = false, lastY = 0;

        var list = Y.Node.all('.course_list .coursebox');
        list.each(function(v, k) {
            // Replace move link and image with move_2d image.
            var imagenode = v.one('.course_title .move a img');
            imagenode.setAttribute('src', M.util.image_url(MOVEICON.pix, MOVEICON.component));
            imagenode.addClass('cursor');
            v.one('.course_title .move a').replace(imagenode);

            var dd = new Y.DD.Drag({
                node: v,
                target: {
                    padding: '0 0 0 20'
                }
            }).plug(Y.Plugin.DDProxy, {
                moveOnEnd: false
            }).plug(Y.Plugin.DDConstrained, {
                constrain2node: '.course_list'
            });
            dd.addHandle('.course_title .move');
        });

        Y.DD.DDM.on('drag:start', function(e) {
            //Get our drag object
            var drag = e.target;
            //Set some styles here
            drag.get('node').setStyle('opacity', '.25');
            drag.get('dragNode').addClass('block_mbsmycourses');
            drag.get('dragNode').set('innerHTML', drag.get('node').get('innerHTML'));
            drag.get('dragNode').setStyles({
                opacity: '.5',
                borderColor: drag.get('node').getStyle('borderColor'),
                backgroundColor: drag.get('node').getStyle('backgroundColor')
            });
        });

        Y.DD.DDM.on('drag:end', function(e) {
            var drag = e.target;
            //Put our styles back
            drag.get('node').setStyles({
                visibility: '',
                opacity: '1'
            });
            M.block_mbsmycourses.save(Y);
        });

        Y.DD.DDM.on('drag:drag', function(e) {
            //Get the last y point
            var y = e.target.lastXY[1];
            //is it greater than the lastY var?
            if (y < lastY) {
                //We are going up
                goingUp = true;
            } else {
                //We are going down.
                goingUp = false;
            }
            //Cache for next check
            lastY = y;
        });

        Y.DD.DDM.on('drop:over', function(e) {
            //Get a reference to our drag and drop nodes
            var drag = e.drag.get('node'),
            drop = e.drop.get('node');

            //Are we dropping on a li node?
            if (drop.hasClass('coursebox')) {
                //Are we not going up?
                if (!goingUp) {
                    drop = drop.get('nextSibling');
                }
                //Add the node to this list
                e.drop.get('node').get('parentNode').insertBefore(drag, drop);
                //Resize this nodes shim, so we can drop on it later.
                e.drop.sizeShim();
            }
        });

        Y.DD.DDM.on('drag:drophit', function(e) {
            var drop = e.drop.get('node'),
            drag = e.drag.get('node');

            //if we are not on an li, we must have been dropped on a ul
            if (!drop.hasClass('coursebox')) {
                if (!drop.contains(drag)) {
                    drop.appendChild(drag);
                }
            }
        });
    });
}

M.block_mbsmycourses.save = function() {
    var Y = M.block_mbsmycourses.Y;
    var sortorder = Y.one('.course_list').get('children').getAttribute('id');
    for (var i = 0; i < sortorder.length; i++) {
        sortorder[i] = sortorder[i].substring(7);
    }
    var params = {
        sesskey : M.cfg.sesskey,
        sortorder : sortorder
    };
    Y.io(M.cfg.wwwroot + '/blocks/mbsmycourses/save.php', {
        method: 'POST',
        data: build_querystring(params),
        context: this
    });
}

/**
 * Init a collapsible region, see print_collapsible_region in weblib.php
 * @param {YUI} Y YUI3 instance with all libraries loaded
 * @param {String} id the HTML id for the div.
 * @param {String} userpref the user preference that records the state of this box. false if none.
 * @param {String} strtooltip
 */
M.block_mbsmycourses.collapsible = function(Y, id, userpref, strtooltip) {
    if (userpref) {
        M.block_mbsmycourses.userpref = true;
    }
    Y.use('anim', function(Y) {
        new M.block_mbsmycourses.CollapsibleRegion(Y, id, userpref, strtooltip);
    });
};

/**
 * Object to handle a collapsible region : instantiate and forget styled object
 *
 * @class
 * @constructor
 * @param {YUI} Y YUI3 instance with all libraries loaded
 * @param {String} id The HTML id for the div.
 * @param {String} userpref The user preference that records the state of this box. false if none.
 * @param {String} strtooltip
 */
M.block_mbsmycourses.CollapsibleRegion = function(Y, id, userpref, strtooltip) {
    // Record the pref name
    this.userpref = userpref;

    // Find the divs in the document.
    this.div = Y.one('#' + id);

    // Get the caption for the collapsible region
    var caption = this.div.one('.category-title');
    
    caption.on('click', function(e) {
        
        e.preventDefault();
        this.div.toggleClass('collapsed');
        
        if (this.userpref) {
            M.util.set_user_preference(this.userpref, this.div.hasClass('collapsed'));
        }
    }, this);
};

M.block_mbsmycourses.userpref = false;

/**
 * The user preference that stores the state of this box.
 * @property userpref
 * @type String
 */
M.block_mbsmycourses.CollapsibleRegion.prototype.userpref = null;

/**
 * The key divs that make up this
 * @property div
 * @type Y.Node
 */
M.block_mbsmycourses.CollapsibleRegion.prototype.div = null;

/**
 * The key divs that make up this
 * @property icon
 * @type Y.Node
 */
M.block_mbsmycourses.CollapsibleRegion.prototype.icon = null;

M.block_mbsmycourses.add_overlay = function(Y, id) {

    YUI().use('overlay', 'node','dd-constrain', function(Y) {

        // detect the click anywhere other than overlay element to close it.
	Y.one(document).on('click', function(e) {
		// Below code is causing the Overlay to close as soon as it is open. Need to detect the state of overlay.
		// When it is already open then the below code should fire.
		if(e.target.ancestor("#mbsmycourses-overlay-" + id) === null && (e.target.get('id') != 'mbsmycourses-new-' + id) && overlay.get('visible') == true)  {
			overlay.hide();
		}
	});

        // Create an overlay from markup, using an existing contentBox.
        var xy = Y.one("#mbsmycourses-new-" + id).getXY();
        var overlay = new Y.Overlay({
            srcNode: "#mbsmycourses-overlay-" + id,
            width: "auto",
            height:"auto",
            visible:false,
            xy:[xy[0] + 10, xy[1] + 35]
        });

        overlay.render();
        overlay.move(xy[0], xy[1]);

        Y.one("#mbsmycourses-new-" + id).on('click', function(e) {
            e.preventDefault();
            overlay.show();
        });

        Y.one("#mbsmycourses-overlay-position-" + id + " .mbscourses-hide-overlay").on('click', function (e) {
            e.preventDefault();
        });

        Y.on("click", Y.bind(overlay.hide, overlay), ".mbscourses-hide-overlay");
        

        // Make overlay draggable.
        new Y.DD.Drag({
            node : overlay.get('boundingBox'),
            handles : ['.yui3-widget-hd']

        }).plug(Y.Plugin.DDConstrained, {
            constrain2view : true
        });
    });
}