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
 * Library functions for messaging
 *
 * @package   theme_mebis_overrides_message
 * @copyright 2008 Luis Rodrigues
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir.'/eventslib.php');

/**
 * Print the selector that allows the user to view their contacts, course participants, their recent
 * conversations etc
 *
 * @param int $countunreadtotal how many unread messages does the user have?
 * @param int $viewing What is the user viewing? ie MESSAGE_VIEW_UNREAD_MESSAGES, MESSAGE_VIEW_SEARCH etc
 * @param object $user1 the user whose messages are being viewed
 * @param object $user2 the user $user1 is talking to
 * @param array $blockedusers an array of users blocked by $user1
 * @param array $onlinecontacts an array of $user1's online contacts
 * @param array $offlinecontacts an array of $user1's offline contacts
 * @param array $strangers an array of users who have messaged $user1 who aren't contacts
 * @param bool $showactionlinks show action links (add/remove contact etc)
 * @param int $page if there are so many users listed that they have to be split into pages what page are we viewing
 * @return void
 */
function theme_mebis_message_print_contact_selector($countunreadtotal, $viewing, $user1, $user2, $blockedusers, $onlinecontacts, $offlinecontacts, $strangers, $showactionlinks, $page=0) {
    global $PAGE;

    //if 0 unread messages and they've requested unread messages then show contacts
    if ($countunreadtotal == 0 && $viewing == MESSAGE_VIEW_UNREAD_MESSAGES) {
        $viewing = MESSAGE_VIEW_CONTACTS;
    }

    //if they have no blocked users and they've requested blocked users switch them over to contacts
    if (count($blockedusers) == 0 && $viewing == MESSAGE_VIEW_BLOCKED) {
        $viewing = MESSAGE_VIEW_CONTACTS;
    }

    $onlyactivecourses = true;
    $courses = enrol_get_users_courses($user1->id, $onlyactivecourses);
    $coursecontexts = message_get_course_contexts($courses);//we need one of these again so holding on to them

    $strunreadmessages = null;
    if ($countunreadtotal>0) { //if there are unread messages
        $strunreadmessages = get_string('unreadmessages','message', $countunreadtotal);
    }

    theme_mebis_message_print_usergroup_selector($viewing, $courses, $coursecontexts, $countunreadtotal, count($blockedusers), $strunreadmessages, $user1);

    echo html_writer::start_tag('div', array('class' => 'userlist'));
    if ($viewing == MESSAGE_VIEW_UNREAD_MESSAGES) {
        theme_mebis_message_print_contacts($onlinecontacts, $offlinecontacts, $strangers, $PAGE->url, 1, $showactionlinks,$strunreadmessages, $user2);
    } else if ($viewing == MESSAGE_VIEW_CONTACTS || $viewing == MESSAGE_VIEW_SEARCH || $viewing == MESSAGE_VIEW_RECENT_CONVERSATIONS || $viewing == MESSAGE_VIEW_RECENT_NOTIFICATIONS) {
        theme_mebis_message_print_contacts($onlinecontacts, $offlinecontacts, $strangers, $PAGE->url, 0, $showactionlinks, $strunreadmessages, $user2);
    } else if ($viewing == MESSAGE_VIEW_BLOCKED) {
        message_print_blocked_users($blockedusers, $PAGE->url, $showactionlinks, null, $user2);
    } else if (substr($viewing, 0, 7) == MESSAGE_VIEW_COURSE) {
        $courseidtoshow = intval(substr($viewing, 7));

        if (!empty($courseidtoshow)
            && array_key_exists($courseidtoshow, $coursecontexts)
            && has_capability('moodle/course:viewparticipants', $coursecontexts[$courseidtoshow])) {

            message_print_participants($coursecontexts[$courseidtoshow], $courseidtoshow, $PAGE->url, $showactionlinks, null, $page, $user2);
        }
    }

    echo html_writer::end_tag('div');

}

/**
 * Print the search form and search results if a search has been performed
 *
 * @param  boolean $advancedsearch show basic or advanced search form
 * @param  object $user1 the current user
 * @return boolean true if a search was performed
 */
