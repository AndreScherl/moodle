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
use block_mbstpl\dataobj\license;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

global $OUTPUT, $PAGE, $DB;

require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('blockmbstplmanagelicenses');

$usedshortnames = mbst\dataobj\license::fetch_all_used_shortnames();

$deleteid = optional_param('deletelicenseid', 0, PARAM_INT);
if ($deleteid) {
    $license = license::fetch(array('id' => $deleteid));
    if ($license) {
        if (in_array($license->shortname, $usedshortnames)) {
            throw new moodle_exception('exceptiondeletingusedlicense', 'block_mbstpl');
        }
        $DB->delete_records(license::get_tablename(), array('id' => $deleteid));
        redirect($PAGE->url);
    }
}

$form = new mbst\form\addlicense();
if ($data = $form->get_data()) {

    $license = new mbst\dataobj\license(array(
        'shortname' => $data->newlicense_shortname,
        'fullname' => $data->newlicense_fullname,
        'source' => $data->newlicense_source
    ));
    $license->insert();
    redirect($PAGE->url);
}

$licenses = mbst\dataobj\license::fetch_all(array());

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('licenses_header', 'block_mbstpl'));

$renderer = mbst\course::get_renderer();
echo $renderer->license_table($licenses, $usedshortnames);

echo $OUTPUT->heading(get_string('newlicense', 'block_mbstpl'), 3);
echo $form->display();

echo $OUTPUT->footer();

