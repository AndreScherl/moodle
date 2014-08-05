<?php
/*
 #########################################################################
 #                       DLB-Bayern
 # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 #
 # Copyright 2012 Andreas Wagner. All Rights Reserved.
 # This file may not be redistributed in whole or significant part.
 # Content of this file is Protected By International Copyright Laws.
 #
 # ~~~~~~~~~~~~~~~~~~ THIS CODE IS NOT FREE SOFTWARE ~~~~~~~~~~~~~~~~~~~~
 #
 # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 # @author Andreas Wagner, DLB	andreas.wagner@alp.dillingen.de
 #########################################################################
*/

$string['pluginname'] = 'Kursbereiche xtd';

/** Bearbeiten des Kursbereichsheaders */
$string['custom_category:editheader'] = 'Kursbereichsheader bearbeiten';
$string['custom_category:editheaderimage'] = 'Kursbereichsheaderbild bearbeiten';

$string['cat_attributes'] = 'Eigenschaften des Kursbereiches';
$string['cat_headline'] = 'Überschrift des Kursbereiches';
$string['imagepreview'] = 'Bildvorschau';
$string['editheader'] = "Header bearbeiten";
$string['custom_header'] = "Eigenschaften des Headerbildes";
$string['custom_header_imgwidth'] = "Breite des Headerbildes";
$string['custom_header_imgwidthexpl'] = "Das Headerbildes wird auf diese Breite zugeschnitten";
$string['custom_header_imgheight'] = "Höhe des Headerbildes";
$string['custom_header_imgheightexpl'] = "Das Headerbildes wird auf diese Höhe zugeschnitten";

/** Kursbereichsdarstellung */
$string['courselistheading'] = 'Optionen in der Kurslistendarstellung';
$string['coursenamelength'] = 'Länge der Kursbezeichnung';
$string['coursenamelengthexpl'] = 'Länge der Kursbezeichnung in der Kursbereichsliste. Ist der Kursname länger, so wird "..." ergänzt.';

$string['usecourselinks'] = 'Kursverlinkungen verwenden';
$string['usecourselinksexpl'] = 'Kursverlinkungen können in der Kategorieansicht erzeugt werden, um Kurs aus anderen Kursbereichen anzuzeigen.';

/** Kategorieliste */
$string['folder_open'] = 'Ordnersymbol offen';
$string['folder_closed'] = 'Ordnerymbol zu';
$string['folder_up'] = 'Ordnersymbol nach oben';
$string['intocategory'] = 'zum Kursbereich';
$string['targetlink'] = '----- Zielmarke -------------- Zielmarke -----';
$string['targettitle'] = 'Zielfeld';
$string['cancelmove'] = 'Verschieben abbrechen';
$string['catheading'] = 'Hauptbereich {$a}';

/** Kursbereichsstatus */
$string['movecatexpl'] = 'Kategorie {$a} test wird verschoben: Sie können in eine Kategorie navigieren und das Zielfeld anklicken';
$string['category_moved'] = 'Kategorie {$a} wurde verschoben';
$string['invalidcattomove'] = 'Der zum Verschieben gewählte Kursbereich ist nicht gültig';
$string['invalidtargetcat'] = 'Der Kursbereich kann nicht in einen seiner Unterbereiche verschoben werden.';

/** Kursliste */
$string['courselist'] = 'Liste der Kurse des Bereiches {$a}';
$string['sortorder'] = 'Sortierung';
$string['sortorder_asc'] = 'Priorität aufsteigend';
$string['sortorder_desc'] = 'Priorität absteigend';
$string['fullname_asc'] = 'Name aufsteigend';
$string['fullname_desc'] = 'Name absteigend';
$string['timecreated_asc'] = 'Datum aufsteigend';
$string['timecreated_desc'] = 'Datum absteigend';

$string['perpage'] = "  Kurse pro Seite";
$string['movecourseexpl'] = 'Kurs {$a} wird verschoben: Bitte in den Zielbereich navigieren und Zielfeld anklicken';
$string['invalidcoursetomove'] = 'Der zum Verschieben gewählte Kurs exisitert nicht {$a}';
$string['course_moved'] = 'Kurs {$a} wurde verschoben';
$string['courselink'] = 'Link auf Kurs';

//Kurslinks
$string['linkcourseexpl'] = 'Kurs {$a} wird verlinkt: Bitte in den Zielbereich navigieren und Zielfeld anklicken';
$string['linkexistsexpl'] = 'Für den Kurs {$a} ist bereits eine Kursverlinkung in diesem Kursbereich vorhanden';
$string['invalidcoursetolink'] = 'Der zum Verlinken gewählte Kurs ist ungültig {$a}';
$string['courseexistsexpl'] = 'Der zum Verlinken gewählte Kurs {$a} befindet sich bereits in diesem Kursbereich';
$string['courselink_created'] = 'Kurslink erzeugt';
$string['courselink_deleted'] = 'Kurslink gelöscht';
$string['create_courselink'] = 'Kurslink erzeugen';
$string['cancellink'] = 'Verlinken abbrechen';

$string['movelinkexpl'] = 'Link {$a} wird verschoben: Bitte in den Zielbereich navigieren und Zielfeld anklicken';
$string['link_moved'] = 'Link {$a} wurde verschoben';
$string['nopermissiontoeditcat'] = 'Sie haben nicht das Recht, diese Kategorie zu bearbeiten.';

//Zugangssinformation
$string['enrol_guest_withoutpassword'] = 'Dieser Kurs ist für Gäste zugelassen.';
$string['enrol_guest_withpassword'] = 'Dieser Kurs ist für Gäste mit Zugangsschlüssel zugelassen.';
$string['enrol_self_withoutkey'] = 'Die Einschreibung in diesen Kurs ist für eingeloggte User möglich.';
$string['enrol_self_withkey'] = 'Die Einschreibung in diesen Kurs erfordert einen Zugangsschlüssel.';

?>