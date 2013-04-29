$(document).ready(function() {
    $("td[id$=moddesc]").css('display', 'none');

    $('.mycoursestabs').tabs({
        selected: starttab,
        select: function(event, ui) {
            var tabnum = $(ui.tab).attr('href');
            tabnum = tabnum.match(/#school(.*)tab/);
            if (tabnum.length >= 2) {
                tabnum = parseInt(tabnum[1], 10);
                $('.meinekurse_sorticons a').each(function (idx, el) {
                    var url = $(el).attr('href');
                    url = url.replace(/meinekurse_school=[-\d]+/, 'meinekurse_school=' + tabnum);
                    $(el).attr('href', url);
                });
            }
        }
    });

    //Mouseover event hook for table rows
    $('.mycoursestabs table.mycourses tr').bind('mouseenter', function() {
        $(this).siblings().removeClass('hover');
        var row = this;
        var rowcontent = $(this).find('td[id$=moddesc]').html();
        var kurseid = $(this).closest('div.meinekurse_course').attr('id');
        $(row).addClass('hover');
            var newdiv = $('.mycoursestabs').find('div[class]');
            newdiv.each(function(){
                if ($(this).hasClass(kurseid)) {
                    $(this).html(rowcontent);
                    $(this).css('height', 'auto');
                    if ($(this).height() < $(this).closest('div.coursecontainer').height()) {
                        $(this).height($(this).closest('div.coursecontainer').height());
                    }
                }
            })
    });

    //Mouseout event hook for table rows
    $('.mycoursestabs .coursecontainer').bind('mouseleave', function() {
        var div = this;
        var content = $(this).find('div.coursecontent');
        var kurseid = $(this).closest('div.meinekurse_course').attr('id');
            $(content).html('');
            $(content).css('height', 'auto');
            $('.mycoursestabs table.mycourses tr').removeClass('hover');
    });

});