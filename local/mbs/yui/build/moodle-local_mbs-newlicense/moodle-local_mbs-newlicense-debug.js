YUI.add('moodle-local_mbs-newlicense', function (Y, NAME) {

M.local_mbs = M.local_mbs || {};
M.local_mbs.newlicense = {
    init: function (data) {

        var licenses = Y.all('select[id^="id_asset_license_"]');

        if (licenses) {

            licenses.each(function (item) {

                var number = Number(item.get('id').split('_')[3]);
                M.local_mbs.newlicense.setLock(number, item);

                item.on('change', function () {
                    M.local_mbs.newlicense.setLock(number, item);
                });
            });
        }
    },
    setLock: function (number, selectitem) {
        var isNew = selectitem.get('value') === '__createnewlicense__';
        M.local_mbs.newlicense.setLockElement('#id_newlicense_shortname_' + number, !isNew);
        M.local_mbs.newlicense.setLockElement('#id_newlicense_fullname_' + number, !isNew);
        M.local_mbs.newlicense.setLockElement('#id_newlicense_source_' + number, !isNew);
    },
    setLockElement: function (id, lock) {

        Y.one(id).set('disabled', lock);
        if (!lock) {
            Y.one(id).show();
        } else {
            Y.one(id).hide();
        }
    }
};


}, '@VERSION@', {"requires": ["base", "node"]});
