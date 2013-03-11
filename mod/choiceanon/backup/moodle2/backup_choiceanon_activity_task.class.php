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
 * Defines backup_choiceanon_activity_task class
 *
 * @package     mod_choiceanon
 * @category    backup
 * @copyright   2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/choiceanon/backup/moodle2/backup_choiceanon_stepslib.php');
require_once($CFG->dirroot . '/mod/choiceanon/backup/moodle2/backup_choiceanon_settingslib.php');

/**
 * Provides the steps to perform one complete backup of the Choice instance
 */
class backup_choiceanon_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the choiceanon.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_choiceanon_activity_structure_step('choiceanon_structure', 'choiceanon.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        // Link to the list of choices
        $search="/(".$base."\/mod\/choiceanon\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@CHOICEANONINDEX*$2@$', $content);

        // Link to choiceanon view by moduleid
        $search="/(".$base."\/mod\/choiceanon\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@CHOICEANONVIEWBYID*$2@$', $content);

        return $content;
    }
}
