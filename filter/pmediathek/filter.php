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
 * Convert Prüfungsarchiv mediathek embed links into iframes
 *
 * @package   filter_pmediathek
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();

class filter_pmediathek extends moodle_text_filter {
    public function filter($text, array $options = array()) {
        global $CFG, $DB, $USER;
        $basepath = preg_quote("{$CFG->wwwroot}/repository/pmediathek/link.php?hash=");
        $regex = "%<a.*?href=\"({$basepath})([a-z0-9]*)(&|&amp;)embed=1\".*?</a>%";
        $matches = array();
        if (!preg_match_all($regex, $text, $matches, PREG_SET_ORDER)) {
            return $text;
        }

        foreach ($matches as $match) {
            $find = $match[0];
            $hash = $match[2];
            if ($desturl = $DB->get_field('repository_pmediathek_link', 'url', array('hash' => $hash))) {
                $desturl .= '&mode=display&user='.$USER->username;
                $replace = '<iframe class="pmediathek_embed" src="'.$desturl.'"></iframe>';
                $text = str_replace($find, $replace, $text);
            }
        }

        return $text;
    }
}