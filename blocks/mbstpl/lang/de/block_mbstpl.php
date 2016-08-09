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
 * german language strings
 *
 * @package     block_mbstpl
 * @copyright   2015 ISB
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addfrombank'] = 'Fügen Sie eine Frage aus der Fragensammlung ein';
$string['addqtodraft'] = 'Frage verwenden';
$string['addquestion'] = 'Neue Frage hinzufügen';
$string['ajaxurl'] = 'Ajax-URL für die Daten';
$string['archive'] = 'Archiv';
$string['assigned'] = 'Zugewiesen';
$string['assigneddate'] = 'Datum';
$string['assignee'] = 'Empfänger';
$string['assignauthor'] = 'Autor einschreiben';
$string['assignreviewer'] = 'Reviewer einschreiben';
$string['author'] = 'Autor';
$string['authorrole'] = 'Autoren Rolle';
$string['authorrole_desc'] = 'Rolle für den im Kurs einzuschreibenden Autor';
$string['backtemplatefeedback'] = 'Zurück zum Feedback für diesen Kurs';
$string['checklistexpln'] = 'Anm.: Fragen dieser Art werden am Ende des Formulars als Checkliste für die Inhalte dieses Kurses angezeigt<br>
Jeder Inhalt kann als \'Ja\', \'Nein\', oder \'Nicht anwendbar\' markiert werden.<br>
Es kann keine Kurssuche ausgeführt werden, die auf diesen Feldern basiert.';
$string['complaintemail'] = 'Emailadresse für Beschwerden';
$string['complaintemail_desc'] = 'E-Mail-Adresse für gemeldete Probleme bei Austauschkursen.';
$string['complaintform'] = 'Problem melden';
$string['complaintformdetails_default'] = 'Zusätzliche Hinweise...';
$string['complaintformemail'] = 'Warum verlangen wir eine E-Mail-Adresse?';
$string['complaintformemail_default'] = 'Bitte tragen Sie Ihre E-Mail-Adresse ein.';
$string['complaintformemail_help'] = 'Wir verwenden Ihre E-Mail-Adresse für Nachfragen. '
        . 'Bei inhaltlichen Fehlern wird der Kursautor Sie eventuell kontaktieren.';
$string['complaintformerrortype'] = 'Fehlerart';
$string['complaintformerrortype_1'] = 'Urheberrechtsverstoß';
$string['complaintformerrortype_2'] = 'Verstoß gegen die Nutzungsordnung';
$string['complaintformerrortype_3'] = 'Anderer Fehler';
$string['complainturl'] = 'URL für Beschwerden';
$string['complainturl_desc'] = 'Externe URL für Beschwerden bezüglich Kursen. Die Kurs-ID wird angehängt.';
$string['confirmdelquest'] = 'Diese Frage ist in Gebrauch. Sie kann aus diesem Entwurf gelöscht werden, verbleibt aber noch in der Fragensammlung. Wollen Sie die Frage löschen?';
$string['confirmdelquestforever'] = 'Diese Frage wird beim Löschen vollständig aus der Fragensammlung gelöscht. Wollen Sie die Frage löschen?';
$string['coursekeyword'] = 'Kurspasswort';
$string['courselicense'] = 'Kurslizenz';
$string['coursename'] = 'Kursname';
$string['coursemetadata'] = 'Kurs-Metadaten';
$string['coursesfromtemplate'] = 'Nach dieser Vorlage erstellte Kurse';
$string['coursetemplates'] = 'Austauschkurse';
$string['creator'] = 'Kursautor';
$string['createdby'] = 'Erstellt durch';
$string['createdon'] = 'Erstellt am';
$string['creationdate'] = 'Erstellungsdatum';
$string['currentrating'] = 'Aktuelle Bewertung';
$string['datasource'] = 'Datenquelle für zugrundeliegende Werte';
$string['datasource_help'] = 'Wenn die gespeicherten Werte durch IDS repräsentiert werden, kann es sein, dass für die Anzeige
    andere Werte verwendet werden sollen. Dafür muss man hier die Datenbanktabelle, die Spalte der IDs und die Spalte für die
    anzuzeigenden Werte angeben.
    Beispiel: block_mbstpl_subjects,id,subject
    Für die Anzeige wird die ID (=id) durch den Namen des Fachs (=subject) ersetzt.';
