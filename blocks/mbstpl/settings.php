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
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $questmanurl = new moodle_url('/blocks/mbstpl/questman/index.php');
    $questmanlink = html_writer::link($questmanurl, get_string('manageqforms', 'block_mbstpl'));
    $searchqurl = new moodle_url('/blocks/mbstpl/questman/managesearch.php');
    $managesearchlink = html_writer::link($searchqurl, get_string('managesearch', 'block_mbstpl'));

    $links = $questmanlink . html_writer::empty_tag('br') . $managesearchlink;
    $settings->add(new admin_setting_heading('questman', get_string('settings'), $links));


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

    $settings->add(new admin_setting_configcheckbox('block_mbstpl/delayedrestore',
                                                  get_string('delayedrestore', 'block_mbstpl'),
                                                  get_string('delayedrestore_desc', 'block_mbstpl'), false));

}
