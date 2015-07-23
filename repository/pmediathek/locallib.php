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
require_once($CFG->dirroot.'/repository/lib.php');

/**
 * Class repository_pmediathek_search
 */
class repository_pmediathek_search {
    /** @var \context */
    protected $context;
    /** @var int */
    protected $returntypes;
    /** @var string[] */
    protected $filetypes;
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
    /** @var string */
    protected $viewname = null;
    /** @var string */
    protected $viewurl = null;

    /** @var array */
    protected static $validsearchparams = array('searchtab', 'examtype', 'subject', 'year', 'type', 'school', 'grade');
    protected static $api = null;

    const TAB_EXAM = 'exam';
    const TAB_SCHOOL = 'school';
    const DEFAULT_PER_PAGE = 10;

    const ACTION_FORM = 'form';
    const ACTION_SEARCH = 'search';
    const ACTION_VIEW = 'view';

    const INSERT_NO_FILETYPE = -1;
    const INSERT_NO = 0;
    const INSERT_LINK = 1;
    const INSERT_ANY = 2;

    /**
     * @param context $context
     * @param $returntypes
     * @param $filetypes
     */
    public function __construct(context $context, $returntypes, $filetypes) {
        $this->context = $context;
        $this->returntypes = $returntypes;

        $this->filetypes = explode(',', $filetypes);
        if (in_array('*', $this->filetypes)) {
            $this->filetypes = array('*');
        }
    }

    protected function any_filetypes() {
        return in_array('*', $this->filetypes);
    }

    protected function filetypes_param() {
        return implode(',', $this->filetypes);
    }

