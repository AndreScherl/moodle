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
 * Internal functions used by the search page of Prüfungsarchiv Mediathek
 *
 * @package   repository_pmediathek
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/repository/pmediathek/mediathekapi.php');

/**
 * Class repository_pmediathek_search
 */
class repository_pmediathek_search {
    /** @var \context */
    protected $context;
    /** @var string */
    protected $action = self::ACTION_FORM;
    /** @var array */
    protected $searchparams = array();
    /** @var moodleform */
    protected $searchform = null;
    /** @var int */
    protected $page = 0;
    /** @var int */
    protected $perpage = self::DEFAULT_PER_PAGE;
    /** @var int */
    protected $totalresults = 0;
    /** @var array */
    protected $results = array();

    /** @var array */
    protected static $validsearchparams = array('searchtab', 'examtype', 'subject', 'year', 'type', 'school', 'grade');
    protected static $api = null;

    const TAB_EXAM = 'exam';
    const TAB_SCHOOL = 'school';
    const DEFAULT_PER_PAGE = 10;

    const ACTION_FORM = 'form';
    const ACTION_SEARCH = 'search';
    const ACTION_VIEW = 'view';
    const ACTION_INSERT = 'insert';

    /**
     * @param context $context
     */
    public function __construct(context $context) {
        $this->context = $context;
    }

    /**
     * Initialise the search parameters and (if needed) the form.
     */
    public function process() {
        if (optional_param('search', false, PARAM_BOOL)) {
            $this->action = self::ACTION_SEARCH;
        } else if ($view = optional_param('view', null, PARAM_RAW)) {
            $this->action = self::ACTION_VIEW;
        } else if ($insert = optional_param('insert', null, PARAM_RAW)) {
            $this->action = self::ACTION_INSERT;
        } else {
            $this->action = self::ACTION_FORM;
        }

        $this->set_search_params();

        switch ($this->action) {
            case self::ACTION_SEARCH:
                $this->search();
                break;

            case self::ACTION_VIEW:
                break;

            case self::ACTION_INSERT:
                break;

            case self::ACTION_FORM:
            default:
                if ($this->get_tab() == self::TAB_EXAM) {
                    $this->searchform = new repository_pmediathek_exam_search_form();
                } else {
                    $this->searchform = new repository_pmediathek_school_search_form();
                }

                $formdata = $this->searchparams;
                $formdata['contextid'] = $this->context->id;
                $this->searchform->set_data($formdata);
                if ($data = $this->searchform->get_data()) {
                    $redir = new moodle_url('/repository/pmediathek/search.php', array('contextid' => $this->context->id,
                                                                                      'search' => 1));
                    foreach (self::$validsearchparams as $validparam) {
                        if (!empty($data->$validparam)) {
                            $redir->param($validparam, $data->$validparam);
                        }
                    }
                    redirect($redir);
                }
                break;
        }
    }

    /**
     * Ouptut the search form / results (as appropriate).
     *
     * @return string
     */
    public function output() {
        $out = '';
        switch ($this->action) {
            case self::ACTION_SEARCH:
                $out .= $this->output_results();
                break;

            case self::ACTION_VIEW:
                echo "viewing the resource";
                break;

            case self::ACTION_INSERT:
                echo "Insert the resource";
                break;

            case self::ACTION_FORM:
            default:
                $out .= $this->output_tabs();
                $out .= $this->output_form();
                break;
        }

        return $out;
    }

    protected function get_url($search = false) {
        global $PAGE;

        $url = new moodle_url($PAGE->url, $this->searchparams);
        if ($search) {
            $url->param('search', 1);
        }
        return $url;
    }

    protected static function get_api() {
        if (is_null(self::$api)) {
            self::$api = new repository_pmediathek_api();
        }
        return self::$api;
    }

    /**
     * Gather the search parameters specified via the URL.
     */
    protected function set_search_params() {
        $this->page = optional_param('page', 0, PARAM_INT);
        $this->perpage = optional_param('perpage', self::DEFAULT_PER_PAGE, PARAM_INT);

        foreach (self::$validsearchparams as $validsearch) {
            $value = optional_param($validsearch, null, PARAM_TEXT);
            if (!is_null($value)) {
                $this->searchparams[$validsearch] = $value;
            }
        }

        if (!isset($this->searchparams['searchtab']) || $this->searchparams['searchtab'] != self::TAB_SCHOOL) {
            $this->searchparams['searchtab'] = self::TAB_EXAM;
        }
    }

