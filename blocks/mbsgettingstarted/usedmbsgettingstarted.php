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
 * File for event to be triggered when a link in block mbsgettingstarted is used.
 *
 * @package    block_mbsgettingstarted
 * @copyright  2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');

require_sesskey();
require_login();

$linkid = required_param('id', PARAM_TEXT);

$context = context_user::instance($USER->id);
$event = \block_mbsgettingstarted\event\link_viewed::create(
                array('context' => $context, 'relateduserid' => $USER->id,
                    'other' => array('selectedlink' => $linkid)));
$event->trigger();
