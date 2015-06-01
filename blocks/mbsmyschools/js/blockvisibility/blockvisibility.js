// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * helper functions
 *  - to hide the block
 *  - to delete the block
 *  - for logging
 *
 * @package    block_mbsmyschools
 * @copyright  2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$(document).ready(function () {
    M.block_mbsmyschools.visibility.init();
});

/*
 * Namespace block mbsmyschools
 */
M.block_mbsmyschools = M.block_mbsmyschools || {};

/*
 * Namespace visibility
 */
M.block_mbsmyschools.visibility = M.block_mbsmyschools.visibility || {}

/*
 * Intitialize
 */
M.block_mbsmyschools.visibility.init = function () {

    $('#mbsmyschools_closeforever').on('click', (function () {
        $.post(
            M.cfg['wwwroot'] + '/blocks/mbsmyschools/blockvisibility.php',
            {sesskey: M.cfg.sesskey, forever: true},
            function (data) {
                M.block_mbsmyschools.visibility.closealert(data);
            }
        );
    }));
};

/*
 * Function for deleting block mbsmyschools forever
 */
M.block_mbsmyschools.visibility.closealert = function (data) {

    var dialog = new M.core.dialogue({
        draggable: true,
        bodyContent: data,
        centered: true,
        modal: true,
        visible: true,
        closeButton: false,
        zIndex: 100
    });

    dialog.render();
    dialog.show();

    $('#closealert').on('click', function () {
        dialog.hide();
        $('.block_mbsmyschools').remove();
    });
};