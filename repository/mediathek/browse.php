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
 * Output search results
 *
 * @package   repository_mediathek
 * @copyright 2013 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
global $OUTPUT, $PAGE, $CFG;
require_once($CFG->dirroot.'/repository/mediathek/mediathekapi.php');
require_once($CFG->dirroot.'/repository/lib.php');

define('MEDIATHEK_PAGESIZE', 30);

$searchtext = optional_param('searchtext', null, PARAM_TEXT);
$topic = optional_param('topic', null, PARAM_TEXT);
$level = optional_param('level', null, PARAM_TEXT);
$sort = optional_param('sort', null, PARAM_TEXT);
$mediatype = optional_param('mediatype', null, PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);
$pagesize = optional_param('pagesize', MEDIATHEK_PAGESIZE, PARAM_INT);
$mimetypes = optional_param_array('mimetypes', '*', PARAM_TEXT);

$params = array();
if ($searchtext) {
    $params['searchtext'] = $searchtext;
}
if ($topic) {
    $params['topic'] = $topic;
}
if ($level) {
    $params['level'] = $level;
}
if ($sort) {
    $params['sort'] = $sort;
}
if ($mediatype) {
    $params['mediatype'] = $mediatype;
}
if ($mimetypes != '*') {
    foreach ($mimetypes as $key => $value) {
        $params["mimetypes[$key]"] = $value;
    }
}

$url = new moodle_url('/repository/mediathek/browse.php', $params);
$PAGE->set_url($url);
$context = context_system::instance();
$PAGE->set_context($context);
require_login();
require_capability('repository/mediathek:view', $context);
$PAGE->add_body_class('repository-mediathek-browse');

$api = new repository_mediathek_api();
try {
    // Note - the Moodle paging_bar is 0-based, Mediathek search_content is 1-based, hence the +1 below.
    $results = $api->search_content('searchContent', $pagesize, $page+1, $sort, 'asc', $searchtext, $level, $topic, $mediatype);
    $totalresults = $api->get_total_results();
    /** @var repository_mediathek_file[] $items  */
    $items = array();
    foreach ($results as $result) {
        // Check the mimetype is allowed, before adding the file to the results.
        if ($mimetypes == '*' || in_array(mimeinfo_from_type('extension', $result->technical_format), $mimetypes)) {
            $items[] = repository_mediathek_file::create_from_search_result($result);
        }
    }
} catch (moodle_exception $e) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification($e->getMessage());
    echo $OUTPUT->container_end_all(true);
    die();
}

$pagingbar = '';
if ($totalresults) {
    $pagingbar = $OUTPUT->paging_bar($totalresults, $page, $pagesize, $PAGE->url);
}

// Page output starts here
echo $OUTPUT->header();

echo '<div class="file-picker"><div class="fp-content">';
echo '<div class="fp-iconview">';

echo $pagingbar;

if ($items) {
    foreach ($items as $item) {
        echo $item->output();
    }
} else {
    echo html_writer::tag('p', get_string('noresults', 'repository_mediathek'), array('class' => 'noresults'));
}

echo $pagingbar;

echo '</div>';
echo '</div></div>';

echo $OUTPUT->container_end_all(true);

/**
 * Parse a search result item from the mediathek server and output an icon for the user to select
 */
class repository_mediathek_file {

    /** @var string $title - the filename to display */
    protected $title = null;
    /** @var string $displaynmae - the name to display in the search results */
    protected $displayname = null;
    /** @var string $source - URL to download the image */
    protected $source = null;
    /** @var string $thumbnail - URL of the thumbnail image */
    protected $thumbnail = null;
    /** @var string $author - the name of the author */
    protected $author = null;
    /** @var int $thumbnail_width - width of the thumbnail image */
    protected $thumbnailwidth = null;
    /** @var int $thumbnail_height - height of the thumbnail image */
    protected $thumbnailheight = null;
    /** @var string $datemodified - human-readable date */
    protected $datemodified = null;
    /** @var string $datecreated - human-readable date */
    protected $datecreated = null;
    /** @var int $size - the size of the file */
    protected $size = null;
    /** @var string $license - name of the applied license */
    protected $license = null;
    /** @var string $dimensions - the width & height of the image */
    protected $dimensions = null;
    /** @var int  $returntypes - the valid returntypes for this file */
    protected $returntypes = null;
    /** @var string $description - the description of the resource */
    protected $description = null;
    /** @var string $resourcetype - the human-readable description of the resource */
    protected $resourcetype = null;
    /** @var string $restrictions - the type of restrictions in place for this resource */
    protected $restrictions = null;

    /** @var repository_mediathek_api $api */
    protected static $api = null;

    protected function __construct() {
    }