$string['description'] = 'Beschreibung';
$string['delayedrestore'] = 'Austauschkurs Erzeugung verzögern';
$string['delayedrestore_desc'] = 'Kopieren der Kurse über CRON planen anstatt sofort ausführen.';
$string['deploycat'] = 'teachSHARE Kursbereich';
$string['deployuserinfo'] = 'Nutzerdaten dürfen in Kopien des Austauschkurses verwendet werden.';
$string['destination'] = 'Ziel';
$string['duplcourseforuse'] = 'Kurs für Nutzung kopieren';
$string['duplcourseforuse1'] = 'Abschnitte und Aktivitäten zur Duplizierung auswählen';
$string['duplcourseforuse2'] = 'Kurs aus dieser Vorlage erstellen';
$string['duplcourselicensedefault'] = 'Die von {$a->creator} erstellten Inhalte dieses Kurses stehen unter der Lizenz {$a->licence}.';
$string['duplcourselicense'] = 'Lizenzinformation für die Eigeninhalte des Kurses';
$string['editmeta'] = 'Kursinformationen editieren';
$string['emailassignedreviewer_body'] = 'Sehr geehrter Kurs-Reviewer,'."\n".'Sie wurden ausgewählt, diesen Kurs zu überprüfen: {$a->fullname}. Sie können den Kurs nun unter folgender Adresse überprüfen: {$a->url} .';
$string['emailassignedreviewer_subj'] = 'Sie wurden als Reviewer zugeteilt.';
$string['emailassignedauthor_body'] = 'Sehr geehrte(r) Nutzer(in) der mebis-Lernplattform,
sie wurden im Kurs {$a->fullname} als Kursautor eingeschrieben. Sie können den Kurs unter folgender Adresse einsehen: {$a->url} .';
$string['emailassignedauthor_subj'] = 'Sie wurden als Kursautor eingeschrieben.';
$string['emailcoursepublished_body'] = 'Sehr geehrte(r) Kursautor(in),'."\n"
        . 'vielen Dank für die Einreichung Ihres Kurses {$a->coursename} bei teachSHARE. Ihr Kurs wurde stichprobenartig unter folgenden Aspekten geprüft:
             • Fremdmaterial und Eigenmaterial (v. a. Videos, Musik und Bilder) ist mit vollständiger Quellenangabe versehen.
             • Die Lizenz der veränderten Fremdinhalte erlaubt ggf. die Bearbeitung bzw. Veränderung dieser Materialien.
             • Etwaige personenbezogene Daten (z. B. Forumsbeiträge) lassen keinen Rückschluss auf einzelne Schüler zu.
            Bei der Durchsicht sind keine Unstimmigkeiten aufgefallen. Bitte beachten Sie, dass die Verantwortung für die Kursinhalte '
        .'trotz dieses Prozesses immer beim einreichenden Autor bleibt. Der Kurs wurde veröffentlicht und kann unter folgender Adresse abgerufen werden:'
        ."\n".'{$a->url}'."\n"
        .'Sollten Sie dennoch Änderungen vornehmen wollen, lesen Sie folgendes Tutorial: '."\n"
        .'https://www.mebis.bayern.de/infoportal/fortbildung/tutorials/weitere/teachshare/austauschkurs-ueberarbeiten/';
$string['emailcoursepublished_subj'] = 'Kurs veröffentlicht';
$string['emailcomplaint_body'] = 'Liebes Support-Team,

    für den teachSHARE-Kurs {$a->coursename} wurde folgendes Problem gemeldet.
    Von: {$a->from}
    Fehlertyp: {$a->error}
    Details: {$a->details}
    Link zum entsprechenden teachSHARE-Kurs: {$a->url}
    Link zum Überarbeiten nötig Formular: {$a->revision}

