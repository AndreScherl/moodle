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
            
            if ($('ul.me-subnav').children('li.hiddennavnode').hasClass('open')) {
                $('div.dropdown-inner').addClass('open');
            } 
            else {
                $('div.dropdown-inner').removeClass('open');
            }
        });
    }

    return {
        init: function () {
            preventLinkDefault();
            hiddennav();
        }
    }

})(jQuery);

$(document).ready(function () {
    MebisLearningPlatform.init();
});