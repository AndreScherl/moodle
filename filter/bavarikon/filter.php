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
 * Filter class
 *
 * @package    filter
 * @subpackage bavarikon
 * @copyright  2017 Andreas Wagner, ISB Bayern
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class filter_bavarikon extends moodle_text_filter {

    private static $loaded;

    public function filter($text, array $options = array()) {
        global $PAGE;

        if (!is_string($text) or empty($text)) {
            // non string data can not be filtered anyway
            return $text;
        }

        if (stripos($text, '</a>') === false) {
            // Performance shortcut - if not </a> tag, nothing can match.
            return $text;
        }

        // Looking for tags.
        $matches = preg_split('/(<[^>]*>)/i', $text, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        if (!$matches) {
            return $text;
        }

        $bavarikonurl = get_config('filter_bavarikon', 'bavarikonurl');

        if (empty($bavarikonurl)) {
            debugging('Bavarikon filter must be configurated');
            return $text;
        }

        $regex = "%<a.*?href=\"(https://bavarikon.de/object/(.*?)?(.*?))\".*?</a>%is";
        $matches = array();
        if (!preg_match_all($regex, $text, $matches, PREG_SET_ORDER)) {
            return $text;
        }

        if (!isset(self::$loaded)) {
            $PAGE->requires->js_call_amd('filter_bavarikon/resize', 'init', array());
            self::$loaded = true;
        }

        $newtext = $text;
        $newtext = preg_replace_callback($regex, array( &$this, 'filter_bavarikon_callback'), $newtext);

        // Return the same string except processed by the above.
        return $newtext;
    }

    /**
     * Callback for changes in bavarikon url.
     *
     * @param array $match
     */
    protected function filter_bavarikon_callback($match) {
        global $CFG;

        $medialink = str_replace($match[2], '', $match[1]);
        $medialink = str_replace($match[3], $match[3]."?mebisembedding=true", $medialink);

        $out = html_writer::tag('embed', '', array('class' => 'bavarikon-frame embed-responsive-item', 'src' => $medialink));

        return html_writer::tag('div', $out, array('class' => "embed-responsive embed-responsive-4by3"));
    }

}
