<?php

namespace block_star_rating;
// moodleform is defined in formslib.php
class starrating extends \moodleform {
	// Add elements to form
	public function definition() {
		global $CFG;
		
		$mform = $this->_form;
		
		$radioarray = array ();
		$radioarray [] = & $mform->createElement ( 'radio', 'yesno', '', get_string ( 'star_1' ,'block_star_rating'), 1);
		$radioarray [] = & $mform->createElement ( 'radio', 'yesno', '', get_string ( 'star_2' ,'block_star_rating'), 2);
		$radioarray [] = & $mform->createElement ( 'radio', 'yesno', '', get_string ( 'star_3' ,'block_star_rating'), 3);
		$radioarray [] = & $mform->createElement ( 'radio', 'yesno', '', get_string ( 'star_4' ,'block_star_rating'), 4);
		$radioarray [] = & $mform->createElement ( 'radio', 'yesno', '', get_string ( 'star_5' ,'block_star_rating'), 5);
		$mform->addGroup ( $radioarray, 'radioar', '', array (' '), false );
		
		$mform->addElement('text', 'block_star_rating_comments', get_string('comments', 'block_star_rating'),  array('maxlength' => 200, 'size' => 100));
		$mform->setType('block_star_rating_comments', PARAM_RAW_TRIMMED);
		
		$buttonarray=array();
		$buttonarray[] =& $mform->createElement('submit', 'submitbutton', get_string('savechanges' ,'block_star_rating'));
		$buttonarray[] =& $mform->createElement('submit', 'cancel', get_string('cancelbutton' ,'block_star_rating'));
		$mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
				
				
	}
	// Custom validation should be added here
	function validation($data, $files) {
		return array ();
	}
}