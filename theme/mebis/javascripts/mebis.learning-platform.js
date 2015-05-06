var MebisLearningPlatform = (function ($) {
    'use strict';

    function preventLinkDefault() {
        var $preventLinks = $('[data-prevent="default"]');
        $preventLinks.on('click', function (e) {
            return false;
        });
    }

    function hasLocalStorage() {
        try {
            return 'localStorage' in window && window['localStorage'] !== null;
        } catch (e) {
            return false;
        }
    }

    function initHelpNote() {
        $('div#me-help-box a#me-help-box-close').click(function (e) {
            $('#me-help-box').remove();
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
            initHelpNote();
            hiddennav();
            categorytoggles();
        }
    }

})(jQuery);

$(document).ready(function () {
    MebisLearningPlatform.init();
});