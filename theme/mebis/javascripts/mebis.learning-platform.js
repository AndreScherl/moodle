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
        $('div#me-help-box a#me-help-box-closeforever').click(function (e) {
            if(hasLocalStorage()) {
                localStorage['helpnote-hidden'] = '1';
            }

            $('#me-help-box').remove();
            return false;
        });

        $('div#me-help-box a#me-help-box-close').click(function (e) {
            $('#me-help-box').remove();
            return false;
        });

        if(hasLocalStorage()) {
            var hidden = localStorage['helpnote-hidden'];
            if('1' === hidden) {
                $('#me-help-box').remove();
            }
        }
    }

    return {
        init: function () {
            preventLinkDefault();
            initHelpNote();
        }
    }

})(jQuery);

$(document).ready(function () {
    MebisLearningPlatform.init();
});