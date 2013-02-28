<?php
function dlbgs_process_css($css, $theme) {
    global $CFG;

   $tag = '[[font:Amaranth]]';
   if (strpos($_SERVER['HTTP_USER_AGENT'],"MSIE") != false){

    $replacement = $CFG->wwwroot.'/theme/dlbgs/pix/fonts/Amaranth-webfont.eot';

   }
   else{

    $replacement = $CFG->wwwroot.'/theme/dlbgs/pix/fonts/Amaranth-webfont.ttf';

   }
    $css = str_replace($tag, $replacement, $css);
    return $css;
}

?>
