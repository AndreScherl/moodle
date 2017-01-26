require.config({
    // You have to change the following paths to meet your mebis application requirements
    paths: {
        'mebis': M.cfg.wwwroot+'/theme/mebis/mbsglobaldesign/javascripts/jquery.mebis'
    },
    shim: {
        'mebis': {
            deps: ['jquery']
        }
    }
});

define(['jquery', 'mebis'], function($, mebis) {
    //'use strict';

    var preventLinkDefault = function() {
        var $preventLinks = $('[data-prevent="default"]');
        $preventLinks.on('click', function() {
            return false;
        });
    };

    var hiddennav = function() {
        $('span.hiddennavbutton').click(function () {
            $(this).parent().children('ul.hiddennavleaf').toggle(200);
            $(this).parent().toggleClass('open');

            if ($('ul.me-subnav').children('li.hiddennavnode').hasClass('open')) {
                $('div.dropdown-inner').addClass('open');
            } else {
                $('div.dropdown-inner').removeClass('open');
            }
        });
    };

    // prevent dialog to be overlapped by sticky header
    var repositionMoodleDialog = function() {
        var offset = parseInt($('header.me-page-header').css("height")) + parseInt($('header.me-page-header').css("padding-top"));
        var target = document.querySelector('body');
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.className.indexOf("moodle-dialogue") >= 0) {
                        var dialog = document.querySelector(".moodle-dialogue-focused");
                        if (parseInt(dialog.style.top) < offset) {
                            dialog.style.top = offset + "px";
                        }
                    }
                });
            });
        });
        var config = {childList: true};
        observer.observe(target, config);
    };

    return {
        init: function() {
            preventLinkDefault();
            hiddennav();
            repositionMoodleDialog();
            mebis.init();
        }
    };
});