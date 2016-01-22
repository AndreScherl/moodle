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

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'block/mbstpl:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_ALLOW,
            'student' => CAP_PREVENT
        ),

        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),

    'block/mbstpl:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'user' => CAP_ALLOW,
            'student' => CAP_PREVENT
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),

    'block/mbstpl:sendcoursetemplate' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/course:create'
    ),

    'block/mbstpl:viewcoursetemplatebackups' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => array(
            'teachsharemasterreviewer' => CAP_ALLOW
        )
    ),

    'block/mbstpl:coursetemplatemanager' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'teachsharemasterreviewer' => CAP_ALLOW
        )
    ),

    'block/mbstpl:coursetemplatereview' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'teachsharecoursereviewer' => CAP_ALLOW,
            'teachsharemasterreviewer' => CAP_ALLOW
        )
    ),

    'block/mbstpl:assignauthor' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teachsharecoursereviewer' => CAP_ALLOW,
            'teachsharemasterreviewer' => CAP_ALLOW
        )
    ),

    'block/mbstpl:coursetemplateeditmeta' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'teachsharecourseauthor' => CAP_ALLOW,
            'teachsharecoursereviewer' => CAP_ALLOW,
            'teachsharemasterreviewer' => CAP_ALLOW
        )
    ),

    'block/mbstpl:createcoursefromtemplate' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,        
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW
        )
    ),

    'block/mbstpl:ratetemplate' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'teachsharecourseauthor' => CAP_PREVENT,
            'teachsharecoursereviewer' => CAP_PREVENT
        ),
    ),

    'block/mbstpl:viewrating' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'teachsharecourseauthor' => CAP_PREVENT,
            'teachsharecoursereviewer' => CAP_PREVENT
        ),
    ),


    'block/mbstpl:viewhistory' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teachsharemasterreviewer' => CAP_ALLOW
        )
    ),

    'block/mbstpl:notanonymised' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW
        )
    ),

);
