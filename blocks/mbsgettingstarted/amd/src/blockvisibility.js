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
 * @package    block_mbsgettingstarted
 * @copyright  2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/config'], function($, config) {
    
    var blockvisibility = {
        /*
         * Function for deleting fake block mbsgettingstarted forever
         */
        closealert: function(data) {

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

            $('#closealert').on('click', function() {
                dialog.hide();
                $('#block_mbsgettingstarted').remove();
            });
        },

        /*
         * Function for logging which link was used
         */
        getidsforlogging: function(id) {
            $.post(
                config.wwwroot + '/blocks/mbsgettingstarted/usedmbsgettingstarted.php',
                {sesskey: config.sesskey, id: id}
            );
        }
    };
    
    return {
        init: function() {
            $('#mbsgettingstarted_closeforever').on('click', function() {
                $.post(
                    config.wwwroot + '/blocks/mbsgettingstarted/blockvisibility.php',
                    {sesskey: config.sesskey, forever: true},
                    function(data) {
                        blockvisibility.closealert(data);
                    }
                );
            });

            $('#mbsgettingstarted_closeforsession').on('click', function() {
                $.post(
                    config.wwwroot + '/blocks/mbsgettingstarted/blockvisibility.php',
                    {sesskey: config.sesskey, hide: true},
                    function() {
                        $('#block_mbsgettingstarted').remove();
                    }
                );
            });

            $('#block_mbsgettingstarted, .a').on('click', function(event) {
                blockvisibility.getidsforlogging(event.target.id);
            });
        }
    };
});


