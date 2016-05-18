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
 * file class of local mbslicenseinfo - represents a file with all the mebis license and metadata stuff
 *
 * @package   local_mbslicenseinfo
 * @copyright 2015, ISB Bayern
 * @author    Andre Scherl <andre.scherl@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_mbslicenseinfo\local;

defined('MOODLE_INTERNAL') || die();

class mbsfile {

    public $id;
    public $filename;
    public $title;
    public $source;
    public $author;
    public $license;

    /**
     * Construct a file object with all appropriate data, if id is given.
     * 
     * @param object $filewmeta file record with some meta data.
     */
    
    public function __construct($filewmeta) {

        $this->id = $filewmeta->id;
        $this->filename = $filewmeta->filename;
        $this->author = $filewmeta->author;
        $this->title = $filewmeta->title;
        $this->source = $filewmeta->source;
        $this->userid = $filewmeta->userid;
        if ($license = $this->get_license($filewmeta->license)) {
            $this->license = $license;
        }
    }

    /**
     * Get the license of the file
     * 
     * @param string $shortname - shortname of license
     * @return object - license object
     */
    public function get_license($shortname) {
        
        $license = new \stdClass();
        $license->id = null;
        $license->userid = null;
        $license->shortname = $shortname;
        $license->fullname = null;
        $license->source = null;

        // Get the license from license manager.
        if ($lic = \local_mbs\local\licensemanager::get_license_by_shortname($shortname)) {
            $license->id = $lic->id;
            if (!empty($lic->userid)) {
                $license->userid = $lic->userid;
            }
            $license->fullname = $lic->fullname;
            $license->source = $lic->source;
        }

        return $license;
    }
}