function theme_mebis_message_print_search($advancedsearch = false, $user1=null) {
    $frm = data_submitted();
    $overrides_path = dirname(__FILE__);

    $doingsearch = false;
    if ($frm) {
        if (confirm_sesskey()) {
            $doingsearch = !empty($frm->combinedsubmit) || !empty($frm->keywords) || (!empty($frm->personsubmit) and !empty($frm->name));
        } else {
            $frm = false;
        }
    }

    if (!empty($frm->combinedsearch)) {
        $combinedsearchstring = $frm->combinedsearch;
    } else {
        //$combinedsearchstring = get_string('searchcombined','message').'...';
        $combinedsearchstring = '';
    }

    if ($doingsearch) {
        if ($advancedsearch) {

            $messagesearch = '';
            if (!empty($frm->keywords)) {
                $messagesearch = $frm->keywords;
            }
            $personsearch = '';
            if (!empty($frm->name)) {
                $personsearch = $frm->name;
            }
            include($overrides_path . '/search_advanced.html');
        } else {
            include($overrides_path . '/search.html');
        }

        $showicontext = false;
        message_print_search_results($frm, $showicontext, $user1);

        return true;
    } else {

        if ($advancedsearch) {
            $personsearch = $messagesearch = '';
            include($overrides_path . '/search_advanced.html');
        } else {
            include($overrides_path . '/search.html');
        }
        return false;
    }
}

/**
 * Print a select box allowing the user to choose to view new messages, course participants etc.
 *
 * Called by message_print_contact_selector()
 * @param int $viewing What page is the user viewing ie MESSAGE_VIEW_UNREAD_MESSAGES, MESSAGE_VIEW_RECENT_CONVERSATIONS etc
 * @param array $courses array of course objects. The courses the user is enrolled in.
 * @param array $coursecontexts array of course contexts. Keyed on course id.
 * @param int $countunreadtotal how many unread messages does the user have?
 * @param int $countblocked how many users has the current user blocked?
 * @param stdClass $user1 The user whose messages we are viewing.
 * @param string $strunreadmessages a preconstructed message about the number of unread messages the user has
 * @return void
 */
function theme_mebis_message_print_usergroup_selector($viewing, $courses, $coursecontexts, $countunreadtotal, $countblocked, $strunreadmessages, $user1 = null) {
    $options = array();

    if ($countunreadtotal>0) { //if there are unread messages
        $options[MESSAGE_VIEW_UNREAD_MESSAGES] = $strunreadmessages;
    }

    $str = get_string('contacts', 'message');
    $options[MESSAGE_VIEW_CONTACTS] = $str;

    $options[MESSAGE_VIEW_RECENT_CONVERSATIONS] = get_string('mostrecentconversations', 'message');
    $options[MESSAGE_VIEW_RECENT_NOTIFICATIONS] = get_string('mostrecentnotifications', 'message');

    if (!empty($courses)) {
        $courses_options = array();

        foreach($courses as $course) {
            if (has_capability('moodle/course:viewparticipants', $coursecontexts[$course->id])) {
                //Not using short_text() as we want the end of the course name. Not the beginning.
                $shortname = format_string($course->shortname, true, array('context' => $coursecontexts[$course->id]));
                if (core_text::strlen($shortname) > MESSAGE_MAX_COURSE_NAME_LENGTH) {
                    $courses_options[MESSAGE_VIEW_COURSE.$course->id] = '...'.core_text::substr($shortname, -MESSAGE_MAX_COURSE_NAME_LENGTH);
                } else {
                    $courses_options[MESSAGE_VIEW_COURSE.$course->id] = $shortname;
                }
            }
        }

        if (!empty($courses_options)) {
            $options[] = array(get_string('courses') => $courses_options);
        }
    }

    if ($countblocked>0) {
        $str = get_string('blockedusers','message', $countblocked);
        $options[MESSAGE_VIEW_BLOCKED] = $str;
    }

    echo html_writer::start_tag('form', array('id' => 'usergroupform','method' => 'get','action' => ''));
    echo html_writer::start_tag('fieldset');
    if ( !empty($user1) && !empty($user1->id) ) {
        echo html_writer::empty_tag('input', array('class' => 'form-control','type' => 'hidden','name' => 'user1','value' => $user1->id));
    }
    echo html_writer::select($options, 'viewing', $viewing, false, array('class' => 'form-control','id' => 'viewing','onchange' => 'this.form.submit()'));
    echo html_writer::end_tag('fieldset');
    echo html_writer::end_tag('form');

}

