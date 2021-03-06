/**
 * Created by shramee on 11/5/15.
 */
jQuery(document).ready(function ($) {

    //Our fixitigation element caching for better performance
    var fixit = $('#wp-content-editor-tools'),
        container = $('#postdivrich'),
        panelsBar = $('#so-panels-panels .hndle');

    //fixit offset cached since it will change later
    var stickyFixitTop = fixit.offset().top;

    panelsBar.outerWidth(container.width() - 2);

    //stickyfixit function
    function stickyfixit() {

        if (!fixit.parent().hasClass('panels-active')) {
            container.removeClass('sticky');
            return;
        }

        panelsBar.outerWidth(container.width() - 2);

        //Current Scroll Position
        var scrollTop = $(window).scrollTop();

        if (scrollTop > stickyFixitTop - 22 && $(document).width() > 768) {
            //Adding class for distributing styles to stylesheet
            container.addClass('sticky');

        } else {
            //Removing CSS class
            container.removeClass('sticky');
        }
    };

    //On runtime if page is already scrolled down
    stickyfixit();

    //For every scroll
    $(window).scroll(function () {
        stickyfixit();
    });

});