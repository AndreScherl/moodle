<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * CSS overrides for mod hvp.
 *
 * @package   theme_mebis
 * @copyright 2017 ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/hvp/renderer.php');
class theme_mebis_mod_hvp_renderer extends mod_hvp_renderer {
	
	public function hvp_alter_styles(&$scripts, $libraries, $embedType) {
		global $CFG;
		if (isset($libraries['H5P.CoursePresentation']) && $libraries['H5P.CoursePresentation']['majorVersion'] == '1') {
			$scripts[] = (object) array(
					'path' => $CFG->httpswwwroot.'/theme/mebis/style/mebis-moodle.css',
					'version' => '',
			);
		}
	}
	
}