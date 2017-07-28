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
 * mbschangeplatform block caps.
 *
 * @package    mbschangeplatform
 * @copyright  2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 defined('MOODLE_INTERNAL') || die;
 
 
 
//admin_setting_configtext($name, $visiblename, $description, $defaultsetting, $paramtype=PARAM_RAW, $size=null)
$settings->add(new admin_setting_configtext('block_mbschangeplatform/changeurl',
            get_string('changeurl', 'block_mbschangeplatform'),
            get_string('changeurl_expl', 'block_mbschangeplatform'),
			'',
			PARAM_RAW));
			
$settings->add(new admin_setting_configtext('block_mbschangeplatform/linktext',
            get_string('linktext', 'block_mbschangeplatform'),
            get_string('linktext_expl', 'block_mbschangeplatform'),
			'',
			PARAM_RAW));

$settings->add(new admin_setting_configtext('block_mbschangeplatform/imgpath',
            get_string('imgpath', 'block_mbschangeplatform'),
            get_string('imgpath_expl', 'block_mbschangeplatform'),
			'',
			PARAM_RAW));			

			