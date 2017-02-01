<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/hvp/renderer.php');
class theme_mebis_mod_hvp_renderer extends mod_hvp_renderer {

	
	public function hvp_add_styles($libraries) {
		global $CFG;
		$styles = array();
		if (isset($libraries['H5P.CoursePresentation']) && $libraries['H5P.CoursePresentation']['majorVersion'] == '1') {
			$styles[] = (object) array(
					'path' => $CFG->httpswwwroot.'/theme/mebis/style/h5p_cp_overrides.css',
					'version' => '',
			);
		}
		return $styles;
	}
	
}