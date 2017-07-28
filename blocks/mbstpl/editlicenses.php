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
 * @copyright 2015 Janek Lasocki-Biczysko, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_mbstpl as mbst;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

global $OUTPUT, $PAGE, $DB;

require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('blockmbstplmanagelicenses');

$usedshortnames = \local_mbs\local\licensemanager::get_all_used_shortnames();

$deleteid = optional_param('deletelicenseid', 0, PARAM_INT);
$unchecklid = optional_param('unchecklid', 0, PARAM_INT);
$checklid = optional_param('checklid', 0, PARAM_INT);

if ($deleteid) {
    $license = \local_mbs\local\licensemanager::get_core_license(array('id' => $deleteid));
    if ($license) {
        if (in_array($license->shortname, $usedshortnames)) {
            throw new moodle_exception('exceptiondeletingusedlicense', 'block_mbstpl');
        }
        $DB->delete_records('license', array('id' => $deleteid));
        mbst\course::remove_course_license($license->shortname);
        redirect($PAGE->url);
    }
}

if ($unchecklid) {
    $license = \local_mbs\local\licensemanager::get_core_license(array('id' => $unchecklid));
    mbst\course::remove_course_license($license->shortname);
    redirect($PAGE->url);
}

if ($checklid) {
    $license = \local_mbs\local\licensemanager::get_core_license(array('id' => $checklid));
    mbst\course::add_course_license($license->shortname);
    redirect($PAGE->url);
}

$form = new mbst\form\addlicense();
if ($data = $form->get_data()) {

    $license = new \stdClass();
    $license->shortname = $data->newlicense_shortname;
    $license->fullname = $data->newlicense_fullname;
    $license->source = $data->newlicense_source;
    $license->version = date("Ymd").'00';
    $license->enabled = true;
    
    \local_mbs\local\licensemanager::new_core_license($license);
    
    redirect($PAGE->url);
}

$licenses = \local_mbs\local\licensemanager::get_core_licenses();

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('licenses_header', 'block_mbstpl'));

$renderer = mbst\course::get_renderer();
echo $renderer->license_table($licenses, $usedshortnames);

echo $OUTPUT->heading(get_string('newlicense', 'block_mbstpl'), 3);
echo $form->display();

echo $OUTPUT->footer();

