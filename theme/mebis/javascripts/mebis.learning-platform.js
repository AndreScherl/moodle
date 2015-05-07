var MebisLearningPlatform = (function ($) {
    'use strict';

    function preventLinkDefault() {
        var $preventLinks = $('[data-prevent="default"]');
        $preventLinks.on('click', function (e) {
            return false;
        });
    }

    function hiddennav() {
        $('span.hiddennavbutton').click(function () {
            $(this).parent().children('ul.hiddennavleaf').toggle(200);
            $(this).parent().toggleClass('open');
        })
    }

    function categorytoggles() {
        $('span.category-toggle').click(function(evt){
            evt.stopPropagation();
            $(this).parent().parent().children('.category-body').slideToggle(200);
            $(this).toggleClass('open');
        })
        
         $('div.category-toggle').click(function(){
            $(this).parent().children('.category-body').slideToggle(200);
            $(this).toggleClass('open');
        })
        
        $('span.infoToggle').click(function(){
            $(this).parent().parent().children('.category-course-info').slideToggle(200);
        })
    }

    return {
        init: function () {
            preventLinkDefault();
            hiddennav();
            categorytoggles();
        }
    }

})(jQuery);

$(document).ready(function () {
    MebisLearningPlatform.init();
});