/**
 * Print $user1's contacts. Called by message_print_contact_selector()
 *
 * @param array $onlinecontacts $user1's contacts which are online
 * @param array $offlinecontacts $user1's contacts which are offline
 * @param array $strangers users which are not contacts but who have messaged $user1
 * @param string $contactselecturl the url to send the user to when a contact's name is clicked
 * @param int $minmessages The minimum number of unread messages required from a user for them to be displayed
 *                         Typically 0 (show all contacts) or 1 (only show contacts from whom we have a new message)
 * @param bool $showactionlinks show action links (add/remove contact etc) next to the users
 * @param string $titletodisplay Optionally specify a title to display above the participants
 * @param object $user2 the user $user1 is talking to. They will be highlighted if they appear in the list of contacts
 * @return void
 */
function theme_mebis_message_print_contacts($onlinecontacts, $offlinecontacts, $strangers, $contactselecturl=null, $minmessages=0, $showactionlinks=true, $titletodisplay=null, $user2=null) {
    global $CFG, $PAGE, $OUTPUT;

    $countonlinecontacts  = count($onlinecontacts);
    $countofflinecontacts = count($offlinecontacts);
    $countstrangers       = count($strangers);
    $isuserblocked = null;

    if ($countonlinecontacts + $countofflinecontacts == 0) {
        echo html_writer::tag('div', get_string('contactlistempty', 'message'), array('class' => 'heading'));
    }

    echo html_writer::start_tag('table', array('id' => 'message_contacts', 'class' => 'boxaligncenter'));

    if (!empty($titletodisplay)) {
        message_print_heading($titletodisplay);
    }

    if($countonlinecontacts) {
        // Print out list of online contacts.

        if (empty($titletodisplay)) {
            message_print_heading(get_string('onlinecontacts', 'message', $countonlinecontacts));
        }

        $isuserblocked = false;
        $isusercontact = true;
        foreach ($onlinecontacts as $contact) {
            if ($minmessages == 0 || $contact->messagecount >= $minmessages) {
                message_print_contactlist_user($contact, $isusercontact, $isuserblocked, $contactselecturl, $showactionlinks, $user2);
            }
        }
    }

    if ($countofflinecontacts) {
        // Print out list of offline contacts.

        if (empty($titletodisplay)) {
            message_print_heading(get_string('offlinecontacts', 'message', $countofflinecontacts));
        }

        $isuserblocked = false;
        $isusercontact = true;
        foreach ($offlinecontacts as $contact) {
            if ($minmessages == 0 || $contact->messagecount >= $minmessages) {
                message_print_contactlist_user($contact, $isusercontact, $isuserblocked, $contactselecturl, $showactionlinks, $user2);
            }
        }

    }

    // Print out list of incoming contacts.
    if ($countstrangers) {
        message_print_heading(get_string('incomingcontacts', 'message', $countstrangers));

        $isuserblocked = false;
        $isusercontact = false;
        foreach ($strangers as $stranger) {
            if ($minmessages == 0 || $stranger->messagecount >= $minmessages) {
                message_print_contactlist_user($stranger, $isusercontact, $isuserblocked, $contactselecturl, $showactionlinks, $user2);
            }
        }
    }

    echo html_writer::end_tag('table');

    if ($countstrangers && ($countonlinecontacts + $countofflinecontacts == 0)) {  // Extra help
        echo html_writer::tag('div','('.get_string('addsomecontactsincoming', 'message').')',array('class' => 'note'));
    }
}

/**
 * Print the message history between two users
 *
 * @param object $user1 the current user
 * @param object $user2 the other user
 * @param string $search search terms to highlight
 * @param int $messagelimit maximum number of messages to return
 * @param string $messagehistorylink the html for the message history link or false
 * @param bool $viewingnewmessages are we currently viewing new messages?
 */