    /**
     * Initialise the search parameters and (if needed) the form.
     */
    public function process() {
        global $PAGE;

        $viewurl = null;
        $viewname = null;
        $token = null;
        if (optional_param('search', false, PARAM_BOOL)) {
            $this->action = self::ACTION_SEARCH;
        } else if ($viewurl = optional_param('viewurl', null, PARAM_URL)) {
            $this->action = self::ACTION_VIEW;
            $viewname = required_param('viewname', PARAM_TEXT);
            $token = required_param('token', PARAM_ALPHANUM);
        } else {
            $this->action = self::ACTION_FORM;
        }

        $this->set_search_params();

        switch ($this->action) {
            case self::ACTION_SEARCH:
                $this->search();
                break;

            case self::ACTION_VIEW:
                $this->set_view_details($viewurl, $viewname, $token);
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
                $formdata['returntypes'] = $this->returntypes;
                $formdata['filetypes'] = $this->filetypes_param();
                $this->searchform->set_data($formdata);
                if ($data = $this->searchform->get_data()) {
                    $redir = new moodle_url($PAGE->url, array('search' => 1));
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
                $out .= $this->output_view();
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
        $url->param('page', $this->page);
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

    protected function set_view_details($viewurl, $viewname, $token) {
        global $USER;

        if (empty($viewurl) || empty($viewname)) {
            throw new moodle_exception('missingviewparams', 'repository_pmediathek');
        }

        $api = $this->get_api();
        if (!$api->check_embed_url($viewurl, $token)) {
            throw new moodle_exception('invalidurl', 'repository_pmediathek');
        }

        $this->viewurl = $viewurl.'&mode=display&user='.$USER->username;
        $this->viewname = $viewname;
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
                                                       $searchparams['subject'], null, null,
                                                       $searchparams['type']);
        } else {
            $this->results = $api->search_school_content($this->perpage, $this->page, $searchparams['school'],
                                                       $searchparams['subject'], null, $searchparams['grade'],
                                                       $searchparams['year'], $searchparams['type']);
        }
        $this->totalresults = $api->get_total_results();
        $this->cache_result_rights();
    }

    protected function cache_result_rights() {
        $cache = cache::make('repository_pmediathek', 'filerights');
        foreach ($this->results as $result) {
            $saved = $cache->get($result->technical_location);
            if ($saved !== $result->rights_license) {
                $cache->set($result->technical_location, $result->rights_license);
            }
        }
    }

    public static function get_rights_licence($url) {
        $cache = cache::make('repository_pmediathek', 'filerights');
        $rights = $cache->get($url);
        if ($rights !== false) {
            return $rights;
        }
        return 'no copy'; // Bad fallback, but there is no API for getting this data about a single file.
    }

    protected function output_back_link($search = false) {
        $url = $this->get_url($search);
        $strident = $search ? 'backtosearch' : 'backtosearchform';
        return html_writer::link($url, get_string($strident, 'repository_pmediathek'));
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
        global $PAGE;

        $out = '';

        if (!$this->results) {
            return get_string('noresults', 'repository_pmediathek');
        }

        foreach ($this->results as $result) {
            $out .= $this->output_results_item($result);
        }

        $PAGE->requires->yui_module('moodle-repository_pmediathek-results', 'M.repository_pmediathek.results.init',
                                    null, null, true);
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
        return $type;
    }

    protected function output_result_icon($result) {
        $alt = $this->get_type_name($result->educational_resourcetype);
        return html_writer::empty_tag('img', array('src' => $result->technical_thumbnail, 'alt' => $alt,
                                                  'title' => $alt, 'class' => 'resourceicon'));
    }

    protected function output_result_heading($result) {
        $out = html_writer::tag('div', format_string($result->general_title_de), array('class' => 'resultheading'));
        $out .= html_writer::tag('div', $result->technical_size, array('class' => 'filesize'));
        if (!empty($result->technical_duration)) {
            $out .= html_writer::tag('div', get_string('duration', 'repository_pmediathek', $result->technical_duration),
                                     array('class' => 'duration'));
        }
        return html_writer::tag('div', $out, array('class' => 'basicdata'));
    }

    protected function can_view($result) {
        $license = trim($result->rights_license);
        return ($license != 'blocked' && $license != 'no show');
    }

    protected function can_insert($result) {
        $ret = self::INSERT_NO;
        switch (trim($result->rights_license)) {
            case 'public':
                $ret = self::INSERT_ANY;
                break;

            case 'no copy':
                $ret = self::INSERT_LINK;
                break;
        }

        if (!($this->returntypes & FILE_EXTERNAL)) {
            if ($ret == self::INSERT_LINK) {
                $ret = self::INSERT_NO;
            }
        }

        if ($ret !== self::INSERT_NO && !$this->any_filetypes()) {
            $extn = mimeinfo_from_type('extension', $result->technical_format);
            if (!in_array($extn, $this->filetypes)) {
                $ret = self::INSERT_NO_FILETYPE;
            }
        }

        return $ret;
    }

    protected function output_result_actions($result) {
        global $OUTPUT;

        $actions = array();

        if ($this->can_view($result)) {
            $viewurl = $this->get_url();
            $viewurl->param('viewurl', $result->technical_location);
            $viewurl->param('viewname', $result->general_title_de);
            $viewurl->param('token', $this->get_api()->generate_token($result->technical_location));
            $viewstr = $OUTPUT->pix_icon('t/preview', '');
            $viewstr .= ' '.get_string('view', 'repository_pmediathek');
            $actions[] = html_writer::link($viewurl, $viewstr, array('class' => 'viewlink'));
        } else {
            $viewstr = $OUTPUT->help_icon('cannotview', 'repository_pmediathek');
            $viewstr .= ' '.get_string('cannotview', 'repository_pmediathek');
            $actions[] = $viewstr;
        }

        if ($this->can_insert($result) > self::INSERT_NO) {
            $extn = '';
            if (!$this->any_filetypes()) {
                $extn = mimeinfo_from_type('extension', $result->technical_format);
            }
            $params = array(
                'title' => $result->general_title_de.$extn,
                'source' => $result->technical_location,
                'thumbnail' => $result->technical_thumbnail,
                'author' => '',
            );
            if (isset($result->technical_size)) {
                $params['size'] = $result->technical_size;
            }
            if ($this->can_insert($result) == self::INSERT_LINK) {
                $params['returntypes'] = FILE_EXTERNAL;
            } else {
                $params['returntypes'] = FILE_EXTERNAL|FILE_INTERNAL;
            }
            $inserturl = http_build_query($params);
            $inserturl = '?'.$inserturl;

            $insertstr = $OUTPUT->pix_icon('t/approve', '');
            $insertstr .= ' '.get_string('insert', 'repository_pmediathek');

            $actions[] = html_writer::link($inserturl, $insertstr, array('class' => 'insertlink'));
        } else {
            if ($this->can_view($result)) {
                if ($this->can_insert($result) == self::INSERT_NO) {
                    $insertstr = $OUTPUT->help_icon('cannotinsert', 'repository_pmediathek');
                } else { // INSERT_NO_FILETYPE.
                    $insertstr = $OUTPUT->help_icon('cannotinsert2', 'repository_pmediathek');
                }
                $insertstr .= ' '.get_string('cannotinsert', 'repository_pmediathek');
                $actions[] = $insertstr;
            }
        }

        $out = implode(html_writer::empty_tag('br'), $actions);

        return html_writer::tag('div', $out, array('class' => 'actions'));
    }

    protected function output_result_details($result) {
        $out = '';
        $out .= html_writer::tag('div', get_string('identifier', 'repository_pmediathek', $result->general_identifier),
                                 array('class' => 'identifier'));
        if (!empty($result->general_description_de)) {
            $out .= html_writer::tag('div', format_string($result->general_description_de), array('class' => 'description'));
        }
        if (!empty($result->educational_typicalagerangemin)) {
            $out .= html_writer::tag('div', get_string('typicalage', 'repository_pmediathek',
                                                       $result->educational_typicalagerangemin),
                                     array('class' => 'typicalage'));
        }
        if (!empty($result->rights_licence_description)) {
            $out .= html_writer::tag('div', $result->rights_license_description, array('class' => 'rightsdesc'));
        }
        $out .= html_writer::empty_tag('span', array('class' => 'clearer'));
        return html_writer::tag('div', $out, array('class' => 'details'));
    }

    protected function output_view() {
        $out = '';

        $out .= $this->output_back_link(true);
        $out .= html_writer::tag('div', format_string($this->viewname));
        $out .= html_writer::empty_tag('iframe', array('src' => $this->viewurl, 'class' => 'preview'));

        return $out;
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
        $mform->addElement('hidden', 'returntypes');
        $mform->setType('returntypes', PARAM_INT);
        $mform->addElement('hidden', 'filetypes');
        $mform->setType('filetypes', PARAM_RAW);

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
        $resourcemap = $api->get_exam_resource_map(true);

        $mform->addElement('select', 'examtype', get_string('examtype', 'repository_pmediathek'), $examtypes);
        $mform->addElement('select', 'subject', get_string('subject', 'repository_pmediathek'), $allsubjects);
        //$mform->addElement('select', 'year', get_string('year', 'repository_pmediathek'), $years);
        $mform->addElement('select', 'type', get_string('type', 'repository_pmediathek'), $types);

        $this->add_action_buttons(false, get_string('search'));

        $options = array(
            'subjects' => $examsubjects,
            'resourcemap' => $resourcemap,
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
        $mform->addElement('hidden', 'returntypes');
        $mform->setType('returntypes', PARAM_INT);
        $mform->addElement('hidden', 'filetypes');
        $mform->setType('filetypes', PARAM_RAW);

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
        $resourcemap = $api->get_school_resource_map(true);

        $mform->addElement('select', 'school', get_string('school', 'repository_pmediathek'), $schools);
        $mform->addElement('select', 'subject', get_string('subject', 'repository_pmediathek'), $allsubjects);
        $mform->addElement('select', 'grade', get_string('grade', 'repository_pmediathek'), $grades);
        $mform->addElement('select', 'year', get_string('year', 'repository_pmediathek'), $years);
        $mform->addElement('select', 'type', get_string('type', 'repository_pmediathek'), $types);

        $this->add_action_buttons(false, get_string('search'));

        $options = array(
            'subjects' => $schoolsubjects,
            'resourcemap' => $resourcemap,
        );
        $PAGE->requires->yui_module('moodle-repository_pmediathek-searchform', 'M.repository_pmediathek.searchform.init',
                                    array($options), null, true);
    }
}