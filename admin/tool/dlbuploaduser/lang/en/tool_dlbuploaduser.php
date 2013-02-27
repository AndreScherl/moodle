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
 * Strings for component 'tool_uploaduser', language 'en', branch 'MOODLE_22_STABLE'
 *
 * @package    tool
 * @subpackage dlbuploaduser
 * @copyright  2011 Petr Skoda {@link http://skodak.org}
 * @modifier   20120 Ulrich Weber
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'DLB User upload';
$string['uploadusers'] = 'DLB Upload users';
$string['uploadusers_help'] = 'Users may be uploaded (and optionally enrolled in courses) via text file. The format of the file should be as follows:

* Each line of the file contains one record
* Each record is a series of data separated by commas (or other delimiters)
* The first record contains a list of fieldnames defining the format of the rest of the file
* Required fieldnames are username, password, firstname, lastname, email';
$string['download_imported_users'] = 'Liste zuletzt importierter Nutzer herunterladen';
$string['csvexport'] = 'CSV-Export';
$string['pdfexport'] = 'PDF-Export';
$string['institutionerror'] = 'Fehler beim Upload: keine Institution eingetragen. 
Bitte wenden Sie sich an den Support!';
$string['departmentunknown']  = 'Ungültige Rolle (Lehrer/Schüler)';
$string['actionunknown']  = 'Ungültige Aktion (insert/update)';
