<?php
require_once (dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/config.php');

use \block_star_rating\starrating;

global $PAGE, $OUTPUT;

$thisurl = new moodle_url ('/blocks/star_rating/rating.php');

$courseid = required_param ( 'courseid', PARAM_INT );
$thisurl->param ('courseid',$courseid);
$PAGE->set_url ($thisurl);
$PAGE->set_pagelayout ('course');

$coursecontext = context_course::instance ( $courseid );

$PAGE->set_context ( $coursecontext );

echo $OUTPUT->header ();

require_once ("$CFG->libdir/formslib.php");

$renderer = $PAGE->get_renderer('block_star_rating');
echo $renderer->starrating();
echo $OUTPUT->footer ();
