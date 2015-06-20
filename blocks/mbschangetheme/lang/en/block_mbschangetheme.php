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
 * language file for Block mbschangetheme
 *
 * @package    block_mbschangetheme
 * @copyright  Andreas Wagner <andreas.wagner@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['changeallowusertheme'] = 'Setting allowusertheme';
$string['changetotheme1'] = 'Select new design';
$string['changetotheme2'] = 'Select old design';
$string['displayname'] = 'Design Change';
$string['eventthemechanged'] = 'User has changed theme';
$string['mbschangetheme:addinstance'] = 'Add block mebis changetheme';
$string['mbschangetheme:myaddinstance'] = 'Add block mebis changetheme to my dashboard';
$string['newalertheading'] = 'You may change the design!';
$string['newalertexpl'] = 'Temporarily you may select the old (deprecated) mebis design. You find a design change button at you personal desktop.';
$string['newalerthideme'] = 'Don\'t show this notification anymore.';
$string['newalertclose'] = 'Close';
$string['notconfiguredproperly'] = 'The plugin is not configured properly!';
$string['pluginname'] = 'mebis changetheme';
$string['requireallowusertheme'] = 'To enalbe theme-toggling, you have to switch global configuration allowuserthemes to on ({$a}).';
$string['theme1'] = 'theme 1 (Mebis)';
$string['theme1desc'] = 'The user may toggle between theme1 and theme2 using the button in the block. Themes can be selected in following contexts:
    site, category, course, user. The user specific theme has the highest priority, so the setting in all other contexts will be
    overridden. Note that therefore a course theme cannot be forced for a user, when global configuration allowusertheme is set to on.';
$string['theme2'] = 'theme 2 (DLB)';
$string['theme2desc'] = '';
$string['unknowntheme'] = 'Unkown theme';