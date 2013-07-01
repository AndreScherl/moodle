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
 * Category-related customisations for ALP
 *
 * @package   local_alp
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class local_dlb {
     public static function can_edit_idnumber($category) {
     global $DB;
      if ($category->id) {
          if ($category->depth > 3) {
              return true; // Within a school.
          }
         $context = context_coursecat::instance($category->id);
         return has_capability('local/dlb:editschoolid', $context);
     }
      // Creating a new category - check the parent category instead.
      $parent = optional_param('parent', null, PARAM_INT);
      if (!$parent) {
          return has_capability('local/dlb:editschoolid', context_system::instance());
         }

     $parentcat = $DB->get_record('course_categories', array('id' => $parent), '*', MUST_EXIST);
      if ($parentcat->depth >= 3) {
          return true; // School or lower.
      }
         $context = context_coursecat::instance($parentcat->id);
         return has_capability('local/dlb:editschoolid', $context);
     }
 }
