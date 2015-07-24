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
 * language file for Block mbschangetheme
 *
 * @package    block_mbschangetheme
 * @copyright  Andreas Wagner <andreas.wagner@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['changeallowusertheme'] = 'Einstellung allowusertheme';
$string['changetotheme1'] = 'Neues Design wählen';
$string['changetotheme2'] = 'Altes Design wählen';
$string['displayname'] = 'Designwechsel';
$string['mbschangetheme:addinstance'] = 'Hinzufügen des Blocks mebis Themewechsel';
$string['mbschangetheme:myaddinstance'] = 'Hinzufügen des Blocks mebis Themewechsel zu meinem Schreibtisch';
$string['newalertheading'] = 'Designwechsel möglich!';
$string['newalertexpl'] = 'Vorübergehend ist es möglich noch mit dem alten Design zu arbeiten. Auf Ihrer persönlichen Startseite '
        . 'finden Sie unter dem Bereich "Meine Apps" eine Schaltfläche zum Wechseln auf das alte Design von mebis. Mit der gleichen '
        . 'Schaltfläche kommen Sie auch wieder auf das neue Design zurück.';
$string['newalerthideme'] = 'Diesen Hinweis nicht mehr anzeigen.';
$string['newalertclose'] = 'Schließen';
$string['notconfiguredproperly'] = 'Das Plugin ist nicht richtig konfiguriert!';
$string['pluginname'] = 'mebis Themewechsel';
$string['requireallowusertheme'] = 'Um einen Themewechsel zu ermöglichen, muss die Option allowuserthemes in der Website-Administration erlaubt werden ({$a}).';
$string['theme1'] = 'Theme 1 (Mebis)';
$string['theme1desc'] = 'Sie können insgesamt zwei Themeeinstellungen vornehmen, zwischen diesen Themes wird gewechselt.<br />
    <b>Hinweis</b>: Für folgende Bereiche können Themes ausgewählt sein: gesamte Webseite, Kategorieseiten Kursseiten.<br />< br/>
    Damit Einstellung für den User wirksam wird, muss die Konfiguration allowuserthemes aktiviert sein ({$a}) und in der config.php <br />
    $CFG->themeorder = array("user", "page", "course", "category", "session", site"); <br />eingetragen werden, um die Priorität der Themes zu ändern.
    (Details siehe <a href="https://docs.moodle.org/22/en/Theme_settings">Moodle theme settings</a>)';
    
$string['theme2'] = 'Theme 2 (DLB)';
$string['theme2desc'] = '';
$string['unknowntheme'] = 'Unbekanntes Theme';