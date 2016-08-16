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
   
   /** search in logfile for loggedin-users
    * 
    * @global type $DB
    * @return array
    */


   public function export_for_template(renderer_base $output) {
       global $DB;
       
        $data = array();
        $manager = get_log_manager();
        $selectreaders = $manager->get_readers('\core\log\sql_reader');
        
        if ($selectreaders) {
            $reader = reset($selectreaders);
            $timestamp = strtotime('today', time());
            $weekindex = 0;
            
            //...for each weekday
            while($weekindex != 7) {
                $count = 0;
                unset($idarray);
                $idarray = array();
                //86400s = 24h
                $day = strtolower(date('l', $timestamp - 86400 * $weekindex));
                $am = $timestamp - 86400 * $weekindex;
                $pm = $am + 86400;
                
                $result = $reader->get_events_select('timecreated > ? AND timecreated < ? AND (action = "loggedin" OR action = "loggedout")', array($am, $pm), '', 0 ,0);
                while($result){
                    //count each user once
                    $sqlobj = array_shift($result)->get_data();
                    if(!in_array($sqlobj['objectid'], $idarray)) {
                        $idarray[] = $sqlobj['objectid'];
                        $count++;
                    }
                }
                $data['date'.$weekindex] = date('d.m. ', strtotime('last '.$day, strtotime('tomorrow'))).get_string($day, 'block_mbsstatistics');
                $data['count'.$weekindex] = $count;
                $weekindex++;
            }
        }
        print_r($data);
        //return date and counted useres for each day
        return $data;
    }


    public function has_content() {
        return true;
    }

}