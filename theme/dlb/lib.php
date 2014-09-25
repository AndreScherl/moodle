<?php
function dlb_process_css($css, $theme) {
    global $CFG;

  $tag[] = '[[font:VA]]';

    if (strpos($_SERVER['HTTP_USER_AGENT'],"MSIE") != false){

    $replacement[] = $CFG->wwwroot.'/theme/dlb/pix/fonts/vau30k-webfont.eot';

   }
   else{

    $replacement[] = $CFG->wwwroot.'/theme/dlb/pix/fonts/vau30k-webfont.ttf';

   }

   $tag[] = '[[font:Amaranth]]';

   if (strpos($_SERVER['HTTP_USER_AGENT'],"MSIE") != false){

    $replacement[] = $CFG->wwwroot.'/theme/dlb/pix/fonts/Amaranth-webfont.eot';

   }
   else{

    $replacement[] = $CFG->wwwroot.'/theme/dlb/pix/fonts/Amaranth-webfont.ttf';

   }


 $tag[] = '[[font:SchulbuchBayern]]';

   if (strpos($_SERVER['HTTP_USER_AGENT'],"MSIE") != false){

    $replacement[] = $CFG->wwwroot.'/theme/dlb/pix/fonts/SchulbuchBayernWeb.eot';

   }
   else{

    $replacement[] = $CFG->wwwroot.'/theme/dlb/pix/fonts/SchulbuchBayernComp.ttf';

   }

 $tag[] = '[[font:SAS]]';

   if (strpos($_SERVER['HTTP_USER_AGENT'],"MSIE") != false){

    $replacement[] = $CFG->wwwroot.'/theme/dlb/pix/fonts/bienchen_a-webfont.eot';

   }
   else{

    $replacement[] = $CFG->wwwroot.'/theme/dlb/pix/fonts/bienchen_a-webfont.ttf';

   }

    $tag[] = '[[font:SASB]]';

   if (strpos($_SERVER['HTTP_USER_AGENT'],"MSIE") != false){

    $replacement[] = $CFG->wwwroot.'/theme/dlb/pix/fonts/bienchen_b-webfont.eot';

   }
   else{

    $replacement[] = $CFG->wwwroot.'/theme/dlb/pix/fonts/bienchen_b-webfont.ttf';

   }


    $css = str_replace($tag, $replacement, $css);

    return $css;
}


?>