Vielen Dank und viele Grüße
Eure ISB-Programmierer';
$string['emailcomplaint_subj'] = 'teachSHARE-Problembericht eingegangen';
$string['emailcomplaintsend_body'] = 'Sehr geehrte(r) Nutzer(in) der mebis-Lernplattform,

vielen Dank für Ihre Problemmeldung zum teachSHARE-Kurs.
Wir werden Ihren Problembericht so schnell wie möglich bearbeiten.

Mit freundlichen Grüßen
Ihr Support-Team der mebis-Lernplattform

Akademie für Lehrerfortbildung und Personalführung
Kardinal-von-Waldburg-Str. 6-7
Hotline: 09071 - 53 300
mebis@alp.dillingen.de
www.mebis.bayern.de';
$string['emailcomplaintsend_subj'] = 'Ihr Problembericht ist eingegangen.';
$string['emaildupldeployed_body'] = 'Sehr geehrte(r) Nutzer(in) der mebis-Lernplattform,
der Austauschkurs {$a->fullname} wurde für Sie kopiert. Er kann unter folgender Adresse abgerufen werden:'."\n".'{$a->url}';
$string['emaildupldeployed_subj'] = 'Austauschkurs kopiert';
$string['emailfeedbackauth_body'] = 'Sehr geehrter Kurs-Reviewer,'."\n".'der Autor des Kurses {$a->fullname}, {$a->reviewer},
    hat eine Berichtigung des Kurses vorgenommen. Folgende Rückmeldung wurde hinzugefügt:'."\n".'{$a->feedback}'."\n\n".
    'Sie können den Kurs erneut unter folgender Adresse überprüfen: {$a->url} .';
$string['emailfeedbackauth_subj'] = 'Kurs-Feedback';
$string['emailfeedbackrev_body'] = 'Sehr geehrter Kursautor,'."\n".'für den Austauschkurs {$a->fullname} wurde von {$a->reviewer}
    folgende Rückmeldung hinzugefügt:'."\n".'{$a->feedback}'."\n".'Link zum Kurs: {$a->url} .';
$string['emailfeedbackrev_subj'] = 'Kurs überprüft';
$string['emailreadyforreview_body'] = 'Sehr geehrter Master Reviewer,'."\n".'Der Kurs {$a->fullname} steht unter folgender Adresse für die Review bereit: {$a->url} .';
$string['emailreadyforreview_subj'] = 'Kurs bereit für Review';
$string['emailstatsrep_body'] = 'Im Anhang finden Sie den Statistikbericht für Austauschkurse.';
$string['emailstatsrep_subj'] = 'Statistikbericht für Austauschkurse.';
$string['emailtempldeployed_body'] = 'Sehr geehrte(r) Nutzer(in) der mebis-Lernplattform,'."\n".'vielen Dank für Ihre Einreichung.
    Sie erhalten eine Nachricht wenn Ihr Kurs veröffentlicht wurde.';
