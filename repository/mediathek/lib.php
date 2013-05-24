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
 * This plugin is used to access Mediathek
 *
 * @package    repository_mediathek
 * @copyright  2013 Davo Smith, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot.'/repository/mediathek/mediathekapi.php');

/**
 * Mediathek plugin
 *
 * @package    repository_mediathek
 * @copyright  2013 Davo Smith, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_mediathek extends repository {

    static $api = null;
    const EMBED_PREFIX = 'MEDIATHEK_EMBED:';

    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
        parent::__construct($repositoryid, $context, $options);
    }

    public function check_login() {
        return false;
    }

    public function global_search() {
        return false;
    }

    public function print_login() {
        $ret = array();
        $api = self::get_api();

        $mediatypes = self::get_media_types();
        $restrictionlist = self::get_restriction_list();

        if ($this->options['ajax']) {
            // Output the search form
            $ret['login'] = array(
                'searchtext' => (object)array(
                    'label' => get_string('textsearch', 'repository_mediathek'),
                    'id' => 'el_search',
                    'type' => 'text',
                    'name' => 's',
                ),
                'topic' => (object)array(
                    'label' => get_string('topiclist', 'repository_mediathek'),
                    'id' => 'el_topic',
                    'type' => 'select',
                    'name' => 'mediathek_topic',
                    'options' => $api->get_topic_list(true),
                ),
                'level' => (object)array(
                    'label' => get_string('levellist', 'repository_mediathek'),
                    'id' => 'el_level',
                    'type' => 'select',
                    'name' => 'mediathek_level',
                    'options' => $api->get_level_list(true),
                ),
                'sort' => (object)array(
                    'label' => get_string('sort', 'repository_mediathek'),
                    'id' => 'el_sort',
                    'type' => 'select',
                    'name' => 'mediathek_sort',
                    'options' => $api->get_sort_criteria_list(),
                ),
                'mediatype' => (object)array(
                    'label' => get_string('mediatype', 'repository_mediathek'),
                    'id' => 'el_mediatype',
                    'type' => 'select',
                    'name' => 'mediathek_mediatype',
                    'options' => $mediatypes,
                ),
                'restrictions' => (object)array(
                    'label' => get_string('restrictions', 'repository_mediathek'),
                    'id' => 'el_restrictions',
                    'type' => 'select',
                    'name' => 'mediathek_restrictions',
                    'options' => $restrictionlist,
                ),
            );
            $ret['login_btn_label'] = get_string('search');
            $ret['login_btn_action'] = 'search';
        }
        return $ret;
    }

    public function get_listing($path='', $page = '') {
        return $this->print_login();
    }

    protected static function get_api() {
        if (is_null(self::$api)) {
            self::$api = new repository_mediathek_api(repository_mediathek_api::LIST_LABELVALUE);
        }
        return self::$api;
    }

    protected function get_restriction_list() {
        $api = self::get_api();

        $restrictionlist = $api->get_restriction_list();
        if (!empty($this->options['returntypes'])) {
            if (!($this->options['returntypes'] & FILE_EXTERNAL)) {
                foreach ($restrictionlist as $key => $value) {
                    if ($value['value'] != 'onlyCopyContent') {
                        unset($restrictionlist[$key]);
                    }
                }
            }
        }

        return $restrictionlist;
    }

    protected function get_media_types() {
        $api = self::get_api();

        $mediatypes = $api->get_type_list(true);
        if ($this->options['mimetypes'] != '*') {
            // Based on common file extensions - figure out, from the filetypes allowed in the options, which mediatype
            // settings are relevant.
            $typematch = array('image' => array('.jpg', '.gif', '.png'),
                               'video' => array('.3gp', '.avi', '.mov', '.mp4', '.mpeg', '.swf', '.ogv'),
                               'audio' => array('.mp3', '.wav', '.aac', '.ogg'));
            $keepall = true;
            foreach ($mediatypes as $key => $mediatype) {
                if ($mediatype['value'] == 'all') {
                    continue;
                }
                if (array_key_exists($mediatype['value'], $typematch)) {
                    foreach ($typematch[$mediatype['value']] as $extn) {
                        if (in_array($extn, $this->options['mimetypes'])) {
                            // We've found something in the allowed types from the filepicker that matches this mediatype
                            // so leave this mediatype in the list.
                            continue 2;
                        }
                    }
                }
                $keepall = false;
                unset($mediatypes[$key]);
            }
            if (!$keepall) { // Only show 'all' option is all other types are allowed.
                foreach ($mediatypes as $key => $mediatype) {
                    if ($mediatype['value'] == 'all') {
                        unset($mediatypes[$key]);
                    }
                }
            }
        }

        return $mediatypes;
    }

    public function search($search_text, $page = 0) {
        global $OUTPUT, $SESSION;

        $api = new repository_mediathek_api();
        $pagesize = optional_param('pagesize', 20, PARAM_INT);
        $clientid = required_param('client_id', PARAM_TEXT);

        if (!isset($SESSION->repository_mediathek_searches)) {
            $SESSION->repository_mediathek_searches = array();
        }
        $searchparams = array(
            'sort' => null,
            'level' => null,
            'topic' => null,
            'mediatype' => null,
            'search_text' => null
        );

        $params = array('mediathek_sort' => 'sort',
                        'mediathek_level' => 'level',
                        'mediathek_topic' => 'topic',
                        'mediathek_mediatype' => 'mediatype',
                        's' => 'search_text');
        $newsearch = false;
        foreach ($params as $getname => $paramname) {
            $searchparams[$paramname] = optional_param($getname, null, PARAM_TEXT);
            if (!is_null($searchparams[$paramname])) {
                $newsearch = true;
            }
        }
        if ($newsearch) {
            $SESSION->repository_mediathek_searches[$clientid] = serialize($searchparams);
        } else {
            if (isset($SESSION->repository_mediathek_searches[$clientid])) {
                $searchparams = unserialize($SESSION->repository_mediathek_searches[$clientid]);
            }
        }

        if ($page == 0) {
            $page = 1;
        }

        try {
            $results = $api->search_content('searchContent', $pagesize, $page, $searchparams['sort'], 'asc',
                                            $searchparams['search_text'], $searchparams['level'],
                                            $searchparams['topic'], $searchparams['mediatype']);
            $totalresults = $api->get_total_results();
            $pagecount = (int)ceil((float)$totalresults / $pagesize);
            $ret = array(
                'dynload' => true,
                'nosearch' => true,
                'norefresh' => true,
                'nologin' => false,
                'issearchresult' => false,
                'page' => $page,
                'pages' => $pagecount,
                'logouttext' => get_string('backtosearch', 'repository_mediathek'),
                'list' => array(),
            );

            foreach ($results as $result) {
                // Convert the type strings to lower case (to avoid prolems with mixed-case)
                $result->educational_resourcetype = strtolower($result->educational_resourcetype);
                $result->technical_format = strtolower($result->technical_format);
                // Copy the result details into the return array
                $okformat = $this->options['mimetypes'] == '*';
                // Check the file extension is in the knonw list of extensions.
                $okformat = $okformat || in_array(mimeinfo_from_type('extension', $result->technical_format),
                                                  $this->options['mimetypes']);
                if ($okformat) {
                    $extn = mimeinfo_from_type('extension', $result->technical_format);
                    if ($extn == '.jpe') {
                        $extn = '.jpg'; // Replace with the more common version of the extension.
                    } else if ($extn == '.fdf') {
                        $extn = '.pdf'; // Replace with the more common version of the extension.
                    }
                    $item = array(
                        'shorttitle' => $result->general_title_de,
                        'title' => $result->general_title_de.$extn,
                        'source' => $result->technical_location,
                        'returntypes' => FILE_EXTERNAL|FILE_INTERNAL,
                    );
                    if ($result->educational_resourcetype == 'video' || $result->educational_resourcetype == 'audio') {
                        // Prepare the link so that the mediathek filter can convert this into an iframe.
                        $item['source'] = self::EMBED_PREFIX.$item['source'];
                        $item['returntypes'] = FILE_EXTERNAL;
                    }

                    if (!empty($result->technical_thumbnail)) {
                        $item['thumbnail'] = $result->technical_thumbnail;
                    } else {
                        $item['thumbnail'] = $OUTPUT->pix_url(file_mimetype_icon($result->technical_format, 90))->out(false);
                    }
                    if (!empty($result->rights_license)) {
                        $item['license'] = $result->rights_license;
                    }

                    $tooltipinfo = '';
                    $tooltipinfo .= '<b>'.get_string('title', 'repository_mediathek').'</b>: '.
                        self::truncate($result->general_title_de, 40).'<br />';
                    $tooltipinfo .= '<b>'.get_string('resourcetype', 'repository_mediathek').'</b>: '.
                        self::get_typename($result->educational_resourcetype).'<br />';
                    if (!empty($result->rights_license)) {
                        $tooltipinfo .= '<b>'.get_string('license', 'repository_mediathek').'</b>: '.
                            $result->rights_license.'<br />';
                    }
                    if (!empty($result->restrictions)) {
                        $restriction = false;
                        if ($result->restrictions == 'onlyLink') {
                            $item['returntypes'] = FILE_EXTERNAL;
                            $restriction = get_string('onlylink', 'repository_mediathek');
                        }
                        if ($restriction) {
                            $tooltipinfo .= '<b>'.get_string('restrictions', 'repository_mediathek').'</b>: '.
                                $restriction.'<br />';
                        }
                    }
                    if (!empty($result->general_description_de)) {
                        $tooltipinfo .= '<b>'.get_string('description', 'repository_mediathek').'</b>: '.
                            self::truncate($result->general_description_de, 100).'<br />';
                    }
                    $item['tooltip'] = $tooltipinfo;

                    $ret['list'][] = $item;
                }
            }
        } catch (moodle_exception $e) {
            return array('msg' => $e->getMessage());
        }

        return $ret;
    }

    public function logout() {
        return parent::logout();
    }

    public function get_file($url, $filename = '') {
        // Same as the parent function, except that redirects are followed.
        $path = $this->prepare_file($filename);
        $c = new curl;
        $options = array(
            'filepath' => $path,
            'timeout' => self::GETFILE_TIMEOUT,
            'CURLOPT_FOLLOWLOCATION' => true,
            'CURLOPT_MAXREDIRS' => 5,
        );
        $result = $c->download_one($url, null, $options);
        if ($result !== true) {
            throw new moodle_exception('errorwhiledownload', 'repository', '', $result);
        }
        return array('path'=>$path, 'url'=>$url);
    }

    public function supported_filetypes() {
        return '*';
    }

    public function get_link($url) {
        global $DB;

        $embed = false;
        if (substr_compare($url, self::EMBED_PREFIX, 0, strlen(self::EMBED_PREFIX)) == 0) {
            $embed = true;
            $url = substr($url, strlen(self::EMBED_PREFIX));
        }
        if (!$embed) {
            return $url;
        }
        $hash = sha1($url);
        if (!$DB->record_exists('repository_mediathek_link', array('hash' => $hash))) {
            $ins = (object)array(
                'hash' => $hash,
                'url' => $url,
            );
            $DB->insert_record('repository_mediathek_link', $ins);
        }
        $link = new moodle_url('/repository/mediathek/link.php', array('hash' => $hash));
        if ($embed) {
            $link->param('embed', 1);
        }
        return $link->out(false);
    }

    public function supported_returntypes() {
        return FILE_INTERNAL|FILE_EXTERNAL;
    }

    public static function get_type_option_names() {
        return array_merge(parent::get_type_option_names(), array('url', 'username', 'password'));
    }

    public static function type_config_form($mform, $classname = 'repository') {
        parent::type_config_form($mform);

        $config = get_config('repository_mediathek');
        $mform->addElement('text', 'url', get_string('url', 'repository_mediathek'), array('size' => 60));
        if (isset($config->url)) {
            $mform->setDefault('url', $config->url);
        }
        $mform->addElement('text', 'username', get_string('username', 'repository_mediathek'), array('size' => 20));
        if (isset($config->username)) {
            $mform->setDefault('username', $config->username);
        }
        $mform->addElement('text', 'password', get_string('password', 'repository_mediathek'), array('size' => 20));
        if (isset($config->password)) {
            $mform->setDefault('password', $config->password);
        }
        $clearcacheurl = new moodle_url('/repository/mediathek/clearcache.php');
        $clearcache = html_writer::link($clearcacheurl, get_string('clearcache', 'repository_mediathek'), array('target' => '_blank'));
        $clearcache .= ' - '.get_string('clearcachedesc', 'repository_mediathek');
        $mform->addElement('static', 'clearcache', '', $clearcache);
    }

    protected static function truncate($text, $length) {
        if (strlen($text) <= $length) {
            return $text;
        }

        $text = substr($text, 0, $length - 1);
        $text .= '&hellip;';
        return $text;
    }

    protected static function get_typename($type) {
        $api = self::get_api();

        $types = $api->get_type_list();
        if (isset($types[$type])) {
            return $types[$type];
        }

        return $type;
    }

}
