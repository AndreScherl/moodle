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
 * @package block_mbstpl
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class meta
 * For block_mbstpl_meta.
 * @package block_mbstpl
 */
namespace block_mbstpl\dataobj;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/completion/data_object.php');

class meta extends base {

    /**
     * Array of required table fields, must start with 'id'.
     * @var array
     */
    public $required_fields = array('id');
    public $optional_fields = array(
        'backupid' => 0,
        'templateid' => 0,
        'license' => null,
    );

    /* @var int backupid  */
    public $backupid;

    /* @var int templateid  */
    public $templateid;

    /* @var string license */
    public $license;

    /** @var tag[] $tags */
    protected $tags = null;
    /** @var asset[] $assets */
    protected $assets = null;

    /**
     * Set the table name here.
     * @return string
     */
    public static function get_tablename() {
        return 'block_mbstpl_meta';
    }

    /**
     * Get array of dependants.
     * @return array
     */
    public static function get_dependants() {
        return array('answer' => 'metaid', 'tag' => 'metaid', 'asset' => 'metaid');
    }


    /**
     * Records this object in the Database, sets its id to the returned value, and returns that value.
     * If successful this function also fetches the new object data from database and stores it
     * in object properties.
     *
     * @return int PK ID if successful, false otherwise
     */
    public function insert() {
        if (!($this->backupid xor $this->templateid)) {
            throw new \coding_exception('Meta needs to be linked to either a backup or a template.');
        }
        return parent::insert();
    }

    /**
     * Copy data (including tags, assets, etc) from the given meta data into this one.
     * Note: changes will be saved immediately (no need to call 'update').
     * Note: this will not delete existing tags, etc. on this meta, only add new ones.
     *
     * @param meta $from
     */
    public function copy_from(meta $from) {
        // Copy the answers.
        $answers = answer::fetch_all(array('metaid' => $from->id));
        foreach ($answers as $answer) {
            $copied = clone($answer);
            $copied->id = null;
            $copied->metaid = $this->id;
            $copied->insert();
        }
        // Copy the license.
        $this->license = $from->license;
        // Copy the assets.
        $assets = asset::fetch_all(array('metaid' => $from->id));
        foreach ($assets as $asset) {
            $copied = clone($asset);
            $copied->id = null;
            $copied->metaid = $this->id;
            $copied->insert();
        }
        $this->assets = null;
        // Copy the tags.
        $tags = tag::fetch_all(array('metaid' => $from->id));
        foreach ($tags as $tag) {
            $copied = clone($tag);
            $copied->id = null;
            $copied->metaid = $this->id;
            $copied->insert();
        }

        $this->update();
    }

    /**
     * Get the tag objects associated with this meta data.
     * @return tag[]
     */
    public function get_tags() {
        if ($this->tags === null) {
            $this->tags = tag::fetch_all(array('metaid' => $this->id));
        }
        return $this->tags;
    }

    /**
     * Get the list of tags as a comma-separated list.
     * @return string
     */
    public function get_tags_string() {
        $taglist = array();
        foreach ($this->get_tags() as $tag) {
            $taglist[] = $tag->tag;
        }
        sort($taglist);
        return implode(', ', $taglist);
    }

    /**
     * Given a comma-separated list of tags, add/remove tag objects to match the new list.
     * @param string $tagstr
     */
    public function save_tags_string($tagstr) {
        $taglist = explode(',', $tagstr);
        $taglist = array_map('strtolower', array_filter(array_map('trim', $taglist)));
        foreach ($this->get_tags() as $idx => $tag) {
            if (in_array($tag->tag, $taglist)) {
                $taglist = array_diff($taglist, array($tag->tag)); // Remove from list of tags to add.
            } else {
                $tag->delete(); // Tag not in the new list => delete it.
                unset($this->tags[$idx]);
            }
        }
        foreach ($taglist as $addtag) {
            $tag = new tag(array('metaid' => $this->id, 'tag' => $addtag));
            $tag->insert();
            $this->tags[] = $tag;
        }
    }

    /**
     * Get a list of assets objects associated with this meta data.
     * @retrun asset[]
     */
    public function get_assets() {
        if ($this->assets === null) {
            $this->assets = asset::fetch_all(array('metaid' => $this->id));
        }
        return $this->assets;
    }
}
