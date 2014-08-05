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
require_once('../config.php');

$searchterm = optional_param('searchterm', '0', PARAM_ALPHANUMEXT);

if (empty($searchterm)) {
    echo "Kategorienamen eingeben.";
    die;
}

$sql = "SELECT id, name FROM {course_categories} where name like :searchterm";
$results = $DB->get_records_sql($sql, array('searchterm' => '%'.$searchterm.'%'));

$html ="";

if (count($results) > 0) {
    $html = "<div class=\"category-search\"><ul>";
    foreach ($results as $result) {
        $html .= "<li><a href=\"{$CFG->wwwroot}/course/category.php?id={$result->id}\">{$result->name}</a></li>";
    }
    $html .= "</ul></div>";
}
echo $html;
?>