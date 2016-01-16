<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 2 of the License, or
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
 * Mebis New Blcok message processor, stores messages to be shown using the mebis news block.
 *
 * @package   message_mbsnewsblock
 * @copyright 2016 Andreas Wagner, ISB
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2016011600;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2014051209;        // Requires this Moodle version
$plugin->component = 'message_mbsnewsblock';  // Full name of the plugin (used for diagnostics)
