<?php
/**
 * This file may not be redistributed in whole or significant part.
 * Content of this file is Protected by International Copyright Laws.
 *
 * ~~~~~~~~~ This Plugin IS NOT FREE SOFTWARE ~~~~~~~~~~~~~~~~
 *
 * @package   local_dlb
 * @copyright 2013 Andreas Wagner. All Rights reserved.
 */
function local_dlb_extends_navigation($navigation) {
    global $CFG, $PAGE;

    if (empty($CFG->local_dlb_mebis_sites)) return;

    $node = $navigation->get('home');

    if ($node) {

        //Knoten umgestalten...
        $node->text = $CFG->local_dlb_home;
        $node->action = "";
        $node->mainnavonly = true;

        //neue Links einfügen..
        $nodes = explode(';', trim($CFG->local_dlb_mebis_sites,";"));

        foreach($nodes as $nnode) {
            list($name, $url) = explode(',', $nnode);
            if (!empty($name) and !empty($url)) $node->add($name, $url);
        }
    }
//+++atar: add node "Meine Schulen" to navigation
$schoolnode = $PAGE->navigation->add(get_string('schoolnode','local_dlb'),  navigation_node::TYPE_CONTAINER);
require_once($CFG->dirroot."/blocks/meineschulen/lib.php");
$schoolarray =meineschulen:: get_my_schools();

foreach($schoolarray as $school) {

                 $snode=$schoolnode->add($school->name, $school->viewurl);
                 $snode->make_active();

                  }
//---
}