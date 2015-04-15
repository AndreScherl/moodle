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
 * mebis my courses block (based on course overview block)
 *
 * @package    block_mbsmycourses
 * @copyright  2015 Andreas Wagner <andreas.wagener@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('block_mbsmycourses/defaultmaxcourses',
                    new lang_string('defaultmaxcourses', 'block_mbsmycourses'),
                    new lang_string('defaultmaxcoursesdesc', 'block_mbsmycourses'), 10, PARAM_INT));

    $settings->add(new admin_setting_configcheckbox('block_mbsmycourses/forcedefaultmaxcourses',
                    new lang_string('forcedefaultmaxcourses', 'block_mbsmycourses'),
                    new lang_string('forcedefaultmaxcoursesdesc', 'block_mbsmycourses'), 1, PARAM_INT));

    $settings->add(new admin_setting_configcheckbox('block_mbsmycourses/showchildren',
                    new lang_string('showchildren', 'block_mbsmycourses'),
                    new lang_string('showchildrendesc', 'block_mbsmycourses'), 1, PARAM_INT));
}
