YUI.add('moodle-block_mbschangetheme-newalert', function (Y, NAME) {

M.block_mbschangetheme = M.block_mbschangetheme || {};
M.block_mbschangetheme.newalert = function (data) {
    
    var node = Y.one('#newalertoverlay');

    var dialog = new M.core.dialogue({
        draggable    : true,
        bodyContent  : node,
        centered     : true,
        modal        : true,
        visible      : true,
        closeButton  : false,
        zIndex       : 100
    });
    
    node.one('#newalertclose').on('click', function (e) {
        
        var hideme = node.one('#newalerthideme').get('checked');
        M.util.set_user_preference(data.userpreference, hideme);
       
        dialog.hide();
    });
    
    dialog.render();
    dialog.show();
};

}, '@VERSION@', {"requires": ["moodle-core-notification"]});
