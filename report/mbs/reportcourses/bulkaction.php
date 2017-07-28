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
 * Report courses
 *
 * @package    report_mbs
 * @copyright  ISB Bayern
 * @author     Andreas Wagner<andreas.wagner@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../../config.php');

global $CFG, $PAGE, $OUTPUT;

require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('reportorphanedcourses', '', null, '', array('pagelayout' => 'admin'));

$baseurl = new moodle_url('/report/mbs/reportcourses/bulkaction.php');
$PAGE->set_url($baseurl);

$action = required_param('bulkaction', PARAM_TEXT);
$redirecturl = new moodle_url('/report/mbs/reportcourses/index.php');
\report_mbs\local\reportcourses::require_valid_action($action, $redirecturl);

$courseids = required_param('courseids', PARAM_TEXT);
$formname = '\report_mbs\form\bulk_' . $action . '_form';

$form = new $formname($baseurl, array('courseids' => $courseids));

$message = '';
if ($data = $form->get_data()) {

    $result = $form->do_action($data);

    if ($result['error'] == 0) {
        redirect($redirecturl, $result['message']);
    } else {
        $message = $result['message'];
    }
}

echo $OUTPUT->header();

if (!empty($message)) {
    echo $OUTPUT->notification($message, 'notifyproblem');
}

$form->display();

echo $OUTPUT->footer();
