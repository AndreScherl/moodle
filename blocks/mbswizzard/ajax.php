<?php
/**
 * ajax script for block mbswizzard
 *
 * @package     block_mbswizzard
 * @author      Andre Scherl <andre.scherl@isb.bayern.de>
 * @copyright   2015, ISB Bayern
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');

$action = required_param('action', PARAM_ALPHA);
$sequence = required_param('sequence', PARAM_ALPHAEXT);

require_login();

$url = new moodle_url('/blocks/mbswizzard/ajax.php');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

switch ($action) {

    case 'startwizzard':

        $USER->mbswizzard_activesequence = $sequence;

        break;

    case 'finishwizzzard':

        $USER->mbswizzard_activesequence = false;

        break;

    default:

        print_error('unknownaction', 'block_mbssearch');
        die();
}