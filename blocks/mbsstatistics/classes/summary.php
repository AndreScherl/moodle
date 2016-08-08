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
 * @package    block_mbsstatistics
 * @copyright  René Egger <rene.egger@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbsstatistics;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

/**
 * Summary renderable class.
 *
 * @package    block_mbsstatistics
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class summary implements renderable, templatable {

   public function __construct() {
   }


   public function export_for_template(renderer_base $output) {
       global $DB;
       
       
       $sql_data = array();
       $array_index = 0; 
       $wochentag_index = 6;
       
       $sql_data[$array_index] = date('l', strtotime('today', time()));
       //86400 = 24 std.
       while($wochentag_index != 0) {
           $array_index++;
           $sql_data[$array_index] = strtotime('today', time()) - 86400 * $wochentag_index;
           $array_index++;
           $wochentag_index--;
           $sql_data[$array_index] = strtotime('today', time()) - 86400 * $wochentag_index;
           $array_index++;
           $sql_data[$array_index] = date('l', $sql_data[$array_index-2]);
       }
       $array_index++;
       $sql_data[$array_index] = strtotime('today', time());
       $array_index++;
       $sql_data[$array_index] = strtotime('today', time()) + 86400;
       
       $sql_object = $DB->get_record_sql('SELECT COUNT(*) AS ?,'
               . '(SELECT COUNT(*) FROM {user} WHERE currentlogin >= ? AND currentlogin < ?) AS ?,'
               . '(SELECT COUNT(*) FROM {user} WHERE currentlogin >= ? AND currentlogin < ?) AS ?,'
               . '(SELECT COUNT(*) FROM {user} WHERE currentlogin >= ? AND currentlogin < ?) AS ?,'
               . '(SELECT COUNT(*) FROM {user} WHERE currentlogin >= ? AND currentlogin < ?) AS ?,'
               . '(SELECT COUNT(*) FROM {user} WHERE currentlogin >= ? AND currentlogin < ?) AS ?,'
               . '(SELECT COUNT(*) FROM {user} WHERE currentlogin >= ? AND currentlogin < ?) AS ?'
               . ' FROM {user} WHERE currentlogin >= ? AND currentlogin < ?', $sql_data);
       
       $data = array(
           'Montag_date' => date('d.m. D', strtotime('last monday', strtotime('tomorrow'))),
           'Montag_count' => $sql_object->monday,
           'Dienstag_date' => date('d.m. D', strtotime('last tuesday', strtotime('tomorrow'))),
           'Dienstag_count' => $sql_object->tuesday,
           'Mittwoch_date' => date('d.m. D', strtotime('last wednesday', strtotime('tomorrow'))),
           'Mittwoch_count' => $sql_object->wednesday,
           'Donnerstag_date' => date('d.m. D', strtotime('last thursday', strtotime('tomorrow'))),
           'Donnerstag_count' => $sql_object->thursday,
           'Freitag_date' => date('d.m. D', strtotime('last friday', strtotime('tomorrow'))),
           'Freitag_count' => $sql_object->friday,
           'Samstag_date' => date('d.m. D', strtotime('last saturday', strtotime('tomorrow'))),
           'Samstag_count' => $sql_object->saturday,
           'Sonntag_date' => date('d.m. D', strtotime('last sunday', strtotime('tomorrow'))),
           'Sonntag_count' => $sql_object->sunday
       );
       return $data;
    }
    
    public function has_content() {
        return true;
    }

}