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

    /**
     * Sets onclick eventhandler for the jumpnavigation
     */
    function scrollToTopic () {
        //check if jumpnavigation exists
        if ($('ul.jumpnavigation').length) {
            //set click on each node
            $('ul.jumpnavigation').on('click', 'li.jumpnavigation-point', function () {
                //if data-scroll attribute is set
                if ($(this).is('[data-scroll]')) {
                    //if data-scroll is top, scroll to top of the page
                    if ($(this).attr('data-scroll') === 'top') {
                        $("html, body").animate({scrollTop: 0}, 1000);
                    } else {
                        //else scroll to selected resource, provided it exists
                        if ($($(this).attr('data-scroll')).length) {
                            $("html, body").animate({scrollTop: $($(this).attr('data-scroll')).offset().top - 50}, 1000);
                            //if topic is closed, open it
                            if(!($($(this).attr('data-scroll')).find('.the_toggle').is('.toggle_open'))){
                                $($(this).attr('data-scroll')).find('.sectionhead.toggle').trigger("click");
                            }
                        }
                    }
                }
            });
        }
    }

    return {
        init: function () {
            preventLinkDefault();
            initHelpNote();
            scrollToTopic();
        }
    }

})(jQuery);

$(document).ready(function () {
    MebisLearningPlatform.init();
});