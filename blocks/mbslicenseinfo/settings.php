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
 * mbslicenseinfo block caps.
 *
 * @package    mbslicenseinfo
 * @author     Andre Scherl <andre.scherl@isb.bayern.de>
 * @copyright  2015 ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 defined('MOODLE_INTERNAL') || die;

//admin_setting_configtext($name, $visiblename, $description, $defaultsetting, $paramtype=PARAM_RAW, $size=null)
$settings->add(new admin_setting_configtext('block_mbslicenseinfo/extensionblacklist',
            get_string('extensionblacklist', 'block_mbslicenseinfo'),
            get_string('extensionblacklist_expl', 'block_mbslicenseinfo'),
			'doc,docx,dot,dotx,xls,xlsx,xlt,xltx,ppt,pptx,odt,ott,ods,ots,odp,pdf',
			PARAM_RAW));	

$settings->add(new admin_setting_configtext('block_mbslicenseinfo/filesperpage',
            get_string('filesperpage', 'block_mbslicenseinfo'),
            get_string('filesperpage_expl', 'block_mbslicenseinfo'),
			10,
			PARAM_INT));

			