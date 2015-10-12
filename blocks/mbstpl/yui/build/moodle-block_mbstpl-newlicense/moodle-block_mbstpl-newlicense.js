YUI.add('moodle-block_mbstpl-newlicense', function (Y, NAME) {

M.block_mbstpl = M.block_mbstpl || {};
M.block_mbstpl.newlicense = {
	init: function(newlicensename, licensename) {

		var licenseEl = Y.one('#id_' + licensename);
		var newlicenseInputEls = Y.one('#fgroup_id_' + newlicensename).all('input');

		licenseEl.on('change', function() {
			var isNew = licenseEl.get('value') === '__createnewlicense__';
			newlicenseInputEls.set('disabled', !isNew);
		});
	}
};


}, '@VERSION@', {"requires": ["base", "node"]});
