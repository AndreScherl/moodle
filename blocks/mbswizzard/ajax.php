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
$sequence = optional_param('sequence', 'none', PARAM_ALPHAEXT);

require_sesskey();
require_login();

$url = new moodle_url('/blocks/mbswizzard/ajax.php');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

$context = context_user::instance($USER->id);

switch ($action) {

    case 'startwizzard':

        $USER->mbswizzard_activesequence = $sequence;
        
        $event = \block_mbswizzard\event\wizzardstate_changed::create(array(
            'context' => $context,
            'relateduserid' => $USER->id,
            'other' => array(
                'wizzardname' => $sequence,
                'useraction' => 'started')));
        $event->trigger();

        break;

    case 'finishwizzard':

        $USER->mbswizzard_activesequence = false;
        
        $event = \block_mbswizzard\event\wizzardstate_changed::create(array(
            'context' => $context,
            'relateduserid' => $USER->id,
            'other' => array(
                'wizzardname' => $sequence,
                'useraction' => 'finished')));
        $event->trigger();
        
        break;
    
    case 'cancelwizzard':
        
        $USER->mbswizzard_activesequence = false;
        
        $event = \block_mbswizzard\event\wizzardstate_changed::create(array(
            'context' => $context,
            'relateduserid' => $USER->id,
            'other' => array(
                'wizzardname' => $sequence,
                'useraction' => 'canceled')));
        $event->trigger();
        
        break;

    default:

        print_error('unknownaction', 'block_mbssearch');
        die();
}