function theme_mebis_message_print_message_history($user1, $user2 ,$search = '', $messagelimit = 0, $messagehistorylink = false, $viewingnewmessages = false, $showactionlinks = true) {
    global $CFG, $OUTPUT;

    echo $OUTPUT->box_start('center');

    $myAvatar = $OUTPUT->user_picture($user1, array('size' => 100, 'courseid' => SITEID));
    $username1 = html_writer::tag('div', fullname($user1), array('class' => 'heading'));
    $correspondence = html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('i/twoway'), 'alt' => ''));

    // Show user picture with link is real user else without link.
    if (core_user::is_real_user($user2->id)) {
        $otherUser = $OUTPUT->user_picture($user2, array('size' => 100, 'courseid' => SITEID));
    } else {
        $otherUser = $OUTPUT->user_picture($user2, array('size' => 100, 'courseid' => SITEID, 'link' => false));
    }

    $username2 = html_writer::tag('div', fullname($user2), array('class' => 'heading'));

    if ($showactionlinks && isset($user2->iscontact) && isset($user2->isblocked)) {

        $script = null;
        $text = true;
        $icon = false;

        $strcontact = message_get_contact_add_remove_link($user2->iscontact, $user2->isblocked, $user2, $script, $text, $icon);
        $strblock   = message_get_contact_block_link($user2->iscontact, $user2->isblocked, $user2, $script, $text, $icon);


        $useractionlinks = html_writer::tag('div', $strcontact);
        $useractionlinks .= html_writer::tag('div', $strblock);
    }

    echo html_writer::start_tag('div', array('class'=>'row user-correspondence margin-bottom-small'));

    echo html_writer::tag('div', $myAvatar . $username1, array('class' => 'col-md-3 text-center'));
    echo html_writer::tag('div', $correspondence, array('class' => 'col-md-2 text-center spacing-top'));
    echo html_writer::tag('div', $otherUser . $username2, array('class' => 'col-md-3 text-center'));
    echo html_writer::tag('div', html_writer::tag('div', $useractionlinks, array('class' => 'useractionlinks spacing-top')), array('class' => 'col-md-4'));
    echo html_writer::end_tag('div');


    echo $OUTPUT->box_end();

    if (!empty($messagehistorylink)) {
        echo $messagehistorylink;
    }

    /// Get all the messages and print them
    if ($messages = message_get_history($user1, $user2, $messagelimit, $viewingnewmessages)) {
        $tablecontents = '';

        $current = new stdClass();
        $current->mday = '';
        $current->month = '';
        $current->year = '';
        $messagedate = get_string('strftimetime');
        $blockdate   = get_string('strftimedaydate');
        foreach ($messages as $message) {
            if ($message->notification) {
                $notificationclass = ' notification';
            } else {
                $notificationclass = null;
            }
            $date = usergetdate($message->timecreated);
            if ($current->mday != $date['mday'] | $current->month != $date['month'] | $current->year != $date['year']) {
                $current->mday = $date['mday'];
                $current->month = $date['month'];
                $current->year = $date['year'];

                $datestring = html_writer::empty_tag('a', array('name' => $date['year'].$date['mon'].$date['mday']));
                $tablecontents .= html_writer::tag('div', $datestring, array('class' => 'mdl-align heading'));

                $tablecontents .= $OUTPUT->heading(userdate($message->timecreated, $blockdate), 4, 'mdl-align');
            }

            $formatted_message = $side = null;
            if ($message->useridfrom == $user1->id) {
                $formatted_message = message_format_message($message, $messagedate, $search, 'me');
                $side = 'mdl-left';
            } else {
                $formatted_message = message_format_message($message, $messagedate, $search, 'other');
                $side = 'mdl-right';
            }
            $tablecontents .= html_writer::tag('div', $formatted_message, array('class' => "$side $notificationclass"));
        }

        $tablecontents .= '<hr>';

        echo html_writer::nonempty_tag('div', $tablecontents, array('class' => 'mdl-left messagehistory'));
    } else {
        echo html_writer::nonempty_tag('div', '('.get_string('nomessagesfound', 'message').') <hr>', array('class' => 'mdl-align messagehistory'));
    }
}
