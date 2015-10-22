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
 * Switch to contrast theme or back
 *
 * @package   theme_mebis
 * @copyright 2015 ISB Bayern
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');

$context = context_system::instance();
$PAGE->set_context($context);

//sicherstellen, dass ein gültiges barrierearmes Theme ausgewählt wurde:
$themenames = array_keys(get_plugin_list('theme'));

if (!in_array($PAGE->theme->settings->contrast_theme, $themenames)) {
    throw new coding_exception("Value \"contrast_theme\" ({$PAGE->theme->settings->contrast_theme})in theme_mebis Configuration is not valid. Theme not changed.");
}

//Falls ein SESSION-THEME gesetzt ist dieses Löschen, sonst setzen, falls dieses gültig ist
if (!empty($_SESSION['SESSION']->theme)) {

    unset($_SESSION['SESSION']->theme);

} else {

    $newtheme = $PAGE->theme->settings->contrast_theme;

    //neues Theme checken...
    if (!in_array($newtheme, $themenames)) {
        throw new coding_exception("Theme {$newtheme} is not valid. Theme not changed.");
    }

    $_SESSION['SESSION']->theme = $newtheme;
}

//Weiterleiten zur Ausgangsseite:
$returnto = optional_param('returnto', 'index.php', PARAM_URL);

$returnto = urldecode($returnto);
//Verifizieren der Ausgangsseite
$regex = '@^'.$CFG->wwwroot.'@';
if (preg_match($regex, $returnto) == false) {
    print_error(get_string('invalidredirect', 'block_dlb'));
}

redirect($returnto);

