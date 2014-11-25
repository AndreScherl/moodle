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
 * Version information for Prüfungsarchiv activity
 *
 * @package   mod_pmediathek
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();

$module->version   = 2014071000;
$module->requires  = 2014051200; // Moodle 2.7.
$module->cron      = 0;
$module->component = 'mod_pmediathek';
$module->maturity  = MATURITY_STABLE;
$module->release   = '2.7+ (Build: 2013092300)';
 
$module->dependencies = array(
    'repository_pmediathek' => ANY_VERSION,
);