$string['emailtempldeployed_subj'] = 'Kurs eingereicht';
$string['errorcannotassignauthor'] = 'Sie können keine Autoren für diesen Kurs zuweisen.';
$string['errorcannotassignreviewer'] = 'Sie können keinen Reviewer für diesen Kurs zuweisen.';
$string['errorcannotcomplain'] = 'Sie können zu diesem Kurs kein Problem melden.';
$string['errorcannotdupcrs'] = 'Sie können diesen Kurz nicht zur Nutzung kopieren.';
$string['errorcannoteditmeta'] = 'Sie können die Kursinformationen nicht editieren.';
$string['errorcannotmovefile'] = 'Die Kurssicherung konnte nicht an den Wiederherstellungsort verschoben werden.';
$string['errorcannotsearch'] = 'Sie haben nicht die Berechtigung Austauschkurse zu suchen.';
$string['errorcannotsendforrevision'] = 'Sie können diesen Kurs nicht zur Überarbeitung bereitstellen.';
$string['errorcannotviewabout'] = 'Sie können weitere Informationen zu diesem Kurs nicht einsehen.';
$string['errorcannotviewfeedback'] = 'Sie können das Feedback für diesen Kurs nicht sehen.';
$string['errorcatnotexists'] = 'Kategorie, in die wiederhergestellt werden soll, existiert nicht.';
$string['errorcoursenottemplate'] = 'Dieser Kurs ist kein Austauschkurs.';
$string['errordeploying'] = 'Fehler beim Bereitstellen des Austauschkurses.';
$string['erroremailbody'] = 'Ein Fehler ist aufgetreten: {$a->message}'."\n".'{$a->errorstr}';
$string['erroremailsubj'] = 'Fehler bei einem Austauschkurs';
$string['errormanualenrolnotset'] = 'Manuelle Einschreibung wurde für diesen Kurs nicht aktiviert.';
$string['errornowheretorestore'] = 'Es gibt keine Kategorien oder Kurse, in denen Sie die Erlaubnis haben, einen Kurs wiederherzustellen.';
$string['errorrestorefilenotexists'] = 'Kurssicherung existiert nicht.';
$string['errorincorrectdatatype'] = 'Falscher Datentyp angegeben.';
$string['errornobackupfound'] = 'Keine Sicherung gefunden für Sicherung {$a}';
$string['errornoassignableusers'] = 'Keine Nutzer gefunden, die zugewiesen werden können.';
$string['errornotallwoedtosendfeedback'] = 'Nutzer darf kein Feedback senden (weder Autor noch Reviewer).';
$string['errorreviewerrolenotset'] = 'Rolle Reviewer nicht gesetzt. Muss in den Plugin-Einstellungen gesetzt werden.';
$string['exceptiondeletingusedlicense'] = 'Lizenzformen, die in Gebrauch sind, können nicht gelöscht werden';
$string['errorteacherrolenotset'] = 'Rolle Lehrer nicht gesetzt. Muss in den Plugin-Einstellungen gesetzt werden.';
$string['feedback'] = 'Nachricht';
$string['feedbackfiles'] = 'Anhang';
$string['feedbackfor'] = 'Feedback für {$a}';
$string['field_checklist'] = 'Checklist';
$string['field_checkboxgroup'] = 'Checkboxgroup';
$string['field_lookupset'] = 'Lookupset';
$string['forrevision'] = 'Überarbeitung nötig';
$string['history'] = 'Verlauf';
$string['incluserdata'] = 'Nutzerdaten veröffentlichen';
$string['incorrectfieldname'] = 'Eingabe eines falschen Feldnamens.';
$string['initialform'] = 'Entwurf';
$string['lastupdate'] = 'Letzte Aktualisierung';
$string['layout'] = 'Layout';
$string['layoutgrid'] = 'Kacheln';
$string['layoutlist'] = 'Liste';
$string['leastoneoption'] = 'Bitte wählen Sie mindestens eine Option aus.';
$string['legalinfo'] = 'Rechtliche Information';
$string['license'] = 'Veröffentlichung der Eigeninhalte unter der Lizenz';
$string['license_help'] = '<ul><li><a href="https://creativecommons.org/licenses/by/3.0/de/" target="_blank">BY - Namensnennung</a></li>'
        . '<li><a href="https://creativecommons.org/licenses/by-sa/3.0/de/" target="_blank">SA - Weitergabe unter gleichen Bedingungen</a></li>'
        . '<li><a href="https://creativecommons.org/licenses/by-nc/3.0/de/" target="_blank">NC - Nicht kommerziell</a></li>'
        . '<li><a href="https://creativecommons.org/licenses/by-nd/3.0/de/" target="_blank">ND - Keine Bearbeitung</a></li></ul>';
