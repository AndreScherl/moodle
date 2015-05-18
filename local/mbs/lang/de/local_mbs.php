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
 * de - language file for local_mbs
 *
 * @package    local_mbs
 * @copyright  Andreas Wagner, ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['pluginname'] = 'Mebis - Anpassungen';
$string['schoolnode'] = 'Meine Schulen';

//Datenschutz
$string['mbs:institutionview'] = 'User anderer Institutionen sehen';
$string['invalidredirect'] = 'Diese Weiterleitung ist nicht gestattet.';

$string['notallowedtoaccessuserreport'] = 'Der Zugriff auf den Userreport ist nicht gestattet';
$string['notallowedtoaccessrecentactivities'] = 'Der Zugriff auf Neues im Kurs ist nicht gestattet.';

$string['nopermissiontoedituser'] = 'Sie dürfen das Profil dieses Users nicht bearbeiten.'.
'<p>Nur User von der gleichen Schule dürfen ein Profil bearbeiten.</p>';

$string['noinstitutionerror'] = 'Sie sind keiner Institution (z. B. einer Schule) zugewiesen und können diese Aktion deshalb nicht durchführen.'.
'Bitte wenden Sie sich an den Support, um die Zuordnung zu einer Institution vornehmenzulassen';

$string['nopermissiontoviewuser'] = 'Sie dürfen diesen User nicht sehen.'.
'<h1>Sichtbarkeitsregelung von Usern</h1>'.
'<p>Normale User sehen auf der Plattform nur User der gleichen Schule oder User mit denen sie gemeinsam an einem Kurs teilnehmen.</p>';   

//Sidebar Navigation
$string['local_mbs_mebis_sites'] = 'Navigationslinks';
$string['local_mbs_mebis_sites_expl'] = 'Diese Navigationselemente erscheinen in der sidebar-Navigation.'.
        'Die Element werden zeilenweise in der Form <b>Link,URL;</b> angegeben.';
$string['local_mbs_mebis_sites_default'] =
        'Startseite,https://mebis.bayern.de;'.
        'Infoportal,https://mebis.bayern.de/infoportal;'.
        'Mediathek,https://mediathek.mebis.bayern.de;'.
        'Lernplattform,https://lernplattform.mebis.bayern.de;'.        
        'Prüfungsarchiv,https://mediathek.mebis.bayern.de/archiv.php;';