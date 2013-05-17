$(document).ready(function() {
    $("td[id$=moddesc]").css('display', 'none');

    $('.mycoursestabs').tabs({
        selected: starttab,
        select: function(event, ui) {
            var tabnum = $(ui.tab).attr('href');
            tabnum = tabnum.match(/#school(.*)tab/);
            if (tabnum.length >= 2) {
                tabnum = parseInt(tabnum[1], 10);
                var params = {
                    'action': 'setschool',
                    'schoolid': tabnum,
                    'sesskey': M.cfg.sesskey
                };
                $.ajax({
                    url: M.cfg.wwwroot+'/blocks/meinekurse/ajax.php',
                    data: params
                });
            }
        }
    });
});