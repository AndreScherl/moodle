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

require_once(dirname(__FILE__) . '/../../../config.php');

//sicherstellen, dass ein gültiges barrierearmes Theme ausgewählt wurde:
$themenames = array_keys(get_plugin_list('theme'));

if (!in_array($CFG->block_dlb_lowbarriertheme, $themenames)) {
    throw new coding_exception("Value \"block_dlb_lowbarriertheme\" ({$CFG->block_dlb_lowbarriertheme})in block_dlb Configuration is not valid. Theme not changed.");
}

//Falls ein SESSION-THEME gesetzt ist dieses Löschen, sonst setzen, falls dieses gültig ist
if (!empty($_SESSION['SESSION']->theme)) {

    unset($_SESSION['SESSION']->theme);

} else {

    $newtheme = $CFG->block_dlb_lowbarriertheme;

    //neues Theme checken...
    if (!in_array($newtheme, $themenames)) {
        throw new coding_exception("Theme {$newtheme} is not valid. Theme not changed.");
    }

    $_SESSION['SESSION']->theme = $newtheme;
}

//Weiterleiten zur Ausgangsseite:
$returnto = optional_param('returnto', 'index.php', PARAM_URL);

$returnto = urldecode($returnto);
//Verifizieren der Ausgangsseite
$regex = '@^'.$CFG->wwwroot.'@';
if (preg_match($regex, $returnto) == false) {
    print_error(get_string('invalidredirect', 'block_dlb'));
}

redirect($returnto);
?>