$string['licenses_header'] = 'Verfügbare Lizenzen';
$string['licenses_edit'] = 'Lizenzen verwalten';
$string['license_fullname'] = 'Name';
$string['license_shortname'] = 'Kurzname';
$string['license_source'] = 'Link';
$string['license_used'] = 'Verwendet';
$string['loadmoreresults'] = 'Weitere Ergebnisse laden...';
$string['managesearch'] = 'Suche verwalten';
$string['manageqforms'] = 'Fragen für die Kursinformationen verwalten';
$string['mbstpl:abouttemplate'] = 'Über diesen Kurs';
$string['mbstpl:addinstance'] = 'teachSHARE Block hinzufügen';
$string['mbstpl:assignauthor'] = 'Autor einem Kurs zuweisen';
$string['mbstpl:coursetemplateeditmeta'] = 'Kursinformationen editieren';
$string['mbstpl:coursetemplatereview'] = 'Kurs überprüfen';
$string['mbstpl:coursetemplatemanager'] = 'Master Reviewer';
$string['mbstpl:createcoursefromtemplate'] = 'Austauschkurs kopieren';
$string['mbstpl:notanonymised'] = 'Im Austauschkurs nicht anonymisiert';
$string['mbstpl:myaddinstance'] = 'teachSHARE Block zu meiner Startseite hinzufügen';
$string['mbstpl:ratetemplate'] = 'Bewertung';
$string['mbstpl:sendcoursetemplate'] = 'Kurs veröffentlichen';
$string['mbstpl:viewcoursetemplatebackups'] = 'Kurs-Sicherungen anschauen';
$string['mbstpl:viewhistory'] = 'Kurschronik';
$string['mbstpl:viewrating'] = 'Kursbewertungen';
$string['message'] = 'Nachricht';
$string['messageprovider:assignedauthor'] = 'teachSHARE: Autor zugewiesen';
$string['messageprovider:assignedreviewer'] = 'teachSHARE: Reviewer zugewiesen';
$string['messageprovider:complaint'] = 'teachSHARE: Problem melden';
$string['messageprovider:deployed'] = 'teachSHARE: Austauschkurs erstellt';
$string['messageprovider:duplicated'] = 'teachSHARE: Austauschkurs importiert (dubliziert)';
$string['messageprovider:error'] = 'teachSHARE: Fehler';
$string['messageprovider:feedback'] = 'teachSHARE: Feedback';
$string['messageprovider:forrevision'] = 'teachSHARE: Zurückgewiesen zur Überarbeitung';
$string['messageprovider:published'] = 'teachSHARE: Veröffentlicht';
$string['messageprovider:stats'] = 'teachSHARE: Statistik';
$string['messageprovider:reminder'] = 'teachSHARE: Erinnerungen';
$string['mustselectuser'] = 'Sie müssen einen Nutzer auswählen';
$string['myassigned'] = 'Kurse in Bearbeitung';
$string['mypublished'] = 'Meine veröffentlichten Kurse';
$string['myreview'] = 'Kurse im Reviewingprozess';
$string['myreview_help'] = '<B>fett</B>: Der Kurs ist mir zugewiesen, damit ich ihn überarbeite oder überprüfe. Je nachdem, ob ich Autor oder Reviewer bin.<br />'
        . '<span style="background-color:#ff6600;color:#fff;"> orange </span>: Der Kurs ist dem Autor zugewiesen, also der Person, die ihn in teachSHARE hochgeladen hat.<br />'
        . '<span style="background-color:#00a8d5;color:#fff;"> blau </span>: Der Kurs ist dem Reviewer zugewiesen, damit dieser ihn überprüft. D.h. der Autor kann im Moment keine Änderungen vornehmen.';
