M.theme_dlb = {} || M.theme_dlb;
M.theme_dlb.init = function (Y, args) {
    
    var toolbarsettings;
    var toolbarsubmenu;
    
    function onSettingsEnter(e) {
        toolbarsubmenu.show();
    }
    
    function onMenuLeave(e) {
        toolbarsubmenu.hide();
    }
    
    function initialize() {
        
        toolbarsettings = Y.one('#toolbar-settings');
        
        toolbarsettings.on('mouseenter', function(e) {
           onSettingsEnter(e);
        });
        
        Y.one('#custommenu').on('mouseleave', function(e) {
           onMenuLeave(e);
        });
        
        toolbarsubmenu = Y.one('#toolbar-submenu');

        toolbarsubmenu.on('mouseleave', function(e) {
           onMenuLeave(e);
        });
    }
    initialize();
}