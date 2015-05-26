<?php

require('../../../config.php');
require_once($CFG->dirroot.'/local/mbs/lib.php');

require_once($CFG->libdir . '/adminlib.php');

for ($i = 0; $i < 500; $i++) {
    echo '<br/>kurs-'.$i;
    $backend = new tool_generator_course_backend('kurs-'.$i, 0);
    $id = $backend->make();
}


//local_mbs::fix_catgeorie_sortorder(4);

