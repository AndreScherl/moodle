var Mebis = (function ($) {
    'use strict';

    var $win; // to be initialized after DOM ready.
    var $body;
    var didScroll = false;
    var backTopAppearOn = 300;
//    var lastY = 0;
//    var anchorHeadlinePositions = [];

    function isMobile() {
        return ($win.width()) <= 768 ? true : false;
    }

    /**
     * To-Top-Button
     */
    function scrollToTop() {
        // scroll body to 0px on click
        $('.me-back-top').on('click', function (e) {
            e.preventDefault();

            $('html')
                    .velocity('stop')
                    .velocity('scroll', {duration: 800, offset: 0});

            //setAnchorClass();

            window.location.hash = ''; // for older browsers, leaves a # behind

            // IE9 Fix
            if (history.pushState != undefined) {
                history.pushState('', document.title, window.location.pathname + window.location.search);
            }

            return false;
        });
    }

// method in use? 
    /**
     * Init smooth scrolling-effect to hash
     */
    /*    function initSmoothscrolling() {
     
     // init smooth scrolling to window.hash
     var $jumpmark = $(window.location.hash);
     if ($jumpmark.length) {
     setTimeout(function () {
     if (window.location.hash) {
     var offset = $jumpmark.data('offset') - $('#topbar').height() - $('header.me-page-header.full').height() - 150;
     window.scrollTo(0, 0);
     $('html, body').animate({
     scrollTop: $(window.location.hash).offset().top - 85 - (offset || 0)
     }, 800);
     }
     }, 1);
     }
     }
     */
    function initCarousel() {
        var $carousel = $('[data-me-carousel]');
        var $controls = $('.carousel-control');

        if ($carousel.length == 0) {
            return;
        }

        var offset = (isMobile()) ? 1 : 3;

        $carousel.on('jcarousel:reload jcarousel:create', function () {
            var width = $carousel.innerWidth();

            if (width >= 768) {
                width = width / 3;
                offset = 3;
            } else if (width >= 350) {
                width = width;
                offset = 1;
            }

            $carousel.jcarousel('items').css('width', width + 'px');

            setTimeout(function () {
                var maxHeight = 0;

                $carousel.jcarousel('items').each(function () {
                    var currentHeight = $(this).outerHeight();

                    if (currentHeight > maxHeight) {
                        maxHeight = currentHeight;
                    }
                });

                var $links = $carousel.jcarousel('items').find("> a");

                $links.each(function () {
                    $(this).css("min-height", maxHeight);
                });

            }, 50);

            if (!isMobile()) {
                $('.me-news-item:nth-child(3n)').css('width', width + 1 + 'px');
            }

        })
                .jcarousel({
                    wrap: 'circular',
                    transitions: true
                });

        $carousel.jcarouselAutoscroll({
            autostart: true,
            target: '+=' + offset
        });

        $controls.filter('.left').jcarouselControl({target: '-=' + offset});
        $controls.filter('.right').jcarouselControl({target: '+=' + offset});

    }

    /**
     * Switch html-class 'me-inverted'
     * For Contrast-Mode
     */
    function initInvertContrastSwitch() {
        var $invert = $('#me-invert');
        var $styles = $('[data-mode]');
        //var style = 'mebis.css';
        //var assets = '/theme/mebis/style/';
        //var images = '/theme/mebis/pix/';

        $invert.on('click', function (e) {
            e.preventDefault();
            var mode = $styles.attr('data-mode');

            // replace logos
            $("[data-src-contrast]").each(function () {
                var currentPath = $(this).attr("src");
                var contrastPath = $(this).attr("data-src-contrast");

                if (mode == 'default') {
                    $(this).attr("src", contrastPath);
                    $(this).attr("data-src-contrast", currentPath);
                } else {
                    $(this).attr("src", contrastPath);
                    $(this).attr("data-src-contrast", currentPath);
                }
            });

            if (mode == 'default') {
                $styles.attr('data-mode', 'contrast');
                //style = 'mebis-contrast.css';
                $.post(
                        M.cfg['wwwroot'] + '/theme/mebis/changemode.php',
                        {sesskey: M.cfg.sesskey, mode: true}
                );
            } else {
                $styles.attr('data-mode', 'default');
                // style = 'mebis.css';
                $.post(
                        M.cfg['wwwroot'] + '/theme/mebis/changemode.php',
                        {sesskey: M.cfg.sesskey, mode: false}
                );
            }

            if ($('.js-navbar-collapse').hasClass('.in')) {
                $('[data-target=".js-navbar-collapse"]').trigger('click');
            }

            // var path = M.cfg['wwwroot'] + assets + style;
            // $styles.attr('href', path);

            $('html').toggleClass('me-contrast-mode');

            $(this).toggleClass('active');
        });
    }

    function handleFontSizeSwitch() {
        var $changeFontSize = $('.change-fontsize');
        var baseFontSize = $body.css('font-size');
        var range = [14, 22];

        $changeFontSize.on('click', function (e) {
            e.preventDefault();
            var curFontSize = parseInt($('body').css('font-size'));
            var change = 1;
            var dir = $(this).data('change');
            var newFontSize = (dir == 'inc') ? (curFontSize += change) : (curFontSize -= change);

            if (newFontSize > range[0] && newFontSize < range[1]) {

                $body.css({
                    'font-size': newFontSize + 'px'
                });
            }

        });

        $(window).on('keyup', function (e) {
            var key = e.which;

            var curFontSize = parseInt($body.css('font-size'));
            var change = 1;
            var dir = $(this).data('change');
            var newFontSize = 0;

            if (e.ctrlKey && e.altKey && key == 187) {
                newFontSize = curFontSize += change;
            }

            if (e.ctrlKey && e.altKey && key == 189) {
                newFontSize = curFontSize -= change;
            }

            if (newFontSize > range[0] && newFontSize < range[1]) {
                $body.css({
                    'font-size': newFontSize + 'px'
                });
            }

        });

    }

    /**
     * Toolstips
     *
     * Content beeing displayed when user is logged out and want to use functionality on the homepage
     *
     * Doc: http://iamceege.github.io/tooltipster/#getting-started
     */

    function initTooltips() {
        var $tooltips = $('.me-tooltip');
        if ($tooltips.length) {
            $tooltips.tooltipster({
                maxWidth: $tooltips.parent().width()
            }).on('click', function (e) {
                e.preventDefault();
            });
        }
    }

    function initToggleAllCheckboxes() {
        var $forms = $(".me-idm-portal-search-result-table");
        var $checkboxes = [];
        var $triggerCheckboxes = [];

        // extract checkboxes
        $forms.each(function (index) {
            var $collection = $(this).find('input[type="checkbox"]');
            $triggerCheckboxes.push($collection.last());
            $checkboxes.push($collection);
        });
    }

    function initMobileFunctions() {
        $('.toggle-nav').on('click', function (e) {
            e.preventDefault();
        });

        if ($(window).width() > 992) {
            //$.equalizer();
            $('[data-equalizer-inner] .me-block-inner').each(function () {
                $(this).css('min-height', $(this).parent().css('min-height'));
            });
        }

        /*if (isMobile()) {
         var $topbar = $('#topbar');
         lastY = $(window).scrollTop();
         
         $(window).on({
         touchmove: function(e) {
         var currentY = e.originalEvent.touches ? e.originalEvent.touches[0].pageY : e.pageY;
         
         if (Math.abs(currentY - lastY) < 20) { return; }
         
         if (currentY > lastY) {
         $topbar.show();
         } else {
         $topbar.hide();
         }
         
         lastY = currentY;
         }
         });
         
         }*/
    }

    /**
     * Functions called if deeplinks-status is active
     */
    function initDeeplinkFunctions() {
        var $loginBox = $('.me-login-box');

        // Disable close dropdown on click outside
        if ($body.hasClass('me-deeplink')) {
            $loginBox.on('hide.bs.dropdown', function () {
                return false;
            });
        }
    }

    /**
     * Blur Images with canvas (support down to IE9)
     */
    function initImageBlurCanvas() {
        var $blur = $('.blur');
        $blur.each(function (i) {
            var width = $(this).width();
            var height = $(this).parent().height();
            var id = 'blur-' + i;
            $(this).html('<canvas id="' + id + '" width="' + width + '" height="' + height + '" />');

            var c = $(this).find('canvas')[0];
            var ctx = c.getContext("2d");
            var canvasBackground = new Image();
            canvasBackground.src = $(this).data('bg');

            $(this).removeAttr('data-bg');

            var drawBlur = function () {
                // Store the width and height of the canvas for below
                var w = width;
                var h = height;
                // This draws the image we just loaded to our canvas
                ctx.drawImage(canvasBackground, 0, 0, w, h);
                // This blurs the contents of the entire canvas
                stackBlurCanvasRGBA(id, 0, 0, w, h, 30);
            }

            canvasBackground.onload = function () {
                drawBlur();
            }
        });
    }

    function initLoginToggle() {
        var $btn = $('[data-login]');
        $btn.on('click', function (e) {
            e.preventDefault();
            $('.me-login-box').toggleClass('opened');
        });
    }

    function initBlockLinkResize() {
        var $blocklink = $('.mib-suche .me-block-link, .me-news-item a');

        $blocklink.each(function () {
            var parentHeight = $(this).parent().height();
            $(this).css({
                "min-height": parentHeight
            });
        });
    }

    function initCollectionActions() {
        var $collection = $('.me-collection');
        var $editCollection = $collection.find('.icon-me-text-bearbeiten');

        $editCollection.on('click', function () {
            var $item = $(this).parents('.me-block-inner');
            var mode = $item.attr('data-mode');
            var $edit = $item.find('.me-edit');

            $collection.find('.active').removeClass('active');
            $collection.find('.me-edit').removeClass('visible');
            $collection.find('[data-mode]').attr('data-mode', '');

            if (mode !== 'edit') {
                $(this).addClass('active');
                $item.attr('data-mode', 'edit');
                $edit.addClass('visible');
            } else {
                $(this).removeClass('active');
                $(this).attr('data-mode', '');
                $edit.removeClass('visible');
            }
        });

        $(window).on('keyup', function (e) {
            var key = e.which;
            var enter = 13;
            var esc = 27;
            var $collection = $('.me-collection');
            var $active = $collection.find('[data-mode="edit"]');

            if (key == esc || key == enter) {
                $collection.find('.active').removeClass('active');
                $collection.find('.me-edit').removeClass('visible');
                $collection.find('[data-mode]').attr('data-mode', '');
            }
        });

    }

    function initPopupFix() {
        var $toggle = $('[data-toggle="modal"]');

        $toggle.on('click', function (event) {

            if ($(window).width() <= 768) {

                event.preventDefault();

                var $_this = $(this);
                var target = $_this.data('target');
                var $popup = $(target);

                setTimeout(function () {
                    var top = $_this.offset().top + 25;
                    $popup.css('top', top);
                }, 25);
            }

        });

    }

    function switchModalContents() {
        $('[data-switch]').on('click', function (e) {
            e.preventDefault();
            var _switch = $(this).data('switch');

            $(_switch[0]).hide();
            $(_switch[1]).show();
        });
    }

    function initStarRating() {
        var $rating = $('.me-search-result-rate');
        $rating.each(function () {

            // add class for coloring the rating stars and remove class with a delay when mouse leaves or touch gesture ends
            $(this).on('mouseenter touchstart', function () {
                $(this).addClass("personal-rating");
            });

            $(this).on('mouseleave touchend', function () {
                $(this).removeClass("personal-rating");
            });

            // attach event listener for hover effect
            var $stars = $(this).find('span');

            $stars.each(function (i) {
                $(this).on({
                    mouseenter: function () {
                        $(this).find('i').addClass('icon-me-stern_komplett').removeClass('icon-me-stern_leer');
                        $stars.filter(':lt(' + i + ')').find('i').addClass('icon-me-stern_komplett').removeClass('icon-me-stern_leer');
                    },
                    mouseleave: function () {
                        $(this).find('i').removeClass('icon-me-stern_komplett').addClass('icon-me-stern_leer');
                    }
                });
            });

            $(this).on('mouseleave', function () {
                $(this).find('i.icon-me-stern_komplett').removeClass('icon-me-stern_komplett').addClass('icon-me-stern_leer');
            });
        });
    }

    function initAnchorLinks() {
        var $menu = $(".me-menu-anchor-links");
        var $anchorLinks = $menu.children("li");

        $anchorLinks.on('click', 'a', function (e) {
            e.preventDefault();

            var anchor = $(this).attr('href');
            var anchorTop = $(anchor).offset().top - $('body').offset().top;
            var anchorOffset = parseInt($(anchor).css('margin-top')) / 2 + $('#topbar').height();
            if ($('header.me-page-header').css('position') === 'fixed') {
                anchorOffset += $('header.me-page-header').height();
            }
            anchorTop -= anchorOffset;
            $('body')
                    .velocity('stop')
                    .velocity('scroll', {duration: 800, offset: anchorTop});

            window.location.hash = anchor; // for older browsers, leaves a # behind
            history.pushState('', document.title, window.location.pathname + window.location.search + anchor);

        });
    }
    /*
     function initAnchorScrolling() {
     var $headlines = $("[data-anchor-link]");
     
     setTimeout(function(){
     $headlines.each(function(){
     anchorHeadlinePositions.push($(this).offset().top - 150);
     });
     }, 50);
     }
     
     function setAnchorClass() {
     var viewportPos = $(window).scrollTop();
     var markIndex;
     
     $.each(anchorHeadlinePositions, function(index, element){
     if (viewportPos > element) {
     markIndex = index;
     }
     });
     
     $(".me-menu-anchor-links li").removeClass("active").eq(markIndex).addClass("active");
     }
     */
    function handleSelectboxNavChange() {
        var $selectbox = $('[data-change]');

        $selectbox.on('click', function () {
            $(this).addClass('open');
        });
    }

    function initStickyHeader() {
        $win.on('scroll', function () {
            if ($(this).scrollTop() > 50) {
                $('body').addClass('sticky-header');
            } else {
                $('body').removeClass('sticky-header');
            }
        });
    }

    /**
     * Animate resizing header and back to top button on scroll
     */
    function scrollExperience() {
        //hide to top button at the beginning
        if ($('.me-back-top').length) {
            $('.me-back-top').hide();
        }
        //get scroll event
        $win.scroll(function () {
            didScroll = true;
        });

        setInterval(function () {
            if (didScroll) {
                didScroll = false;

                //header 
                var distanceY = $win.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop;
                var shrinkOn = 300;
                var $header = $('header');
                var $navbarTop = $('#topbar');

                if (distanceY > shrinkOn) {
                    $header.addClass('smaller');
                    $navbarTop.addClass('smaller');
                } else {
                    $header.removeClass('smaller');
                    $navbarTop.removeClass('smaller');
                }

                //to top button
                if ($(this).scrollTop() > backTopAppearOn) {
                    $('.me-back-top').fadeIn();
                } else {
                    $('.me-back-top').fadeOut();
                }
            }
        }, 250);
    }

    /**
     * Prevent the overlapping of the sidebar jump navigation with the to top button
     */
    function preventOverlappingSidebar() {
        var $anchorLinks = $('.me-page-action-menu .me-menu-anchor-links');
        //topActionMenu value must be the same as the value in theme\mebis\mbsglobaldesign\scss\layout.scss
        //.me-page-action-menu {  top: 250px; }
        //we can not use $anchorLinks.offset().top, because this value always changes
        var topActionMenu = 250;
        var offsetInPageMenu = $('.me-in-page-menu').offset();

        if ($anchorLinks.offset().top + $anchorLinks[0].offsetHeight >= offsetInPageMenu.top) {
            //prevent overlapping elements
            var distance = topActionMenu + $anchorLinks[0].offsetHeight + 10;
            backTopAppearOn = distance;
            $('.me-page-action-menu').addClass('overlap');
        } else {
            $('.me-page-action-menu').removeClass('overlap');
        }
    }

    return {
        init: function () {

            $win = $(window);
            $body = $('body');

            scrollToTop();
            scrollExperience();
            //initInvertContrastSwitch();
            handleFontSizeSwitch();
            initTooltips();
//            initSmoothscrolling();
            initCarousel();
            initToggleAllCheckboxes();
            initMobileFunctions();
            initDeeplinkFunctions();
            initImageBlurCanvas();
            initLoginToggle();
            initBlockLinkResize();
            initCollectionActions();
            initPopupFix();
            switchModalContents();
            initStarRating();
            initAnchorLinks();
            //initAnchorScrolling();
            //setAnchorClass();
            handleSelectboxNavChange();
            initStickyHeader();
            preventOverlappingSidebar();
        },
        resize: function () {
            initBlockLinkResize();
            $.equalizer();
            preventOverlappingSidebar();
        },
        orientationchange: function () {
            initBlockLinkResize();
            initImageBlurCanvas();
            setTimeout(function () {
                $.equalizer();
            }, 50);
        },
        scroll: function () {
            //setAnchorClass();
        }
    }

})(jQuery);

$(function () {
    Mebis.init();

    $(window).on('resize', function () {
        Mebis.resize();
    });

    $(window).on('orientationchange', function () {
        Mebis.orientationchange();
    });

});