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
 * Class to contain the code for connecting to the Prüfungsarchiv Mediathek repository
 *
 * @package   repository_ppmediathek
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class repository_pmediathek_api {

    /** @var cache_application */
    protected $settings = null;
    protected $url = null;
    protected $username = null;
    protected $password = null;
    protected $logqueries = false;
    protected $listtype = null; // The type of list to return from list functions
    /** @var int|null $totalresults */
    protected $totalresults = null; // The total number of results found by the last search.

    const LIST_KEYVALUE = 'keyvalue';
    const LIST_LABELVALUE = 'labelvalue';

    public function __construct($listtype = self::LIST_KEYVALUE) {
        $this->listtype = $listtype;
        $this->load_settings();
    }

    protected function load_settings() {
        $config = get_config('pmediathek');

        $this->url = isset($config->url) ? trim($config->url) : '';
        $this->username = isset($config->username) ? $config->username : '';
        $this->password = isset($config->password) ? $config->password : '';
        $this->logqueries = isset($config->logqueries) ? $config->logqueries : false;

        $this->settings = cache::make('repository_pmediathek', 'searchoptions');
    }

    protected function return_list($name, $apicall, $includeany) {
        $list = $this->settings->get($name);
        if (!$list) {
            $resp = $this->do_request($apicall);
            $list = $this->parse_response_list($resp);
            $this->settings->set($name, $list);
        }
        return $this->prepare_list($list, $includeany);
    }

    protected function prepare_list($list, $includeany) {
        $anystr = $includeany;
        if ($includeany && !is_string($includeany)) {
            $anystr = get_string('any', 'repository_pmediathek');
        }
        if ($this->listtype == self::LIST_KEYVALUE) {
            if ($includeany) {
                $list = array(null => $anystr) + $list;
            }
            return $list;
        }

        // Convert to array containing 'label' and 'value' fields (for the repository search form).
        $ret = array();
        if ($includeany) {
            $ret[] = array('label' => $anystr, 'value' => null);
        }
        foreach ($list as $key => $value) {
            $ret[] = array('label' => $value, 'value' => $key);
        }
        return $ret;
    }

    public function get_search_mode_list($includeany = false) {
        return $this->return_list('searchmodelist', 'getSearchModeList', $includeany);
    }

    public function get_topic_list($includeany = false) {
        return $this->return_list('topiclist', 'getTopicList', $includeany);
    }

    public function get_level_list($includeany = false) {
        return $this->return_list('levellist', 'getLevelList', $includeany);
    }

    public function get_type_list($includeany = false) {
        return $this->return_list('typelist', 'getTypeList', $includeany);
    }

    public function get_sort_criteria_list($includeany = false) {
        return $this->return_list('sortcriterialist', 'getSortCriteriaList', $includeany);
    }

    public function get_sort_order_list($includeany = false) {
        return $this->return_list('sortorderlist', 'getSortOrderList', $includeany);
    }

    public function get_restriction_list($includeany = false) {
        return $this->return_list('restrictionlist', 'getRstrictionList', $includeany);
    }

    public function get_record_element_list($includeany = false) {
        return $this->return_list('recordelementlist', 'getRecordElementList', $includeany);
    }

    public function get_error_list($includeany = false) {
        return $this->return_list('errorlist', 'getErrorList', $includeany);
    }

    public function get_exam_type_list($includeany = false) {
        return $this->return_list('examtypelist', 'getArchiveExaminationTypeList', $includeany);
    }

    public function get_exam_subject_lists($includeany = false) {
        $result = array();
        $map = $this->load_resource_map();
        foreach ($map->exam->map as $examid => $subjects) {
            $subj = array();
            foreach ($subjects as $subjectid => $unused) {
                $subj[$subjectid] = $map->exam->subjects[$subjectid];
            }
            $result[$examid] = $this->prepare_list($subj, $includeany);
        }
        return $result;
    }

    public function get_exam_year_list($includeany = false) {
        return $this->return_list('examyear', 'getArchiveExamYearList', $includeany);
    }

    public function get_exam_resource_type_list($includeany = false) {
        $map = $this->load_resource_map();
        asort($map->exam->resources);
        return $this->prepare_list($map->exam->resources, $includeany);
    }

    public function get_school_type_list($includeany = false) {
        return $this->return_list('schooltypelist', 'getArchiveTestContextList', $includeany);
    }

    public function get_school_subject_lists($includeany = false) {
        $result = array();
        $map = $this->load_resource_map();
        foreach ($map->school->map as $schoolid => $subjects) {
            $subj = array();
            foreach ($subjects as $subjectid => $unused) {
                $subj[$subjectid] = $map->school->subjects[$subjectid];
            }
            $result[$schoolid] = $this->prepare_list($subj, $includeany);
        }
        return $result;
    }

    public function get_grade_list($includeany = false) {
        return $this->return_list('gradelist', 'getArchiveTestGradeList', $includeany);
    }

    public function get_school_year_list($includeany = false) {
        return $this->return_list('schoolyear', 'getArchiveTestYearList', $includeany);
    }
    public function get_school_resource_type_list($includeany = false) {
        $map = $this->load_resource_map();
        asort($map->school->resources);
        return $this->prepare_list($map->school->resources, $includeany);
    }

    public function get_p_restriction_list($includeany = false) {
        return $this->return_list('prestrictionlist', 'getArchiveRestrictionList', $includeany);
    }

    public function get_tag_list() {
        global $USER;
        $resp = $this->do_request('getTagList', array('userID' => $USER->id));
        return $this->parse_response_list($resp);
    }

    public function get_exam_resource_map($includeany = false) {
        $data = $this->load_resource_map();
        foreach ($data->exam->map as $examid => $subjects) {
            foreach ($subjects as $subjectid => $resources) {
                $data->exam->map[$examid][$subjectid] = $this->prepare_list($data->exam->map[$examid][$subjectid], $includeany);
            }
        }
        return $data->exam->map;
    }

    public function get_school_resource_map($includeany = false) {
        $data = $this->load_resource_map();
        foreach ($data->school->map as $examid => $subjects) {
            foreach ($subjects as $subjectid => $resources) {
                $data->school->map[$examid][$subjectid] = $this->prepare_list($data->school->map[$examid][$subjectid], $includeany);
            }
        }
        return $data->school->map;
    }

    protected function load_resource_map() {
        $map = $this->settings->get('resourcemap');
        if (!$map) {
            $resp = $this->do_request('getArchiveValueList');
            $map = $this->parse_response_map($resp);
            $this->settings->set('resourcemap', $map);
        }
        return $map;
    }


    /**
     * Perform the search on the Mediathek server
     * @param string $mode
     * @param int $pagesize
     * @param int $page
     * @param string $sortparam
     * @param string $sortorder
     * @param string $text
     * @param string $levels optional
     * @param string $topics optional
     * @param string $types optional
     * @param string $restrictions optional
     * @return array stdClass
     */
    public function search_content($mode, $pagesize, $page, $sortparam, $sortorder, $text, $levels = null,
                                   $topics = null, $types = null, $restrictions = null) {
        global $USER;
        $fields = array(
            'userID' => $USER->id,
            'searchMode' => $mode,
            'numberOfItemsPerPage' => $pagesize,
            'currentPageRequired' => $page,
            'sortParameter' => $sortparam,
            'sortOrder' => $sortorder,
            'searchText' => $text,
        );
        if (!empty($levels)) {
            $fields['searchLevels'] = $levels;
        }
        if (!empty($topics)) {
            $fields['searchTopics'] = $topics;
        }
        if (!empty($types)) {
            $fields['searchTypes'] = $types;
        }
        if (!empty($restrictions)) {
            $fields['searchRestrictions'] = $restrictions;
        }
        $resp = $this->do_request('searchContent', $fields);
        return $this->parse_response_files($resp);
    }

    public function search_exam_content($pagesize, $page, $type, $subject, $text = null, $year = null,
                                        $resourcetype = null, $restrictions = null) {
        global $USER;

        if (is_null($subject)) {
            throw new moodle_exception('missingsubject', 'repository_pmediathek');
        }

        $fields = array(
            'userID' => $USER->id,
            'numberOfItemsPerPage' => $pagesize,
            'currentPageRequired' => $page + 1,
            'searchArchiveExamType' => $type,
            'searchArchiveSubject' => $subject
        );
        if (!empty($text)) {
            $fields['searchText'] = $text;
        }
        if (!empty($year)) {
            $fields['searchArchiveYear'] = $year;
        }
        if (!empty($resourcetype)) {
            $fields['searchArchiveLRT']  = $resourcetype;
        }
        if (!empty($restrictions)) {
            $fields['searchArchiveRestrictions'] = $restrictions;
        }
        $resp = $this->do_request('searchArchiveExamContent', $fields);
        return $this->parse_response_files($resp);
    }

    public function search_school_content($pagesize, $page, $type, $subject, $text = null, $grade = null,
                                          $year = null, $resourcetype = null, $restrictions = null) {
        global $USER;
        $fields = array(
            'userID' => $USER->id,
            'numberOfItemsPerPage' => $pagesize,
            'currentPageRequired' => $page + 1,
            'searchArchiveTestContext' => $type,
            'searchArchiveSubject' => $subject
        );
        if (!empty($text)) {
            $fields['searchText'] = $text;
        }
        if (!empty($grade)) {
            $fields['searchArchiveTestGrade'] = $grade;
        }
        if (!empty($year)) {
            $fields['searchArchiveYear'] = $year;
        }
        if (!empty($resourcetype)) {
            $fields['searchArchiveLRT']  = $resourcetype;
        }
        if (!empty($restrictions)) {
            $fields['searchArchiveRestrictions'] = $restrictions;
        }
        $resp = $this->do_request('searchArchiveTestContent', $fields);
        return $this->parse_response_files($resp);
    }

    /**
     * Return the number of results found by the last search
     * @return int|null
     */
    public function get_total_results() {
        return $this->totalresults;
    }

    /**
     * Confirm that the embed URL matches the configured Mediathek server.
     * @param string $url
     * @return bool
     */
    public function check_embed_url($url) {
        $apiurl = parse_url($this->url);
        $testurl = parse_url($url);

        if ($apiurl['host'] == $testurl['host']) {
            return true; // Direct link to Mediathek server.
        }

        $localurl = parse_url(new moodle_url('/repository/pmediathek/link.php'));
        if ($testurl['host'] == $localurl['host'] && $testurl['path'] == $localurl['path']) {
            return true; // Indirect link via local URL.
        }

        return false;
    }

    protected function do_request($query, $extrafields = array()) {
        $url = $this->url;
        $url .= '?query='.$query;
        foreach ($extrafields as $field => $value) {
            $url .= '&'.urlencode($field).'='.urlencode($value);
        }

        if ($this->logqueries) {
            global $CFG;
            $fp = fopen($CFG->dataroot.'/mediathek.log', 'a');
            fwrite($fp, date('j M Y H:i:s').' - '.$url."\n");
        }

        $c = curl_init($url);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);

        // Set up username / password for connection
        curl_setopt($c, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($c, CURLOPT_USERPWD, $this->username.':'.$this->password);

        $this->add_proxy_settings($c, $url);

        if (($res = curl_exec($c)) == false) {
            throw new moodle_exception('errorconnecting', 'repository_pmediathek', '', curl_error($c));
        }
        $httpcode = curl_getinfo($c, CURLINFO_HTTP_CODE);
        if ($httpcode != 200) {
            preg_match('|\<body\>(.*)\</body\>|si', $res, $matches);
            if (!empty($matches[1])) {
                $errmsg = format_string($matches[1]);
            } else {
                $errmsg = "HTTP code: $httpcode";
            }
            throw new moodle_exception('errorconnecting', 'repository_pmediathek', '', $errmsg);
        }

        if ($this->logqueries) {
            fwrite($fp, $res);
            fwrite($fp, "\n\n=======================================================\n\n\n");
            fclose($fp);
        }

        // Strip off any warning messages + other junk data output by the Mediathek server before the XML starts.
        $xmlstart = strpos($res, '<?xml');
        $res = substr($res, $xmlstart);

        try {
            $response = new SimpleXMLElement($res);
        } catch (exception $e) {
            throw new moodle_exception('errorparseresponse', 'repository_pmediathek');
        }

        if (!empty($response->responseStatus->code)) {
            throw new moodle_exception('errorserver', 'repository_pmediathek', '', (string)$response->responseStatus->description);
        }

        if (!empty($response->searchResultParameters->totalNumberOfItems)) {
            $this->totalresults = intval($response->searchResultParameters->totalNumberOfItems);
        } else {
            $this->totalresults = 0;
        }

        if (!empty($response->archiveTypes)) {
            return $response->archiveTypes; // Returned by getArchiveValueList.
        }
        return $response->items;
    }

    protected function add_proxy_settings($c, $url) {
        global $CFG;

        $proxybypass = is_proxybypass($url);
        if (!empty($CFG->proxyhost) and !$proxybypass) {
            // SOCKS supported in PHP5 only
            if (!empty($CFG->proxytype) and ($CFG->proxytype == 'SOCKS5')) {
                if (defined('CURLPROXY_SOCKS5')) {
                    curl_setopt($c, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                } else {
                    debugging("SOCKS5 proxy is not supported in PHP4.", DEBUG_ALL);
                    return;
                }
            }

            curl_setopt($c, CURLOPT_HTTPPROXYTUNNEL, false);

            if (empty($CFG->proxyport)) {
                curl_setopt($c, CURLOPT_PROXY, $CFG->proxyhost);
            } else {
                curl_setopt($c, CURLOPT_PROXY, $CFG->proxyhost.':'.$CFG->proxyport);
            }

            if (!empty($CFG->proxyuser) and !empty($CFG->proxypassword)) {
                curl_setopt($c, CURLOPT_PROXYUSERPWD, $CFG->proxyuser.':'.$CFG->proxypassword);
                if (defined('CURLOPT_PROXYAUTH')) {
                    // any proxy authentication if PHP 5.1
                    curl_setopt($c, CURLOPT_PROXYAUTH, CURLAUTH_BASIC | CURLAUTH_NTLM);
                }
            }
        }
    }

    protected function parse_response_list(SimpleXMLElement $items) {
        $ret = array();
        foreach ($items->item as $item) {
            if (empty($item->value) || empty($item->description)) {
                continue;
            }
            $ret[(string)$item->value] = (string)$item->description;
        }
        return $ret;
    }

    protected function parse_response_files(SimpleXMLElement $items) {
        $ret = array();
        foreach ($items->item as $item) {
            $file = new stdClass();
            foreach ($item->element as $element) {
                $fieldname = (string)$element->field;
                $value = (string)$element->value;
                $file->{$fieldname} = $value;
            }
            $ret[] = $file;
        }

        return $ret;
    }

    protected function parse_response_map(SimpleXMLElement $resp) {
        $ret = (object)array(
            'exam' => null,
            'school' => null,
        );

        foreach ($resp->archiveType as $archivetype) {
            $attrib = $archivetype->attributes();
            if ((string)$attrib['id'] == 'examinations') {
                $ret->exam = $this->parse_response_examination_type($archivetype->examinationType);
            } else if ((string)$attrib['id'] == 'annualTest') {
                $ret->school = $this->parse_response_examination_type($archivetype->examinationType);
            }
        }

        return $ret;
    }

    /**
     * @param SimpleXMLElement[] $examtypes
     * @return object
     */
    protected function parse_response_examination_type($examtypes) {
        $ret = (object)array(
            'exams' => array(),
            'subjects' => array(),
            'resources' => array(),
            'map' => array(),
        );

        foreach ($examtypes as $examtype) {
            $examid = (string)($examtype->attributes()['id']);
            $examname = (string)($examtype->attributes()['label']);
            $ret->exams[$examid] = $examname;
            $subjmap = array();
            foreach ($examtype->subject as $subject) {
                $subjid = (string)($subject->attributes()['id']);
                $subjname = (string)($subject->attributes()['label']);
                $ret->subjects[$subjid] = $subjname;
                $resourcemap = array();
                foreach ($subject->resourcetype as $resource) {
                    $resourceid = (string)($resource->attributes()['id']);
                    $resourcename = (string)($resource->attributes()['label']);
                    $ret->resources[$resourceid] = $resourcename;
                    $resourcemap[$resourceid] = $resourcename;
                }
                $subjmap[$subjid] = $resourcemap;
            }
            $ret->map[$examid] = $subjmap;
        }

        return $ret;
    }
}