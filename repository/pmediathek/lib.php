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
 * This plugin is used to access Prüfungsarchiv Mediathek
 *
 * @package    repository_pmediathek
 * @copyright  2013 Davo Smith, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot.'/repository/mediathek/mediathekapi.php');

/**
 * Prüfungsarchiv Mediathek plugin
 *
 * @package    repository_pmediathek
 * @copyright  2013 Davo Smith, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_pmediathek extends repository {
    const EMBED_PREFIX = 'PMEDIATHEK_EMBED:';

    public function check_login() {
        return false;
    }

    public function global_search() {
        return false;
    }

    public function print_login() {
        return $this->get_listing();
    }

    public function get_listing($path='', $page = '') {
        $url = new moodle_url('/repository/pmediathek/search.php', array('contextid' => $this->context->id,
                                                                        'returntypes' => $this->options['returntypes'],
                                                                        'filetypes' => $this->get_filetypes()));
        $ret = array(
            'nologin' => false,
            'logouttext' => get_string('newsearch', 'repository_pmediathek'),
            'nosearch' => true,
            'norefresh' => true,
            'object' => array(
                'type' => 'text/html',
                'src' => $url->out(false),
            ),
        );
        return $ret;
    }

    protected function get_filetypes() {
        if (!is_array($this->options['mimetypes'])) {
            return $this->options['mimetypes'];
        }
        return implode(',', $this->options['mimetypes']);
    }


    public function search($searchtext, $page = 0) {
        return $this->get_listing();
    }

    public function get_file($url, $filename = '') {
        global $USER, $CFG;

        require_once($CFG->dirroot.'/repository/pmediathek/locallib.php');
        $rights = repository_pmediathek_search::get_rights_licence($url);
        if ($rights != 'public') {
            throw new moodle_exception('notalloweddownload', 'repository_pmediathek');
        }

        $config = get_config('pmediathek');
        $path = $this->prepare_file($filename);
        $c = new curl;
        $options = array(
            'filepath' => $path,
            'timeout' => $CFG->repositorygetfiletimeout,
            'CURLOPT_FOLLOWLOCATION' => true,
            'CURLOPT_MAXREDIRS' => 5,
            'CURLOPT_HTTPAUTH' => CURLAUTH_BASIC,
            'CURLOPT_USERPWD' => "{$config->username}:{$config->password}",
        );
        $url .= '&mode=download&user='.$USER->username;
        $result = $c->download_one($url, null, $options);
        if ($result !== true) {
            throw new moodle_exception('errorwhiledownload', 'repository', '', $result);
        }
        return array('path' => $path, 'url' => $url);
    }

    public function supported_filetypes() {
        return array('audio', 'spreadsheet', 'document', '.pdf');
    }

    public function get_link($url) {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/repository/pmediathek/locallib.php');
        $rights = repository_pmediathek_search::get_rights_licence($url);
        if ($rights != 'public' && $rights != 'no copy') {
            throw new moodle_exception('notallowedlink', 'repository_pmediathek');
        }

        /*
        $embed = false;
        if (substr_compare($url, self::EMBED_PREFIX, 0, strlen(self::EMBED_PREFIX)) == 0) {
            $embed = true;
            $url = substr($url, strlen(self::EMBED_PREFIX));
        }
        if (!$embed) {
            return $url;
        }
        */
        $hash = sha1($url);
        if (!$DB->record_exists('repository_pmediathek_link', array('hash' => $hash))) {
            $ins = (object)array(
                'hash' => $hash,
                'url' => $url,
            );
            $DB->insert_record('repository_pmediathek_link', $ins);
        }
        $link = new moodle_url('/repository/pmediathek/link.php', array('hash' => $hash, 'embed' => 1));
        return $link->out(false);
    }

    public function supported_returntypes() {
        return FILE_INTERNAL|FILE_EXTERNAL;
    }

    public static function get_type_option_names() {
        return array_merge(parent::get_type_option_names(), array('url', 'username', 'password', 'logqueries'));
    }

    /**
     * @param MoodleQuickForm $mform
     * @param string $classname
     */
    public static function type_config_form($mform, $classname = 'repository') {
        global $CFG;

        parent::type_config_form($mform);

        $config = get_config('repository_mediathek');
        $mform->addElement('text', 'url', get_string('url', 'repository_pmediathek'), array('size' => 60));
        $mform->setType('url', PARAM_URL);
        if (isset($config->url)) {
            $mform->setDefault('url', $config->url);
        }
        $mform->addElement('text', 'username', get_string('username', 'repository_pmediathek'), array('size' => 20));
        $mform->setType('username', PARAM_RAW);
        if (isset($config->username)) {
            $mform->setDefault('username', $config->username);
        }
        $mform->addElement('text', 'password', get_string('password', 'repository_pmediathek'), array('size' => 20));
        $mform->setType('password', PARAM_RAW);
        if (isset($config->password)) {
            $mform->setDefault('password', $config->password);
        }

        $logpath = $CFG->dataroot.'/mediathek.log';
        $logdesc = get_string('logqueries_desc', 'repository_pmediathek', $logpath);
        $mform->addElement('advcheckbox', 'logqueries', get_string('logqueries', 'repository_pmediathek'), $logdesc);
        if (isset($config->logqueries)) {
            $mform->setDefault('logqueries', $config->logqueries);
        }
    }
}