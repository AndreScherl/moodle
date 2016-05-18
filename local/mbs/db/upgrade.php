<?php
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
 * @since      Moodle 2.7
 * @package    local_mbs
 * @copyright  2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Handles upgrading instances of this plugin
 *
 * @param int $oldversion
 * @param object $block
 */
function xmldb_local_mbs_upgrade($oldversion) {

    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2015120907) {        
        \local_mbs\local\licensemanager::install_licenses();

        upgrade_plugin_savepoint(true, 2015120907, 'local', 'mbs');
    }

    if ($oldversion < 2016011100) {        
        \local_mbs\local\licensemanager::install_licenses();

        upgrade_plugin_savepoint(true, 2016011100, 'local', 'mbs');
    }

    return true;
}

