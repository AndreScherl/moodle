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

    //Mouseover event hook for table rows
    $('.mycoursestabs table.meinekursetable tr').bind('mouseenter', function() {
        $(this).siblings().removeClass('hover');
        var row = this;
        var rowcontent = $(this).find('td.moddesc-hidden').html();
        $(row).addClass('hover');
        var newdiv = $(row).closest('div.coursecontainer');
        newdiv = $(newdiv).find('.coursecontent');
        $(newdiv).html(rowcontent);
        $(newdiv).css('height', 'auto');
        if ($(newdiv).height() < $(newdiv).closest('div.coursecontainer').height()) {
            $(newdiv).height($(newdiv).closest('div.coursecontainer').height());
        }
    });

    //Mouseout event hook for table rows
    $('.mycoursestabs .coursecontainer').bind('mouseleave', function() {
        var div = this;
        var content = $(this).find('div.coursecontent');
        $(content).html('');
        $(content).css('height', 'auto');
        $('.mycoursestabs table.meinekursetable tr').removeClass('hover');
    });

});