    /**
     * Parse the returned search result item and create a new repository_mediathek_file instance to hold it
     * @param $item
     * @return repository_mediathek_file
     */
    public static function create_from_search_result($item) {
        global $OUTPUT, $CFG;
        require_once($CFG->libdir.'/filelib.php');

        $ret = new repository_mediathek_file();
        $extn = mimeinfo_from_type('extension', $item->technical_format);
        if ($extn == '.jpe') {
            $extn = '.jpg'; // Replace with the more common version of the extension.
        }
        $ret->displayname = $item->general_title_de;
        $ret->title = $item->general_title_de.$extn;
        $ret->source = $item->technical_location;
        if (!empty($item->technical_thumbnail)) {
            $ret->thumbnail = $item->technical_thumbnail;
        } else {
            $ret->thumbnail = $OUTPUT->pix_url(file_mimetype_icon($item->technical_format, 90))->out(false);
        }
        $ret->returntypes = FILE_EXTERNAL|FILE_INTERNAL;
        if (!empty($item->restrictions)) {
            $ret->restrictions = $item->restrictions;
            if ($ret->restrictions == 'onlyLink') {
                $ret->returntypes = FILE_EXTERNAL;
            }
        }
        $ret->description = $item->general_description_de;
        $ret->resourcetype = $item->educational_resourcetype;
        if (!empty($item->rights_license)) {
            $ret->license = $item->rights_license;
        }

        return $ret;
    }

    /**
     * Generate the HTML snipet for a file in the filepicker
     * @return string
     */
    public function output() {

        $tooltipinfo = '';
        $tooltipinfo .= '<b>'.get_string('title', 'repository_mediathek').'</b>: '.$this->trim($this->displayname, 50).'<br />';
        $tooltipinfo .= '<b>'.get_string('resourcetype', 'repository_mediathek').'</b>: '.self::get_typename($this->resourcetype).'<br />';
        if (!empty($this->license)) {
            $tooltipinfo .= '<b>'.get_string('license', 'repository_mediathek').'</b>: '.$this->license.'<br />';
        }
        if (!empty($this->restrictions)) {
            $restriction = false;
            if ($this->restrictions == 'onlyLink') {
                $restriction = get_string('onlylink', 'repository_mediathek');
            }
            if ($restriction) {
                $tooltipinfo .= '<b>'.get_string('restrictions', 'repository_mediathek').'</b>: '.$restriction.'<br />';
            }
        }
        if (!empty($this->description)) {
            $tooltipinfo .= '<b>'.get_string('description', 'repository_mediathek').'</b>: '.$this->trim($this->description, 100).'<br />';
        }

        $out = '';
        $out .= '<a class="fp-file" href="#" onclick="'.$this->output_onclick().'" >';
        $out .= '<div style="position:relative;">';
        $out .= '<div class="fp-thumbnail" style="width: 110px; height: 110px;">';
        $out .= '<img title="'.$this->title.'" alt="'.$this->title.'" style="max-width: 90px; max-height: 90px;" src="'.$this->thumbnail.'" class="realpreview">';
        $out .= '</div>';
        $out .= '<div class="fp-reficons1"></div>';
        $out .= '<div class="fp-reficons2"></div>';
        $out .= '</div>';
        $out .= '<div class="fp-filename-field">';
        $out .= '<p class="fp-filename" style="width: 112px;">'.$this->displayname.'</p>';
        $out .= '</div>';
        $out .= '<span class="tooltip">'.$tooltipinfo.'</span>';
        $out .= '</a>';
        $out .= "\n";

        return $out;
    }

    protected function get_typename($type) {
        if (is_null(self::$api)) {
            self::$api = new repository_mediathek_api();
        }
        $types = self::$api->get_type_list();
        if (isset($types[$type])) {
            return $types[$type];
        }

        return $type;
    }

    protected function trim($text, $length) {
        if (strlen($text) <= $length) {
            return $text;
        }

        $text = substr($text, 0, $length - 1);
        $text .= '&hellip;';
        return $text;
    }

    /**
     * Generate the javascript call to select the file in the filepicker
     * @return string
     */
    protected function output_onclick() {
        $out = '';
        $out .= 'parent.M.core_filepicker.select_file({';

        $include = array('title', 'source', 'thumbnail', 'author', 'license', 'datemodified', 'datecreated',
                         'thumbnail_height', 'thumbnail_width', 'dimensions', 'returntypes');
        foreach ($include as $field) {
            if (!empty($this->$field)) {
                $value = str_replace(array("'", '"'), array("\\'", "&quot;"), $this->$field);
                $out .= "'$field': '$value', ";
            }
        }

        $out .= '}); return false;';
        return $out;
    }
}