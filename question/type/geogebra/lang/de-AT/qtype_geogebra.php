<?php

/**
 * Strings for component 'qtype_geogebra'
 *
 * @package        qtype
 * @subpackage     geogebra
 * @author         Christoph Stadlbauer <christoph.stadlbauer@geogebra.org>
 * @copyright  (c) International GeoGebra Institute 2014
 * @license        http://www.geogebra.org/license
 */
$string['pluginname'] = 'GeoGebra';
$string['pluginname_link'] = 'question/type/geogebra';$string['addconstraints'] = 'Bedingungen für die Variablen hinzufügen.';
$string['addmorevarblanks'] = 'Leerfelder für {no} zusätzliche Variablen';
$string['answerinvalid'] = 'Der Antwortstring in der Abgabe ist falsch. Das sollte nicht passieren.';
$string['answermissing'] = 'Der Antwortstring fehlt in der Abgabe. Möglicherweise ist JavaScript im Browser nicht aktiviert oder ein sonstiger unbekannter Fehler ist aufgetreten';
$string['answervar'] = 'Variablen für die automatische Bewertung';
$string['answervar_help'] = 'Für die automatische Bewertung:Der Name der booleschen Variable in GeoGebra, die wahr ist, wenn der Schüler die Aufgabe (teilweise) korrekt gelöst hat. Es werden alle Bewertungen aufsummiert. Die Frage gilt als korrekt beantwortet wenn irgendeine Kombination 100% übersteigt, aber es sollte mindestens eine Kombination geben die genau 100% ergibt. Leer lassen für manuelle Bewertung!';
$string['constraints'] = 'Bedingungen (Ungleichungen)';
$string['constraints_help'] = 'Gibt es Bedingungen (Ungleichungen), die die Variablen erfüllen müssen? Z.B.: a < b, die nicht über die Slidereinstellungen eingegeben werden konnten? Komma getrennt. Unterstützte Ungleichungen sind <, <=, >, >=. Falls sie Gleichheit benötigen müssen sie in GeoGebra die gleiche Variable verwenden. Das Verwenden von Variablen für Min/Max für die Slider wird von diesem Plugin nicht unterstützt.';
$string['constraintswrongortoohard'] = '{$a->inequalities} sind ungültig oder erfordern zu viel Rechenleistung, we tried (brute force) {$a->tries} times in {$a->time} seconds. Maybe we\'ll use better math in the future...';
$string['feedback'] = 'Feedback für diese Variable wenn diese wahr ist.';
$string['feedback_help'] = 'Als Feedback wird automatisch die Beschriftung dieser Variable/Object in der GeoGebradatei verwendet.';
$string['geogebraapplet'] = 'GeoGebra Applet';
$string['getvars'] = 'Variablen die zufällig verändert werden könnten aus dem Applet laden';
$string['ggbfilemissing'] = 'Der Base64-String fehlt in der Abgabe. Möglicherweise ist JavaScript im Browser nicht aktiviert oder ein sonstiger unbekannter Fehler ist aufgetreten';
$string['ggbturl'] = 'URL oder ID des GeoGebraTube Worksheets';
$string['ggbturl_help'] = 'Sie können entweder den Teilen Button in GeoGebraTube verwenden und den Link kopieren und einfügen oder die GeoGebra Repository verwenden. Das Applet wird vollständig in der Moodle Datenbank gespeichert. Das Applet wird nur von GeoGebraTube neu geladen wenn Sie den Button "Applet (neu) laden und anzeigen" klicken. Wenn Sie nur die ID oder Sharing Key verwenden sollte auch das richtige Applet geladen werden können.';
$string['ggbxmlmissing'] = 'Der XML-String fehlt in der Abgabe. Möglicherweise ist JavaScript im Browser nicht aktiviert oder ein sonstiger unbekannter Fehler ist aufgetreten';
$string['invalidinequality'] = '{$a} ist ungültig';
$string['israndomized'] = 'Gibt es Variablen, die zufällig verändert werden sollen?';
$string['loadapplet'] = 'Applet (neu) laden und anzeigen';
$string['loadapplet_help'] = 'Applet von GeoGebraTube (neu) laden und die neue Version in der Datenbank speichern.';
$string['mineqmax'] = 'Min und Max für die Randomisierung sind für die Variable {$a} nicht korrekt definiert. Entweder Slider min und max sind nicht korrekt angegeben oder das Objekt ist kein Slider. Sie müssen das möglicherweise in Ihrer GeoGebra-Datei korrigieren.';
$string['minplusstepgtmax'] = 'Min plus Inkrement ist größer als Max für Variable {$a}, Sie müssen das möglicherweise in Ihrer GeoGebra-Datei korrigieren.';
$string['noappletloaded'] = 'Es wurde kein Applet geladen! Überprüfen Sie ob die URL korrekt ist und ob das applet sichtbar ist nach dem Sie ein Applet gewählt oder (neu) geladen haben!';
$string['nofractionsumeq1'] = 'Mindestens eine Kombination von Bewertungen muss in Summe 100% ergeben!';
$string['pluginname_help'] = 'Fragen die der Teilnehmer in GeoGebra lösen kann.';
$string['pluginnameadding'] = 'Eine GeoGebra basierte Frage hinzufügen';
$string['pluginnameediting'] = 'Eine GeoGebra basierte Frage bearbeiten';
$string['pluginnamesummary'] = 'Eine Version der berechneten Fragen, bei der GeoGebra für die Anzeige der Frage und Überprüfung der Antwort verwendet wird.';
$string['randomizedbutnovars'] = 'Sie haben angegeben, dass die Frage randomisiert werden soll haben aber keine oder nicht existierende Variablen angegeben.';
$string['randomizedvar'] = 'Variablen die zufällig verändert werden sollen';
$string['randomizedvar_help'] = 'Variablen die zufällig verändert werden sollen. Komma getrennt. Verwenden Sie die Slidereinstellungen in GeoGebra um Min, Max und Schrittweite festzulegen. Die Werte der Variablen können auch in der Frage benutzt werden, indem man sie in geschwungene Klammern setzt, z.B. {a}';
$string['stepzero'] = 'Inkrement ist 0, für Variable {$a}. Entweder das Inkrement beim  Slider ist nicht korrekt angegeben oder das Objekt ist kein Slider. Sie müssen das möglicherweise in Ihrer GeoGebra-Datei korrigieren.';
$string['valuecheckedfor'] = 'Boolesches Objekt in GeoGebra, mit dem die Aufgabe auf Korrektheit überprüft werden kann.';
$string['variablenamewrong'] = 'A variable with that name could not be found in the GeoGebra file.';
$string['variableno'] = 'Variable {$a}';
$string['variables'] = 'Variablen';
$string['willbereadfromfile'] = 'Wird aus GeoGebra gelesen... (siehe Hilfebutton)';