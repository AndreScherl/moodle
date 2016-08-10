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
       
        $array = array();
        $manager = get_log_manager();
        $selectreaders = $manager->get_readers('\core\log\sql_reader');
        
        if ($selectreaders) {
            $obj = new \stdClass();
            $reader = reset($selectreaders);
            $timestamp = strtotime('today', time());
            $week_index = 7;
            
            while($week_index != 0) {
                $count = 0;
                $id_array = array();
                $obj->day = date('l', $timestamp - 86400 * $week_index);
                $am = $timestamp - 86400 * $week_index;
                $week_index--;
                $pm = $timestamp - 86400 * $week_index;
                
                $result = $reader->get_events_select('timecreated > ? AND timecreated < ? AND (action = "loggedin" OR action = "loggedout")', array($am, $pm), '', 0 ,0);
                while($result){
                    $sql_obj = array_shift($result)->get_data();
                    if(!in_array($sql_obj[objectid], $id_array)) {
                        $id_array[] = $sql_obj[objectid];
                        $count++;
                    }
                }
                unset($id_array);
                $obj->count = $count;
                $array[] = $obj;
                unset($obj);
            }
        }
        
        $data = array(
           'monday_date' => date('d.m. D', strtotime('last monday', strtotime('tomorrow'))),
           'monday_count' => $this->return_count($array, 'Monday'),
           'tuesday_date' => date('d.m. D', strtotime('last tuesday', strtotime('tomorrow'))),
           'tuesday_count' => $this->return_count($array, 'Tuesday'),
           'wednesday_date' => date('d.m. D', strtotime('last wednesday', strtotime('tomorrow'))),
           'wednesday_count' => $this->return_count($array, 'Wednesday'),
           'thursday_date' => date('d.m. D', strtotime('last thursday', strtotime('tomorrow'))),
           'thursday_count' => $this->return_count($array, 'Thursday'),
           'friday_date' => date('d.m. D', strtotime('last friday', strtotime('tomorrow'))),
           'friday_count' => $this->return_count($array, 'Friday'),
           'saturday_date' => date('d.m. D', strtotime('last saturday', strtotime('tomorrow'))),
           'saturday_count' => $this->return_count($array, 'Saturday'),
           'sunday_date' => date('d.m. D', strtotime('last sunday', strtotime('tomorrow'))),
           'sunday_count' => $this->return_count($array, 'Sunday')
       );
        return $data;
    }
    
    public function return_count(array $array, $day) {
        for($i = 0; $i < sizeof($array); $i++) {
            if($array[$i]->day == $day) {
                return $array[$i]->count;
            }
        }
        return 0;
    }


    public function has_content() {
        return true;
    }

}