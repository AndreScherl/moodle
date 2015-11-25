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

$string['complaintform'] = 'Problem melden';
$string['complaintformdetails_default'] = 'Zusätzliche Hinweise...';
$string['complaintformemail'] = 'Warum verlangen wir eine E-Mail-Adresse?';
$string['complaintformemail_default'] = 'Bitte tragen Sie Ihre E-Mail-Adresse ein.';
$string['complaintformemail_help'] = 'Wir verwenden Ihre E-Mail-Adresse für Nachfragen. '
        . 'Bei inhaltlichen Fehlern wird der Kursautor Sie eventuell kontaktieren.';
$string['complaintformerrortype'] = 'Fehlerart';
$string['complaintformerrortype_1'] = 'Urheberrechtsverstoß';
$string['complaintformerrortype_2'] = 'Verstoß gegen die Nutzungsordnung';
$string['complaintformerrortype_3'] = 'anderer Fehler';
$string['duplcourseforuse'] = 'Kurs für Nutzung kopieren';
$string['duplcourselicense'] = 'Dieser Kurs ist von {$a} erstellt worden und steht unter der Lizenz CC-NC-SA';
$string['emailcomplaint_body'] = 'The template course {$a->coursename} has been published by reviewer. It can be viewed at:
{$a->url}';
$string['emailcomplaint_subj'] = 'teachSHARE-Problembericht eingegangen';
$string['emailcomplaintsend_body'] = 'Liebes Support-Team,
    
    für den teachSHARE-Kurs {$a->coursename} wurde folgendes Problem gemeldet. 
    Von: {$a->from}
    Fehlertyp: {$a->error}
    Details: {$a->details}
    Link zu dem entsprechenden teachSHARE-Kurs {$a->url}

Vielen Dank und viele Grüße

Eure ISB-Programmierer';
$string['emailcomplaintsend_subj'] = 'Ihr Problembericht ist eingegangen.';
$string['emaildupldeployed_body'] = 'Sehr geehrte(r) Nutzer(in) der mebis-Lernplattform,
    
vielen Dank für Ihre Problemmeldung. Wir werden Ihren Problembericht so schnell wie möglich bearbeiten.
    
Mit freundlíchen Grüßen

Ihr Support-Team der mebis-Lernplattform
Andrea Taras / Beate Talwar

Akademie für Lehrerfortbildung und Personalführung
Kardinal-von-Waldburg-Str. 6-7
Hotline: 09071 - 53 300
mebis@alp.dillingen.de
www.mebis.bayern.de';
$string['emailtempldeployed_subj'] = 'Vielen Dank für Ihre Einreichung. Der Kurs wird geprüft.';
$string['errorcannotcomplain'] = 'Sie können zu diesem Kurs kein Problem melden.';
$string['sendcoursetemplateheading'] = 'Hiermit veröffentlichen Sie Ihren Kurs unter folgenden Lizenzbedingungen: cc / nicht-kommerziell / Weitergabe mit Namensnennung / Veränderbar). Bei der cc-Lizenz ist es notwendig, dass Ihr Name genannt wird. Hiermit stimmen Sie der Veröffentlichung Ihres Namens zu.';