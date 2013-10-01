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
 * Version information for Prüfungsarchiv Mediathek repository
 *
 * @package   repository_pmediathek
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2013091700;        // The current plugin version (Date: YYYYMMDDXX).
$plugin->release   = '2.4+ (Build 2013091700)';
$plugin->requires  = 2012120300;        // Requires this Moodle version.
$plugin->component = 'repository_pmediathek'; // Full name of the plugin (used for diagnostics).
$plugin->maturity  = MATURITY_BETA;
$plugin->cron      = 0;

