<?php

include_once($CFG->dirroot.'/calendar/renderer.php');
class theme_synergy_base_core_calendar_renderer extends core_calendar_renderer {

	 /**
     * Adds a pretent calendar block
     *
     * @param block_contents $bc
     * @param mixed $pos BLOCK_POS_RIGHT | BLOCK_POS_LEFT
     */
    public function add_pretend_calendar_block(block_contents $bc, $pos=BLOCK_POS_RIGHT) {
    	global $PAGE;

    	if ($PAGE->pagetype == 'calendar-event') {
    		return false;
    	} else {
    		$this->page->blocks->add_fake_block($bc, $pos);
    	}
    }

}