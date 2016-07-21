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
    'use strict';
    
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
            } 
            else {
                $('div.dropdown-inner').removeClass('open');
            }
        });
    };

    return {
        init: function() {
            preventLinkDefault();
            hiddennav();
            mebis.init();
        }
    };
});