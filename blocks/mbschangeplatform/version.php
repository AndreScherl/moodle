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
 * Version details
 *
 * @package    block_mbschangeplatform
 * @copyright  2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 defined('MOODLE_INTERNAL') || die();
 
$plugin->version = 2015040700;    // YYYYMMDDHH (year, month, day, 24-hr time)
$plugin->requires = 2012112900;    // YYYYMMDDHH (Requires this Moodle version)
$plugin->component = 'block_mbschangeplatform';    // Full name of the plugin (It is used during the installation and 
											// upgrade process for diagnostics and validation purposes to make sure 
											// the plugin code has been deployed to the correct location within the 
											// Moodle code tree. )