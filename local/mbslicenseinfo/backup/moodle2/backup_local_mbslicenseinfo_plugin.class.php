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
 * @package local_mbslicenseinfo
 * @copyright 2016 Andreas Wagner
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Provides the information to backup grid course format
 */
class backup_local_mbslicenseinfo_plugin extends backup_local_plugin {

    /**
     * Returns the format information to attach to module element
     */
    protected function define_course_plugin_structure() {
        global $DB;

        $plugin = $this->get_plugin_element();

        $licenseinfos = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($licenseinfos);

        $metainfos = new backup_nested_element('metainfos');
        $licenseinfos->add_child($metainfos);

        $meta = new backup_nested_element('fmeta', array('id'), array('title', 'source', 'files_id'));
        $metainfos->add_child($meta);

        $courseid = $this->task->get_courseid();
        $coursecontext = \context_course::instance($courseid);

        $incourse = $DB->sql_like('c.path', ':contextpath');
        $params = array('contextpath' => array('sqlparam' => $coursecontext->path . '%'));

        $sql = "SELECT f.id, li.title, li.source, li.files_id
                  FROM {files} AS f
                  JOIN {context} AS c ON f.contextid = c.id
                  JOIN {local_mbslicenseinfo_fmeta} li ON f.id = li.files_id
                 WHERE $incourse ";

        $meta->set_source_sql($sql, $params);

        return $plugin;
    }

}
