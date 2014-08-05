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

defined('MOODLE_INTERNAL') || die();

/**
 * This class does an extra text-cleaning, when $USER is an global Admin.
 */
class filter_scriptprotection extends moodle_text_filter {

    /** überprüft, ob eine Scriptsäuberung der Text notwendig ist und cached das
     * Ergebnis für Nicht-Admins im Attribut require_clean_text des globalen Objekts
     * $USER für die Dauer der Session.
     *
     * Für Admins erfolgt Prüfung bei jedem Zugriff.
     *
     * @return boolean true, wenn eine Textsäuberung erfolgen soll
     */
    function require_clean_text() {
        global $USER, $DB, $CFG;

        //Site-Admin immer prüfen und Textsäuberung erzwingen.
        if (is_siteadmin()) {
            $USER->require_clean_text = true;
            return $USER->require_clean_text;
        }

        //für nicht Site-Admins für die Dauer der Session cachen
        //falls die Variable noclean bereits gesetzt ist, diese zurückgeben.
        if (isset($USER->require_clean_text)) return $USER->require_clean_text;

        //Sonst Rollenzuweisungen überprüfen, einmal pro SESSION!
        if (!empty($CFG->filter_sp_rolestosupport)) {
            $sql = "SELECT count(*) as count FROM {role_assignments} ".
            "WHERE roleid in ({$CFG->filter_sp_rolestosupport}) and userid = '{$USER->id}'";

            $count = $DB->count_records_sql($sql);
            $USER->require_clean_text = ($count > 0);
            return $USER->require_clean_text;
        }

        $USER->require_clean_text = false;
        return $USER->require_clean_text;
    }

    function filter($text, array $options = array()) {

        //prüfen und ggf. den Text säubern.
        if (filter_scriptprotection::require_clean_text()) {
            return clean_text($text, FORMAT_HTML);
        }

        return $text;
    }
}
