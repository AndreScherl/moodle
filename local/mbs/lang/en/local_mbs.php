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
 * en - language file for local_mbs
 *
 * @package    local_mbs
 * @copyright  2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['enrolledteachers'] = 'Enrolled teachers';
$string['fixgapcategories'] = 'Fix course sortorder in categories';
$string['mbs:adddeleteblock'] = 'Add or/and delete a specific block';
$string['mbs:editschoolid'] = 'Edit school ID-Number';
$string['mbs:globalblockscleanup'] = 'Global cleanup of all (user) blocks';
$string['mbs:viewcourselist'] = 'View list of courses';
$string['mbs:viewteacherlist'] = 'View list of teachers on category management page';
$string['pluginname'] = 'Mebis - Adjustments';
$string['schoolnode'] = 'My schools';

// Privacy.
$string['mbs:institutionview'] = 'See users from other institutions';
$string['invalidredirect'] = 'This routing is not permitted.';

$string['notallowedtoaccessuserreport'] = 'Access to the User Report is not permitted.';
$string['notallowedtoaccessrecentactivities'] = 'Access to New is not permitted.';

$string['nopermissiontoedituser'] = 'You can not edit the profile of this user.'.
'<p>Only users from the same school are allowed to edit a profile.</p>';

$string['noinstitutionerror'] = 'You have not been assigned to an instituation (e.g. a school) and can not perform this action therefore.'.
'Please contact the support, allowing to make the assignment to an institution.';

$string['nopermissiontoviewuser'] = 'You may not view this user.'.
'<h1>Visibility control of users</h1>'.
'<p>Regular users can see on the platform only users in the same school or users with whom they participate in a course.</p>';    

// Sidebar Navigation.
$string['local_mbs_mebis_sites'] = 'Navigation links';
$string['local_mbs_mebis_sites_expl'] = 'These navigation elements are shown within the sidebar navigation.'.
        'Each element should be given its own line, following this rule: <b>Link,URL;</b>.';
$string['local_mbs_mebis_sites_default'] =
        'Startseite,https://mebis.bayern.de;'.
        'Infoportal,https://mebis.bayern.de/infoportal;'.
        'Mediathek,https://mediathek.mebis.bayern.de;'.
        'Lernplattform,https://lernplattform.mebis.bayern.de;'.        
        'Prüfungsarchiv,https://mediathek.mebis.bayern.de/archiv.php;';


// MoodleQuickForm Lizenzen.
$string['newlicense'] = 'New License ...';
$string['newlicense_add'] = 'Add new license';
$string['newlicense_shortname'] = 'New license short name';
$string['newlicense_fullname'] = 'New license full name';
$string['newlicense_fullnamerequired'] = 'A full name for new license "{$a}" is required';
$string['newlicense_source'] = 'New license source';
$string['newlicense_exists'] = 'License "{$a}" already exists - please specify a different short name';
$string['newlicense_required'] = 'A short name is required';
$string['newlicense_typecourse'] = 'Use as course license';

// MoodleQuickForm lookupset.
$string['lookupsetmoreresults'] = 'Please concretise your search, other results are avaiable.';
$string['lookupsetnoresults'] = 'To your input no results were found, please try it with other search words (at least three letters).';
$string['lookupsetlessletters'] = 'After input of at least three letters the available results are suggested to you.';
