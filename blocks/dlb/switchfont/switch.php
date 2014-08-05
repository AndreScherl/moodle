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

$value = optional_param('value', '0', PARAM_INT);
$returnto = optional_param('returnto', 'index.php', PARAM_URL);

if ($value != 0) {

    $cssindex = (isset($_SESSION['MOODLECSSINDEX']))? $_SESSION['MOODLECSSINDEX'] : 0;

    $cssindex = $cssindex + $value;
    $cssindex = max($cssindex , 0);

     if($CFG->theme == 'iwb'){
    $cssindex = min($cssindex, count(explode(",",$CFG->block_dlb_addacss)) - 1);
     }
     else if($CFG->theme !='iwb'){
    $cssindex = min($cssindex, count(explode(",",$CFG->block_dlb_addcss)) - 1);
     }
    $_SESSION['MOODLECSSINDEX'] = $cssindex;
} else {
    unset($_SESSION['MOODLECSSINDEX']);
}

$returnto = urldecode($returnto);
//Verifizieren der Ausgangsseite
$regex = '@^'.$CFG->wwwroot.'@';
if (preg_match($regex, $returnto) == false) {
    print_error(get_string('invalidredirect', 'block_dlb'));
}

//Weiterleiten zur Ausgangsseite:
$returnto = optional_param('returnto', 'index.php', PARAM_URL);
redirect($returnto);
?>