$string['myrevision'] = 'Meine Kurse in Bearbeitung';
$string['na'] = 'irrelevant';
$string['newblocktitle'] = 'teachSHARE';
$string['newlicense'] = 'Neue Lizenz hinzufügen';
$string['nextstatsreport'] = 'Neuer Statistikbericht';
$string['nextstatsreport_desc'] = 'Datum zur Ausführung des nächsten Statistikberichts';
$string['noactiontpls_body'] = 'Folgende(r) Kurs(e) wurde(n) während der letzten Bearbeitungsperiode nicht geändert:'."\n".'{$a}';
$string['noactiontpls_subj'] = 'Ungeprüfte Vorlagen';
$string['noresults'] = 'Keine Suchergebnisse. Bitte verändern Sie Ihre Sucheinstellungen.';
$string['nountouchedtemplates'] = 'Es sind keine ungeprüften Vorlagen im gesetzten Zeitraum vorhanden.';
$string['pluginname'] = 'teachSHARE';
$string['pluginnamecategory'] = 'teachSHARE (mehr)';
$string['qformactivate'] = 'Diesen Entwurf aktivieren';
$string['qbank'] = 'Fragensammlung';
$string['qformdiscard'] = 'Frageformular verwerfen';
$string['qformunsaved'] = 'Neues Frageformular (nicht gespeicherter Entwurf)';
$string['questionhelp'] = 'Hilfetext für Fragen';
$string['questionhelppopupheading'] = 'Hilfe: {$a}';
$string['questionname'] = 'Fragebezeichnung';
$string['questionrequired'] = 'Pflichtfeld';
$string['questiontitle'] = 'Fragetitel';
$string['questiontype'] = 'Fragetyp';
$string['reasonforrevision'] = 'Gründe für die Überarbeitung';
$string['removefromdraft'] = 'Aus Entwurf entfernen';
$string['reviewerrole'] = 'Reviewer-Rolle';
$string['reviewerrole_desc'] = 'Rolle für den im Kurs einzuschreibenden Reviewer';
$string['save'] = 'Speichern';
$string['scheduledreporting'] = 'Geplanter Bericht';
$string['selectedauthor'] = 'Ausgewählter Autor';
$string['selectedreviewer'] = 'Ausgewählter Reviewer';
$string['selecteduser'] = 'Ausgewählter Nutzer';
$string['selectsectionsandactivities'] = 'Wählen Sie aus, welche Kursabschnitte, Aktivitäten und Nutzerdaten Sie in die Kurskopie mit einbeziehen möchten.';
$string['selectuser'] = 'Nutzer auswählen';
$string['sendcoursetemplate'] = 'Kurs veröffentlichen';
$string['sendforreviewing'] = 'Absenden';
$string['sendtpldate'] = 'Absendedatum';
$string['sendfeedbacktoauthor'] = 'Nachricht an Autor';
$string['sendfeedbacktoreviewer'] = 'Nachricht an Reviewer';
$string['sentforreview'] = 'Vielen Dank für die Einreichung Ihres Kurses bei teachSHARE.';
$string['searchpagesize'] = 'Seitenaufteilung in der teachSHARE Suche';
$string['searchresult'] = 'Suchergebnis';
$string['startsreportsent'] = 'Statistikbericht erfolgreich verschickt.';
$string['statsreporttooearly'] = 'Zu früh für nächsten Statistikbericht. Geplant für {$a}.';
$string['statusarchived'] = 'Archiviert';
$string['statusassignedreviewer'] = 'Reviewer zugewiesen';
$string['statuscreated'] = 'Erstellt';
$string['statuspublished'] = 'Veröffentlicht';
$string['statusunderreview'] = 'Bearbeitung durch Reviewer';
$string['statusunderrevision'] = 'Bearbeitung durch Autor';
$string['tag'] = 'Schlagwort';
$string['tags'] = 'Schlagworte';
$string['tagshelpbutton'] = 'Hilfe: Schlagworte';
$string['tagshelpbutton_help'] = 'Geben Sie Schlagworte an, die den Inhalt oder Einsatz Ihres Kurses beschreiben, z. B. für einen SCHILF-Kurs: schilf, fortbildung';
$string['tagsplaceholder'] = 'schlagwort1, schlagwort2';
$string['tasknote'] = 'Auftragsnotiz';
$string['teacherrole'] = 'Lehrer-Rolle';
$string['teacherrole_desc'] = 'Rolle, die einem Nutzer zugewiesen wird, wenn er einen Austauschkurs für die Nutzung kopiert.';
$string['termsofuse'] = 'Nutzungsbedingungen';
$string['termsofuse_descr'] = 'Ich habe die <a href="https://www.mebis.bayern.de/nutzungsbedingungenteachshare/">Nutzungsbedingungen</a> gelesen und akzeptiere sie.';
$string['templatefeedback'] = 'Rückmeldeformular zum Austauschkurs';
$string['templatehistoryreport'] = 'Verlauf für den Austauschkurs "{$a->fullname}" ({$a->shortname})';
$string['templatesearch'] = 'Tauschkurse suchen';
$string['timeassigned'] = 'Zugewiesen seit';
$string['to'] = 'An:';
$string['tplremindafter'] = 'Erinnerung bezüglich Austauschkurs senden nach';
$string['tplremindafter_desc'] = 'Jeder Nutzer, der das Recht Master Reviewer im teachSHARE Kursbereich-Kontext hat,
    wird benachrichtigt, wenn ein Kurs nicht in der vorgegebenen Zeit bearbeitet wird.';
