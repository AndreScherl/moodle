define(['jquery', 'core/str'], function ($, str) {

    function onCheckAllClicked(element) {

        var checkboxes = $('input[id^="course_"]');

        checkboxes.each(function () {
            $(this).prop("checked", element.checked);
        });
    }

    function getCheckedCourseIds() {

        var checkboxes = $('input[id^="course_"]');
        var elemchecked = [];

        checkboxes.each(function () {
            if ($(this).prop("checked")) {
                elemchecked.push($(this).val());
            }
        });

        return elemchecked;
    }

    function onActionClick() {

        if (!$('#id_bulkaction').val()) {
            str.get_string('bulkactionrequired', 'report_mbs').done(
                    function (s) {
                        window.alert(s);
                    }
            );
            return false;
        }

        var courseids = getCheckedCourseIds();

        if (courseids.length === 0) {
             str.get_string('selectonecourse', 'report_mbs').done(
                    function (s) {
                        window.alert(s);
                    }
            );
            return false;
        }

        $('#id_courseids').val(courseids.join(','));

        return true;
    }

    return {
        init: function () {
            $('#coursecheckall').click(
                    function () {
                        onCheckAllClicked(this);
                    }
            );

            $('#bulkaction-form').submit(
                    function () {
                        return onActionClick();
                    }
            );


        }
    };
});