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
 * Report pimped courses (style and js customisations using html - block)
 * settings.
 *
 * @package    report
 * @subpackage mbs
 * @copyright  ISB Bayern
 * @author     Andreas Wagner<andreas.wagern@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports', new admin_category('mebisreportsfolder', get_string('pluginname', 'report_mbs')));

// Just a link to directorate report.
$ADMIN->add('mebisreportsfolder', new admin_externalpage('reportpimpedcourses', get_string('reportpimped', 'report_mbs'),
                "$CFG->wwwroot/report/mbs/reportpimped.php", 'moodle/site:config'));

$ADMIN->add('mebisreportsfolder', new admin_externalpage('reporttex', get_string('replacetex', 'report_mbs'),
                "$CFG->wwwroot/report/mbs/reporttex.php", 'moodle/site:config'));

$ADMIN->add('mebisreportsfolder', new admin_externalpage('reportorphanedcourses', get_string('reportorphaned', 'report_mbs'),
                "$CFG->wwwroot/report/mbs/reportcourses.php", 'moodle/site:config'));

if ($ADMIN->fulltree) {

    $settings = new admin_settingpage('reportmbs',
                    get_string('pluginname', 'report_mbs'));

    $settings->add(new admin_setting_heading('reportpimpedheading',
                    get_string('reportpimped', 'report_mbs'), ''));

    $settings->add(
            new admin_setting_configtext('report_mbs/searchpattern',
                    get_string('searchpattern', 'report_mbs'),
                    get_string('searchpatterndesc', 'report_mbs'), '<script|<style', PARAM_RAW));

    $settings->add(new admin_setting_heading('replacetexheading',
                    get_string('replacetex', 'report_mbs'), ''));

    $settings->add(
            new admin_setting_configcheckbox('report_mbs/texcronactiv',
                    get_string('texcronactiv', 'report_mbs'),
                    get_string('texcronactivdesc', 'report_mbs'), 0));

    $settings->add(new admin_setting_heading('reportorphanedcourses',
                    get_string('replacetex', 'report_mbs'), ''));

    $settings->add(
            new admin_setting_configtext('report_mbs/reportcourseperpage',
                    get_string('reportcourseperpage', 'report_mbs'),
                    get_string('reportcourseperpagedesc', 'report_mbs'), 20, PARAM_INT));
}