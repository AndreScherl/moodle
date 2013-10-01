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

$module->version   = 2013092300;
$module->requires  = 2012120300; // 2.2: 2011120100; 2.3: 2012062500; 2.4: 2012120300; 2.5: 2013051400
$module->cron      = 0;
$module->component = 'mod_pmediathek';
$module->maturity  = MATURITY_BETA;
$module->release   = '2.4+ (Build: 2013092300)';
 
$module->dependencies = array(
    'repository_pmediathek' => ANY_VERSION,
);


