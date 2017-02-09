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
 * MBS Report language string definition
 *
 * @package    report
 * @subpackage mbs
 * @copyright  ISB Bayern
 * @author     Andreas Wagner<andreas.wagern@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['courseid'] = 'Kurs-ID';
$string['coursename'] = 'Kursname';
$string['coordinators'] = 'Koordinator';
$string['pluginname'] = 'mebis Bericht und Pflege';
$string['reportpimped'] = 'Bericht gepimpte Kurse';
$string['reportpimpeddesc'] = 'Es wird im HTML Code von {$a} HTML-Blöcken nach den eingegebenen Suchmuster gesucht.';
$string['school'] = 'Schule';
$string['search'] = 'Suchen';
$string['searchpattern'] = 'Suchmuster';
$string['searchpatterndesc'] = 'Nach diesen Suchmustern wird in den HTML-Blöcken gesucht,
    mehrere Suchmuster können mit einer Pipe | getrennt werden.';
$string['searchpattern_help'] = 'Nach diesen Suchmustern wird in den HTML-Blöcken gesucht,
    mehrere Suchmuster können mit einer Pipe | getrennt werden.';
$string['trainer'] = 'Trainer';
$string['viewhtml'] = 'HTML Code anzeigen';

$string['id'] = 'ID';
$string['coursename'] = 'Course';
$string['filessize'] = 'Dateigröße';
$string['lastviewed'] = 'Zuletzt betreten';
$string['lastmodified'] = 'Zuletzt verändert';
$string['maxparticipantscount'] = 'Maximale Anzahl an Teilnehmern';
$string['maxmodulescount'] = 'Maximale Anzahl an Modulen';
$string['maxtrainerscount'] = 'Maximale Anzahl an Trainern';
$string['lastviewedbefore'] = 'Zuletzt betreten vor';
$string['lastmodifiedbefore'] = 'Zuletzt verändert vor';
$string['oneday'] = '1 Tag';
$string['onemonth'] = '1 Monat';
$string['oneweek'] = '1 Woche';
$string['halfyear'] = 'Halbes Jahr';
$string['oneyear'] = '1 Jahr';

$string['trainer'] = 'Trainer';
$string['trainerscount'] = 'Anzahl Trainer';
$string['participantscount'] = 'Anzahl Teilnehmer';
$string['modulescount'] = 'Anzahl Module';
$string['categoryname'] = 'Kursbereich';

$string['reportcourseperpage'] = 'Seitenaufteilung im Kursreport';
$string['reportcourseperpagedesc'] = 'Maximale Einträge pro Seite';
$string['reportcourses'] = 'Kurs Report';
$string['reportorphaned'] = 'Kurs Report';
$string['texcronactiv'] = 'Cronjob für TeX Ersetzung aktviv';
$string['texcronactivdesc'] = 'Der Cronjob kann hier deaktiviert werden';
$string['reportcoursesynccount'] = 'Anzahl der Kurse, die per cronjob ausgewertet werden.';
$string['reportcoursesynccountdesc'] = 'Aufgrund der hohen Kursanzahl werden die Statistikdaten für Kurse über einen Cron Job ermittelt. Wählen sie hier, wie viele Kurse pro Cron Job ausgewertet werden.';
$string['reportcoursestats'] = 'Kurs Statistik';
$string['coursestatscronactiv'] = 'Hintergrundprozess für Kurs Statistik aktiv';
$string['coursestatscronactivdesc'] = 'Schalten sie den Hintergrundprzess ein oder aus.';
$string['coursesstatscomplete'] = 'Die gesammelten Daten sind aktuell.';
$string['coursesstatsincomplete'] = 'Die Statistikdaten sind veraltet: {$a->counttodelete} zu löschen, {$a->counttoadd} item(s) müssen hinzugefügt werden.';
$string['status'] = 'Älteste Statistikdaten';

$string['bulkaction'] = 'Massenaktion';
$string['bulkaction_delete'] = 'Löschen';
$string['bulkaction_move'] = 'In Kursbereich verschieden';
$string['bulkaction_unenrol'] = 'Einschreibungen löschen';
$string['bulkactiondeleteinfo'] = 'Folgende Kurse werden gelöscht. Dies kann nicht rückgängig gemacht werden!';
$string['bulkactionmoveeinfo'] = 'Folgende Kurse werden in den gewählten Kursbereich verschoben';
$string['bulkactionunenrolinfo'] = 'Alle Teilnehmer werden aus den folgenden Kurses ausgetragen.';
$string['bulkactionrequired'] = 'Bitte wählen sie eine Aktion.';
$string['coursesdeleted'] = 'Kurse wurden gelöscht';
$string['coursesmoved'] = 'Kurse wurden verschoben';
$string['courseidsmissing'] = 'Fehlende Kurs-Ids';
$string['coursesmissing'] = 'Fehlende Kurse';
$string['coursesunenrolled'] = 'Die Teilnehmer wurden aus den Kursen entfernt.';
$string['doaction'] = 'Aktion ausführen';
$string['limitcategories'] = 'Suchergebnisse limitieren';
$string['limitcategoriesdesc'] = 'Bestimmen sie die maximale Anzahl in der Kursbereichssuche.';
$string['depthcategories'] = 'Pfad verkürzen';
$string['depthcategoriesdesc'] = 'Pfade zu Kursbereichen, deren Tiefe länger als dieser Wert sind, werden verkürzt dargestellt.';
$string['nocategoryselected'] = 'Kein Kursbereich gewählt';
$string['selectcategory'] = 'Wählen sie einen Kursbereich';
$string['selectonecourse'] = 'Bitte wählen sie mindestens einen Kurs';
$string['unknownaction'] = 'Unbekannte Aktion';

$string['numberofcourses'] = 'Anzahl gefundener Kurse: {$a}';
$string['draftfilescronactiv'] = 'Löschen von nicht aufgeräumten "draft"-Files';
