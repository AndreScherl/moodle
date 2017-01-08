define(['jquery', 'core/str'], function ($) {

    function onPreviewClicked(icon) {

        var preview = $(icon).find('.mbslicenseinfo-preview-content');

        if (preview) {

            var dialog = new M.core.dialogue({
                draggable: true,
                bodyContent: preview.html(),
                headerContent: 'Preview',
                centered: true,
                modal: true,
                visible: true,
                closeButton: true,
                zIndex: 100
            });

            dialog.render();
            dialog.show();
        }
    }

    function onTransferClicked(button) {

        var contenthash = $(button).attr('id').split('_')[1];
        var fieldset = $('#id_' + contenthash);
        var fieldnames = ['title', 'author', 'filesource'];

        if (fieldset) {

            // Transfer textinput.
            $.each(fieldnames, function (index, name) {

                var titleinput = fieldset.find('input[name^="' + name + '"]');
                var value = $(titleinput).first().prop('value');

                if (value) {
                    $(titleinput).each(
                            function () {
                                $(this).attr('value', value);
                            }
                    );
                }
            });

            // Transfer license.
            var licenseselect = fieldset.find('select[name^="licenseshortname"]');
            var value = $(licenseselect).first().val();
            var lname = $(licenseselect).first().siblings('input[name^="licensefullname"]').val();
            var lsource = $(licenseselect).first().siblings('input[name^="licensesource"]').val();

            if (value) {
                $(licenseselect).each(
                        function () {

                            $(this).val(value);

                            var fullname = $(this).siblings('input[name^="licensefullname"]');
                            var fullsource = $(this).siblings('input[name^="licensesource"]');

                            if (value === '__createnewlicense__') {
                                fullname.show();
                                fullname.val(lname);
                                fullsource.show();
                                fullsource.val(lsource);
                            } else {
                                fullname.hide();
                                fullsource.hide();
                            }
                        }
                );
            }

        }
    }

    return {
        init: function () {

            $('div.editlicenses .mbslicenseinfo-previewicon').on('click',
                    function (e) {
                        e.stopPropagation();
                        onPreviewClicked(this);
                    });


            $('.mbslicenseinfo-transferbutton').on('click',
                    function () {
                        onTransferClicked(this);
                    });

        }
    };
});