$string['tplremindersent'] = 'Erinnerung abgeschickt.';
$string['updated'] = 'Zeit';
$string['uploadfile'] = 'Datei hochladen';
$string['useq'] = 'Diese Frage nutzen';
$string['viewfeedback'] = 'Feedback anschauen';
$string['withanon'] = 'Mit anonymisierten Nutzerdaten';
$string['withoutanon'] = 'Ohne Nutzerdaten';
$string['rating'] = 'Bewertung';
$string['ratingavg'] = 'durchschnittliche Bewertung';
$string['rating_header'] = 'Bewertung abgeben';
$string['submitbutton'] = 'Senden';
$string['cancelbutton'] = 'Abbrechen';
$string['rating_star'] = '{$a} Stern';
$string['redirectdupcrsmsg'] = 'Sehr geehrte(r) Nutzer(in) der mebis-Lernplattform,
ihr Antrag für eine Kurskopie ist eingegangen. Sie erhalten eine Nachricht, sobald die Aktion ausgeführt wurde.';
$string['viewhistory'] = 'Chronik anschauen';
$string['viewrating'] = 'Bewertung anschauen';
$string['redirectdupcrsmsg_done'] = 'Der Austauschkurs wurde kopiert. Sie werden zum kopierten Kurs weitergeleitet.';
$string['yourrating'] = 'Meine Bewertung';

// New strings to be sorted in alphabetically, after translation.
$string['addenrolmbs'] = 'Rücksetzungs-plugin hinzufügen';
$string['advancedsearch'] = 'Erweitere Suche';
$string['backupcreated'] = 'Backup erstellt';
$string['backupinformation'] = 'Informationen zum Backup';
$string['configreset'] = 'Rücksetzung konfigurieren';
$string['courseresetstrategy'] = 'Kursrücksetzung';
$string['courseresetstrategydescription'] = 'Kursrücksetzung';
$string['coursebackupwithuserdata'] = '<p>Dieses Kurs-Template enthielt bereits beim erstmaligen Einsenden Userdaten, die anonymisiert wurden.
    Wenn beim Ausprobieren des Kurses auf teachSHARE neue Userdaten erzeugt werden, wird der Kurs durch eine Wiederherstellung des letzten verfügbaren
    Backupfiles zurückgesetzt. Alle verfügbaren Backupfiles enthalten im Dateinamen die Kurs ID und sind nach folgendem Schema bezeichnet: pubbkp_[ID des Kurses].mbz.
    Bei der Zurücksetzung des Kurses werden alle Teilnehmer aus dem Kurs entfernt.';
$string['coursebackupnopubbackup'] = '<p><b>Es is kein (published) backup file verfügbar?</b></p><p>Sie können ein Backupfile erzeugen, das künftig zur
    Zurücksetzung eines Kurses verwendet wird:
    <ul>
    <li>Setzen sie den Kurs auf nicht sichtbar</li>
    <li>Entfernen sie alle Daten, die bei der Kurszurücksetzung nicht wiederhergestellt werden sollen</li>
    <li>Starten sie das Backup: der Kurs wird sofort in die Datei pubbkp_[ID des Kurses].mbz gesichert</li>
    <li>Schalten sie den Kurs wieder sichtbar</li>
    </ul>';
