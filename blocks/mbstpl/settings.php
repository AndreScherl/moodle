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
 * @package block_mbstpl
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use block_mbstpl\admin_setting_configdate;

defined('MOODLE_INTERNAL') || die;
global $CFG;

global $CFG;
/* @var $settings admin_settingpage */

if ($ADMIN->fulltree) {

    $options = array();
    $options += coursecat::make_categories_list('moodle/category:manage', 0);
    $settings->add(new admin_setting_configselect('block_mbstpl/deploycat',
                                                  get_string('deploycat', 'block_mbstpl'), null, null, $options));

    $roles = get_all_roles();
    $roles = role_fix_names($roles, null, ROLENAME_BOTH, true);
    $settings->add(new admin_setting_configselect('block_mbstpl/reviewerrole',
                                                  get_string('reviewerrole', 'block_mbstpl'),
                                                  get_string('reviewerrole_desc', 'block_mbstpl'), null, $roles));

    $settings->add(new admin_setting_configselect('block_mbstpl/authorrole',
                                                  get_string('authorrole', 'block_mbstpl'),
                                                  get_string('authorrole_desc', 'block_mbstpl'), null, $roles));

    $settings->add(new admin_setting_configselect('block_mbstpl/teacherrole',
                                                  get_string('teacherrole', 'block_mbstpl'),
                                                  get_string('teacherrole_desc', 'block_mbstpl'), null, $roles));

    $settings->add(new admin_setting_configtext('block_mbstpl/complainturl',
                                                  get_string('complainturl', 'block_mbstpl'),
                                                  get_string('complainturl_desc', 'block_mbstpl'), null, PARAM_URL));


    $settings->add(new admin_setting_configdate('block_mbstpl/nextstatsreport',
                                                  get_string('nextstatsreport', 'block_mbstpl'),
                                                  get_string('nextstatsreport_desc', 'block_mbstpl'), 0));
    $settings->add(new admin_setting_configcheckbox('block_mbstpl/delayedrestore',
                                                  get_string('delayedrestore', 'block_mbstpl'),
                                                  get_string('delayedrestore_desc', 'block_mbstpl'), false));

    $settings->add(new admin_setting_configcheckbox('block_mbstpl/delayedrestore',
                                                  get_string('delayedrestore', 'block_mbstpl'),
                                                  get_string('delayedrestore_desc', 'block_mbstpl'), false));

    $settings->add(new admin_setting_configduration('block_mbstpl/tplremindafter',
                                                  get_string('tplremindafter', 'block_mbstpl'),
                                                  get_string('tplremindafter_desc', 'block_mbstpl'),
                                                  DAYSECS * 180, DAYSECS));

}

$category = new admin_category('block_mbstpl', get_string('pluginnamecategory', 'block_mbstpl'));
$ADMIN->add('blocksettings', $category);

$category->add('block_mbstpl', new admin_externalpage('blockmbstplmanagelicenses',
    get_string('licenses_header', 'block_mbstpl'), "$CFG->wwwroot/blocks/mbstpl/editlicenses.php"));

$category->add('block_mbstpl', new admin_externalpage('blockmbstplmanagesearch',
    get_string('managesearch', 'block_mbstpl'), "$CFG->wwwroot/blocks/mbstpl/questman/managesearch.php"));

$category->add('block_mbstpl', new admin_externalpage('blockmbstplmanageqforms',
    get_string('manageqforms', 'block_mbstpl'), "$CFG->wwwroot/blocks/mbstpl/questman/index.php"));

unset($category);