    protected function get_tab() {
        if (empty($this->searchparams['searchtab'])) {
            throw new coding_exception("pmediathek: must not call 'get_tab' before calling 'process'");
        }
        return $this->searchparams['searchtab'];
    }

    protected function output_results() {
        $out = '';

        $out .= $this->output_back_link();
        $out .= $this->output_paging();
        $out .= $this->output_results_page();

        return $out;
    }

    protected function output_tabs() {
        global $PAGE;

        $out = '';
        $out .= html_writer::tag('p', get_string('searchintro', 'repository_pmediathek'), array('class' => 'intro'));

        $tabexamurl = new moodle_url($PAGE->url, array('searchtab' => self::TAB_EXAM));
        $tabschoolurl = new moodle_url($tabexamurl, array('searchtab' => self::TAB_SCHOOL));

        $tabs = array(
            new tabobject(self::TAB_EXAM, $tabexamurl, get_string('tabexam', 'repository_pmediathek')),
            new tabobject(self::TAB_SCHOOL, $tabschoolurl, get_string('tabschool', 'repository_pmediathek')),
        );

        $out .= print_tabs(array($tabs), $this->get_tab(), null, null, true);

        return $out;
    }

    protected function output_form() {
        if (!$this->searchform) {
            throw new coding_exception("pmediathek: search form not defined - should not be calling 'output_form'");
        }
        ob_start();
        $this->searchform->display();
        return ob_get_clean();
    }

    protected function search() {
        $api = self::get_api();

        // Make sure all params exist (even if null).
        $searchparams = $this->searchparams;
        foreach (self::$validsearchparams as $validparam) {
            if (!isset($searchparams[$validparam])) {
                $searchparams[$validparam] = null;
            }
        }

        // Perform the correct search.
        if ($this->get_tab() == self::TAB_EXAM) {
            $this->results = $api->search_exam_content($this->perpage, $this->page, $searchparams['examtype'],
                                                       $searchparams['subject'], null, $searchparams['year'],
                                                       $searchparams['type']);
        } else {
            $this->results = $api->search_school_content($this->perpage, $this->page, $searchparams['school'],
                                                       $searchparams['subject'], null, $searchparams['grade'],
                                                       $searchparams['year'], $searchparams['type']);
        }
        $this->totalresults = $api->get_total_results();
    }

    protected function output_back_link() {
        $url = $this->get_url();
        return html_writer::link($url, get_string('backtosearch', 'repository_pmediathek'));
    }

    protected function output_paging() {
        global $OUTPUT;

        $baseurl = $this->get_url(true);
        if ($this->perpage !== self::DEFAULT_PER_PAGE) {
            $baseurl->param('perpage', $this->perpage);
        }
        return $OUTPUT->paging_bar($this->totalresults, $this->page, $this->perpage, $baseurl);
    }

    protected function output_results_page() {
        $out = '';

        if (!$this->results) {
            return get_string('noresults', 'repository_pmediathek');
        }

        foreach ($this->results as $result) {
            $out .= $this->output_results_item($result);
        }

        return $out;
    }

    protected function output_results_item($result) {
        $out = '';

        $out .= $this->output_result_icon($result);
        $out .= $this->output_result_heading($result);
        $out .= $this->output_result_actions($result);
        $out .= html_writer::empty_tag('br', array('class' => 'clearer'));
        $out .= $this->output_result_details($result);

        return html_writer::tag('div', $out, array('class' => 'searchresult'));
    }

    protected function get_type_name($type) {
        return get_string('type_'.$type, 'repository_pmediathek');
    }

    protected function output_result_icon($result) {
        $alt = $this->get_type_name($result->educational_resourcetype);
        return html_writer::empty_tag('img', array('src' => $result->technical_thumbnail, 'alt' => $alt,
                                                  'class' => 'resourceicon'));
    }

    protected function output_result_heading($result) {
        $out = format_string($result->general_title_de);
        $out .= html_writer::empty_tag('br');
        $out .= format_string($result->technical_size);
        return html_writer::tag('div', $out, array('class' => 'resultheading'));
    }

