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
 * Brings license informations direct to media
 *
 * @package   filter_mbslicenseinfo
 * @copyright 2015 ISB Bayern
 * @author    Andre Scherl <andre.scherl@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2016031500;
$plugin->requires  = 2010112400; // 2.0
$plugin->component = 'filter_mbslicenseinfo';
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '0.1 (Build: 2015112300)';
$plugin->dependencies = array(
    'local_mbs'  => 2015120907,
    'local_mbslicenseinfo' => 2015121000
);
 


