<?php
defined ( 'MOODLE_INTERNAL' ) || die ();
class block_star_rating_renderer extends plugin_renderer_base {
	public function starrating() {
		global $OUTPUT, $DB, $PAGE;
				
		$html = '';

		$seanform = new \block_star_rating\starrating();
		$html .= \html_writer::div ( $seanform->render(), 'star_rating_div' );
		$headingpanel = \html_writer::tag ('h3', 'Search Results'); // TODO: Externalise
				                                                           
		return $html;
	}
}