    protected function output_result_actions($result) {
        global $OUTPUT;

        $viewurl = $this->get_url();
        $viewurl->param('view', urlencode($result->technical_location));
        $params = array(
            'title' => $result->general_title_de,
            'source' => $result->technical_location,
            'thumbnail' => $result->technical_thumbnail,
            'author' => '',
            'license' => $result->rights_license,
        );
        $inserturl = http_build_query($params);

        $viewstr = $OUTPUT->pix_icon('t/preview', '');
        $viewstr .= ' '.get_string('view', 'repository_pmediathek');
        $insertstr = $OUTPUT->pix_icon('t/approve', '');
        $insertstr .= ' '.get_string('insert', 'repository_pmediathek');

        $out = '';
        $out .= html_writer::link($viewurl, $viewstr);
        $out .= html_writer::empty_tag('br');
        $out .= html_writer::link($inserturl, $insertstr);
        return html_writer::tag('div', $out, array('class' => 'actions'));
    }

    protected function output_result_details($result) {
        $out = 'The details';
        return html_writer::tag('div', $out, array('class' => 'details'));
    }

}

/**
 * Class repository_pmediathek_exam_search_form
 */
class repository_pmediathek_exam_search_form extends moodleform {
    public function definition() {
        global $PAGE;

        $mform = $this->_form;
        $mform->addElement('hidden', 'contextid');
        $mform->setType('contextid', PARAM_INT);
        $mform->addElement('hidden', 'searchtab', repository_pmediathek_search::TAB_EXAM);
        $mform->setType('searchtab', PARAM_ALPHA);

        $api = new repository_pmediathek_api();

        $examtypes = $api->get_exam_type_list(get_string('noselection', 'repository_pmediathek'));
        $examsubjects = $api->get_exam_subject_lists();
        $allsubjects = array();
        foreach ($examsubjects as $exam => $subjects) {
            foreach ($subjects as $id => $subject) {
                $allsubjects[$id] = $subject; // Show all subjects in the list (otherwise it will not validate).
            }
        }
        $years = $api->get_exam_year_list(true);
        $types = $api->get_exam_resource_type_list(true);

        $mform->addElement('select', 'examtype', get_string('examtype', 'repository_pmediathek'), $examtypes);
        $mform->addElement('select', 'subject', get_string('subject', 'repository_pmediathek'), $allsubjects);
        $mform->addElement('select', 'year', get_string('year', 'repository_pmediathek'), $years);
        $mform->addElement('select', 'type', get_string('type', 'repository_pmediathek'), $types);

        $this->add_action_buttons(false, get_string('search'));

        $options = array(
            'subjects' => $examsubjects,
        );
        $PAGE->requires->yui_module('moodle-repository_pmediathek-searchform', 'M.repository_pmediathek.searchform.init',
                                    array($options), null, true);
    }
}

/**
 * Class repository_pmediathek_school_search_form
 */
class repository_pmediathek_school_search_form extends moodleform {
    public function definition() {
        global $PAGE;

        $mform = $this->_form;
        $mform->addElement('hidden', 'contextid');
        $mform->setType('contextid', PARAM_INT);
        $mform->addElement('hidden', 'searchtab', repository_pmediathek_search::TAB_SCHOOL);
        $mform->setType('searchtab', PARAM_ALPHA);

        $api = new repository_pmediathek_api();

        $schools = $api->get_school_type_list(get_string('noselection', 'repository_pmediathek'));
        $schoolsubjects = $api->get_school_subject_lists();
        $allsubjects = array();
        foreach ($schoolsubjects as $school => $subjects) {
            foreach ($subjects as $id => $subject) {
                $allsubjects[$id] = $subject; // Show all subjects in the list (otherwise it will not validate).
            }
        }
        $grades = $api->get_grade_list(true);
        $years = $api->get_school_year_list(true);
        $types = $api->get_school_resource_type_list(true);

        $mform->addElement('select', 'school', get_string('school', 'repository_pmediathek'), $schools);
        $mform->addElement('select', 'subject', get_string('subject', 'repository_pmediathek'), $allsubjects);
        $mform->addElement('select', 'grade', get_string('grade', 'repository_pmediathek'), $grades);
        $mform->addElement('select', 'year', get_string('year', 'repository_pmediathek'), $years);
        $mform->addElement('select', 'type', get_string('type', 'repository_pmediathek'), $types);

        $this->add_action_buttons(false, get_string('search'));

        $options = array(
            'subjects' => $schoolsubjects,
        );
        $PAGE->requires->yui_module('moodle-repository_pmediathek-searchform', 'M.repository_pmediathek.searchform.init',
                                    array($options), null, true);
    }
}