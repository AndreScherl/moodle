YUI.add('moodle-block_mbsnews-editjobform', function (Y, NAME) {

M.block_mbsnews = M.block_mbsnews || {};
M.block_mbsnews.editjobform = function (args) {

    var fcontextlevel;
    var finstanceidslist;
    var finstanceidssearch;
    var finstanceids;
    var froleselector;
    var froleid;
    var frecipients;
    var frecipientscount;

    function disableElements() {

        var contextlevel = fcontextlevel.get('value');

        // Contextlevel is not selected or syste context.
        if (contextlevel == 0 || contextlevel == 10) {
            finstanceidssearch.set('disabled', 'disabled');
        } else {
            finstanceidssearch.removeAttribute('disabled');
        }
    }

    function resetForm() {

        // Reset Lookup Search.
        finstanceids.set('value', '');
        finstanceidslist.set('innerHTML', '');

        finstanceidssearch.set('value', '');

        // Reset roleid.
        froleselector.set('value', 0);
        froleid.set('value', 0);
    }

    function onContextLevelChanged() {
        resetForm();
        loadRoles();
        disableElements();
    }

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

    function loadRoles() {

        var params = {};
        params.action = "getroleoptions";
        params.contextlevel = fcontextlevel.get('value');

        doSubmit(params, function (r) {
            loadRolesResult(r);
        });
    }

    function loadRolesResult(options) {

        froleselector.set('innerHTML', '');
        for (var i = 0; i < options.length; i++) {
            var role = options[i];
            froleselector.append(Y.Node.create('<option value="' + role.value + '">' + role.text + '</option>'));
        }
        froleselector.set('value', froleid.get('value'));
    }

    function doSearch() {

        var params = {};
        params.action = "searchrecipients";
        params.contextlevel = fcontextlevel.get('value');
        params.roleid = froleid.get('value');

        // Collect instances.
        var instanceidsselected = new Array();
        Y.all('input[name^="instanceidsselected"]').each(
                function (item, index) {
                    var id = item.get('name').split("[")[1];
                    id = id.substr(0, id.length - 1);
                    instanceidsselected[index] = id;
                });

        params.instanceids = instanceidsselected.join(',');

        doSubmit(params, function (r) {
            doSearchResult(r);
        });
    }

    function doSearchResult(result) {
        frecipients.set('innerHTML', result.list);
        frecipientscount.set('value', result.count);
    }

    function initialize() {

        fcontextlevel = Y.one('#id_contextlevel');
        finstanceids = Y.one('#id_instanceids');
        finstanceidslist = Y.one('#id_instanceids_list');
        finstanceidssearch = Y.one('#id_instanceids_search');
        froleselector = Y.one('#id_roleselector');
        froleid = Y.one('#id_roleid');
        frecipients = Y.one('#id_recipients');
        frecipientscount = Y.one('#id_countrecipients');

        fcontextlevel.on('change', function (e) {
            onContextLevelChanged();
            doSearch();
        });

        finstanceids.on('change', function (e) {
            doSearch();
        });

        froleselector.on('change', function (e) {
            froleid.set('value', froleselector.get('value'));
            doSearch();
        });

        disableElements();
        loadRoles();
        doSearch();
    }

    initialize();
};


}, '@VERSION@', {"requires": ["base", "node", "io-base"]});
