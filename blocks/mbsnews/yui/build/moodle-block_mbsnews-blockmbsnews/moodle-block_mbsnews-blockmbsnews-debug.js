YUI.add('moodle-block_mbsnews-blockmbsnews', function (Y, NAME) {

M.block_mbsnews = M.block_mbsnews || {};
M.block_mbsnews.blockmbsnews = function (args) {

    function doSubmit(params, callback) {
        Y.io(args.url, {
            data: params,
            on: {
                success: function (id, resp) {

                    var result;
                    try {
                        result = Y.JSON.parse(resp.responseText);
                    } catch (e) {
                        return;
                    }
                    if (result.error !== 0) {
                        alert(result.error);
                    } else {
                        callback(result.results);
                    }
                }
            }
        });
    }

    function markAsRead(messageid) {

        var params = {};
        params.action = "markasread";
        params.messageid = messageid;
        
        doSubmit(params, function (r) {
            markAsReadResult(r);
        });
    }

    function markAsReadResult(result) {
        
        Y.one('#mbsnewsmessage_' + result.id).hide();

    }

    function initialize() {

        Y.all('a[id^="mbsnewsdelete"]').each(
                function (item) {

                    item.on('click', function (e) {
                        e.preventDefault();

                        var messageid = Number(item.get('id').split('_')[1]);
                        markAsRead(messageid);
                    });
                }
        );
    }

    initialize();

};


}, '@VERSION@', {"requires": ["base", "node", "io-base"]});
