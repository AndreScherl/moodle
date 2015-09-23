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
 * Asset associated with a template
 *
 * @package   block_mbstpl
 * @copyright 2015 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstpl\dataobj;

defined('MOODLE_INTERNAL') || die();

class asset extends base {
    public $required_fields = array('id', 'metaid', 'url', 'license', 'owner');

    /** @var int $metaid */
    public $metaid;
    /** @var string $tag */
    public $url;
    /** @var string $license */
    public $license;
    /** @var string $owner */
    public $owner;

    public static function get_tablename() {
        return 'block_mbstpl_asset';
    }
}