$string['courseresetwithuserdata'] = '<p>
    Normalerweise wird die Zurücksetzung des Kurses durch einen Hintergrundprozesses automatisch durchgeführt,
    hier können sie eine Kurszurückstzung aber auch sofort manuell auslösen:</p>';
$string['courseresetnouserdata'] = '<p>Dieses Kurstamplate enthielt beim Einsenden <b>keine Userdaten</b>.
    Die Zurücksetzung des Kurses erfolgt mit der Standardzurücksetzung von Moodle, siehe: {$a}</p>';
$string['course'] = 'Kurs Template';
$string['courserestored'] = 'Kurs erfolgreich zurückgesetzt';
$string['containsuserdata'] = 'Kurs enthielt beim Einsenden Userdaten';
$string['contentchanged'] = 'Inhalte des Kurses wurden verändert';
$string['createanewbackupfile'] = 'Ein Backup für künftige Zurücksetzungen des Kurses erstellen';
$string['createpublishedbackup'] = 'Ein Backup für künftige Zurücksetzungen des Kurses erstellen';
$string['creator'] = 'Ersteller des Backups';
$string['deletebackupfile'] = 'Backup löschen';
$string['deletebackupfiledesc'] = 'Möchten sie diese Datei wirklich löschen: {$a}?';
$string['dothecoursereset'] = 'Kurs zurücksetzen';
$string['errorbackinguptemplate'] = 'Beim Backup des Templates (ID: {$a}) ist ein Fehler aufgetreten';
$string['errordeploypublish'] = 'Beim Veröffentlichen des Templates {$a} ist ein Fehler aufgetreten';
$string['filedeleted'] = 'Datei gelöscht';
$string['id'] = 'ID';
$string['implementedownmodules'] = 'Module, die durch Methoden des Plugins geprüft werden';
$string['lastresettime'] = 'Zeitpunkt des letzten Resets';
$string['missingpermisson'] = 'Kurs template (ID: {$a}) kann nicht veröffentlicht werden, da Rechte fehlen';
$string['nextruntime'] = 'Nächste Ausführung';
$string['nextruntimefrom'] = 'Nächste Ausführung von';
$string['nextruntimeto'] = 'bis';
$string['notaskavailable'] = 'Kein Vorgang';
$string['notavailable'] = 'Nicht verfügbar';
$string['nofiletorestoretemplate'] = 'Es ist keine Backupdatei für eine Zurücksetzen des Kurses verfügbar (id: {$a})';
$string['pubbackupfile'] = 'Kurs Template Backup';
$string['publishedbackup'] = 'Übersicht zum Kurs Template Backup';
$string['origbackupfile'] = 'Erste Backup Datei (beim Einsenden)';
$string['recentactivitymodules'] = 'Module, die mit print_recent_activity geprüft werden';
$string['resettasksoverview'] = 'Übersicht über die Prozesse zur Template Zurücksetzung';
$string['restorebackupfile'] = 'Setze diesn Kurs durch eine Wiederherstellung des neuesten Backups zurück';
$string['scheduledpublishing'] = 'Die Veröffentlichung wurde geplant. Der Kurs sollte innerhalb weniger Minuten veröffentlicht werden. Bitte
    haben sie ein wenig Geduld.';
$string['status'] = 'Status';
$string['tasksoverview'] = 'Übersicht Template Reset';
$string['templatereset'] = 'Zurücksetzen des Templates';
$string['timecreated'] = 'Erstellt';
$string['uncheckedmodules'] = 'Module, die nicht auf inhaltliche Änderungen geprüft wurden';
$string['unknowncourse'] = 'Unbekannte Kurs (möglicherweise gelöscht), die Kurs ID war: {$a}';
$string['unkowncreator'] = 'Unbekannter Backup-Ersteller';
$string['userdataids'] = 'Enthaltene User IDs';
$string['userdataincluded'] = 'Enthielt beim Einsenden Userdaten';
$string['uncheckablemodules'] = 'Module, die nicht auf inhaltliche Änderungen geprüft werden können';