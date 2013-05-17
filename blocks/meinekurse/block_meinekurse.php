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
 * Course overview block
 *
 * Currently, just a copy-and-paste from the old My Moodle.
 *
 * @package   blocks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/weblib.php');
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/blocks/meinekurse/lib.php');

class block_meinekurse extends block_base {
    /**
     * block initializations
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_meinekurse');
    }

    public function instance_can_be_docked() {
        return false;
    }

    public function instance_can_be_hidden() {
        return false;
    }

    public function instance_can_be_collapsed() {
        return false;
    }

    /**
     * block contents
     *
     * @return object
     */
    public function get_content() {
        global $USER, $PAGE;

        $PAGE->requires->js('/blocks/meinekurse/javascript/jquery.js');
        $PAGE->requires->js('/blocks/meinekurse/javascript/jqueryui.js');
        $PAGE->requires->js('/blocks/meinekurse/javascript/meinekurse.js');

        if ($this->content !== NULL) {
            return $this->content;
        }

        $opts = array('pageurl' => $PAGE->url->out());
        $PAGE->requires->yui_module('moodle-block_meinekurse-paging', 'M.block_meinekurse.paging.init', array($opts));

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $content = '';

        //Handle submitted / saved data
        $prefs = meinekurse::get_prefs();
        if ($sortby = optional_param('meinekurse_sortby', null, PARAM_TEXT)) {
            /*if ($prefs->sortby == $sortby) {
                $prefs->sortdir = ($prefs->sortdir == 'asc') ? 'desc' : 'asc';
            } else*/ {
                $prefs->sortby = $sortby;
            }
        }
        if ($numcourses = optional_param('meinekurse_numcourses', null, PARAM_INT)) {
            $prefs->numcourses = $numcourses;
        }
        if (!is_null($school = optional_param('meinekurse_school', null, PARAM_INT))) {
            $prefs->school = $school;
        }
        meinekurse::set_prefs($prefs);

        $pagenum = optional_param('meinekurse_page', 0, PARAM_INT) + 1;

        //Get courses:
        $mycourses = meinekurse::get_my_courses($prefs->sortby, $prefs->sortdir, $prefs->numcourses, $prefs->school, $pagenum);

        $starttab = 0;
        $tabnum = 0;
        foreach ($mycourses as $school) {
            if ($prefs->school == $school->id) {
                $starttab = $tabnum;
                break;
            }
            $tabnum++;
        }

        $content .= '<script type="text/javascript">var starttab = '.$starttab.';</script>';

        //Tabs
        $content .= '<div class="mycoursestabs">';

        // Tab headings.
        $content .= '<ul>';
        foreach ($mycourses as $school) {
            $tab = html_writer::link("#school{$school->id}tab", format_string($school->name));
            $tab = html_writer::tag('li', $tab, array('class' => 'block'));
            $content .= $tab;
        }
        $content .= '</ul>';

        // Sorting icons.
        $baseurl = new moodle_url($PAGE->url);
        //$content .= self::sorting_icons($baseurl, $prefs->sortby);

        // Tab contents.
        foreach ($mycourses as $school) {
            $tab = self::sorting_form($baseurl, $prefs->sortby, $numcourses);
            $tab .= meinekurse::one_tab($USER, $prefs, $school->courses, $school->id, $school->coursecount, $school->page);
            $content .= html_writer::tag('div', $tab, array('id' => "school{$school->id}tab"));
        }

        $content .= '</div>';

        $this->content->text = $content;

        return $this->content;
    }


    /**
     * Output the HTML for the icons to sort the courses.
     *
     * @param moodle_url $baseurl the URL to base the links on
     * @param string $selectedtype the sort currently selected
     * @return string html snipet for the icons
     */
    protected static function sorting_icons($baseurl, $selectedtype) {

        $out = '';

        foreach (meinekurse::$validsort as $sorttype) {
            $str = get_string("sort{$sorttype}", 'block_meinekurse');
            $text = html_writer::tag('span', $str);
            $attr = array('id' => "meinekurse_sort{$sorttype}", 'title' => $str);
            if ($sorttype == $selectedtype) {
                $attr['class'] = 'selected';
            }
            $url = new moodle_url($baseurl, array('meinekurse_sortby' => $sorttype));
            $out .= html_writer::link($url, $text, $attr);
        }

        return html_writer::tag('div', $out, array('class' => 'meinekurse_sorticons'));
    }

    /**
     * Output the HTML for the form to sort the courses.
     *
     * @param moodle_url $baseurl the URL to base the links on
     * @param string $selectedtype the sort currently selected
     * @param $numcourses
     * @return string html snipet for the icons
     */
    protected static function sorting_form($baseurl, $selectedtype, $numcourses) {

        $prefs = new stdClass();
        $prefs->sortby = $selectedtype;
        $prefs->numcourses = $numcourses;

        $out = '';
        $out .= html_writer::input_hidden_params($baseurl);
        $table = new html_table();
        $table->head = array(
            get_string('sortby', 'block_meinekurse'),
            get_string('numcourses', 'block_meinekurse'));
        $table->align = array('center', 'center', 'center');
        $table->data = array();
        $row = array();
        $row[] = self::html_select('sortby', array('name', 'timecreated', 'timevisited'), true, $prefs);
        $row[] = self::html_select('numcourses', array(2, 5, 10, 20, 50, 100), false, $prefs);
        $table->data[] = $row;
        $out .= html_writer::table($table);

        return html_writer::tag('form', $out, array('method' => 'get', 'action' => $baseurl->out_omit_querystring()));
    }

    /**
     * Return a html <select> tag
     * @param string $selectname - name of the select tag
     * @param string[] $options
     * @param bool $usegetstring - get string from language file or just display as is
     * @param object $data - preset data
     * @return string
     */
    private static function html_select($selectname, $options, $usegetstring = true, $data = null) {
        if (is_null($data)) {
            $data = new stdClass();
        }
        $fullname = "meinekurse_{$selectname}";
        $select = '<select name="'. $fullname . '" class="'.$fullname.'">';
        foreach ($options as $option) {
            $selected = '';
            if (isset($data->{$selectname}) && $data->{$selectname} == $option) {
                $selected = ' selected="selected"';
            }
            $display = $usegetstring ? get_string($option, 'block_meinekurse') : $option;
            $select .= '<option value="' . $option . '"' . $selected . '>' . $display . '</option>';
        }
        $select .= '</select>';
        return $select;
    }

    /**
     * allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return false;
    }

    /**
     * locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my-index' => true);
    }

    /*
     * Returns a HTML table that has cell IDs that can be hidden
     * @param object $table - similar to the stdClass table sent to html_writer::table,
     *                        only has 'data'
     *                        also with 'colnames' - an array of column names to be used as part of the ID
     */

    private static function table($table) {
        $html = '';
        $html .= '<table style="float: left;" class="generaltable mycourses"><tbody>';
        $rowid = 0;
        foreach ($table->data as $row) {
            $rowid++;
            $html .= '<tr class="r' . $rowid . '">';
            foreach ($table->colnames as $colname) {
                $cell = array_shift($row);
                $html .= '<td id="cell_' . $rowid . '_' . $colname . '">' . $cell . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

}
