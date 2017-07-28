define(['jquery', 'report_mbs/select2', '../amd/src/i18n/de.js'], function ($) {

    return {
        init: function () {

            $("#id_categoryid").select2({
                /*  language: {
                 // You can find all of the options in the language files provided in the
                 // build. They all must be functions that return the string that should be
                 // displayed.
                 loadingMore: function () {
                 return "test";
                 }
                 },
                language: "de",*/
                ajax: {
                    url: "http://localhost/mebis/report/mbs/ajax.php",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term, // search term
                            page: params.page
                        };
                    },
                    processResults: function (data, params) {
                        // parse the results into the format expected by Select2
                        // since we are using custom formatting functions we do not need to
                        // alter the remote JSON data, except to indicate that infinite
                        // scrolling can be used
                        params.page = params.page || 1;
                        return {
                            results: data.items,
                            pagination: {
                                more: (params.page * 30) < data.total_count
                            }
                        };
                    },
                    cache: true
                },
                escapeMarkup: function (markup) {
                    return markup;
                }, // let our custom formatter work
                minimumInputLength: 1,
                templateResult: function (repo) {
                    if (repo.loading) {
                        return repo.text;
                    }
                    return repo.name;
                },
                templateSelection: function (repo) {
                    return repo.name || repo.text;
                }
            }
            );

            $.fn.select2.defaults.set('amdLanguageBase', 'i18n/');
        }
    };
});