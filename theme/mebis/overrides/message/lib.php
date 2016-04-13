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
require_once($CFG->libdir . '/eventslib.php');

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
function theme_mebis_message_print_contact_selector($countunreadtotal, $viewing, $user1, $user2, $blockedusers, $onlinecontacts, $offlinecontacts, $strangers, $showactionlinks, $page = 0) {
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
    $coursecontexts = message_get_course_contexts($courses); //we need one of these again so holding on to them

    $strunreadmessages = null;
    if ($countunreadtotal > 0) { //if there are unread messages
        $strunreadmessages = get_string('unreadmessages', 'message', $countunreadtotal);
    }

    theme_mebis_message_print_usergroup_selector($viewing, $courses, $coursecontexts, $countunreadtotal, count($blockedusers), $strunreadmessages, $user1);

    echo html_writer::start_tag('div', array('class' => 'userlist'));
    if ($viewing == MESSAGE_VIEW_UNREAD_MESSAGES) {
        theme_mebis_message_print_contacts($onlinecontacts, $offlinecontacts, $strangers, $PAGE->url, 1, $showactionlinks, $strunreadmessages, $user2);
    } else if ($viewing == MESSAGE_VIEW_CONTACTS || $viewing == MESSAGE_VIEW_SEARCH || $viewing == MESSAGE_VIEW_RECENT_CONVERSATIONS || $viewing == MESSAGE_VIEW_RECENT_NOTIFICATIONS) {
        theme_mebis_message_print_contacts($onlinecontacts, $offlinecontacts, $strangers, $PAGE->url, 0, $showactionlinks, $strunreadmessages, $user2);
    } else if ($viewing == MESSAGE_VIEW_BLOCKED) {
        message_print_blocked_users($blockedusers, $PAGE->url, $showactionlinks, null, $user2);
    } else if (substr($viewing, 0, 7) == MESSAGE_VIEW_COURSE) {
        $courseidtoshow = intval(substr($viewing, 7));

        if (!empty($courseidtoshow) && array_key_exists($courseidtoshow, $coursecontexts) && has_capability('moodle/course:viewparticipants', $coursecontexts[$courseidtoshow])) {

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
function theme_mebis_message_print_search($advancedsearch = false, $user1 = null) {
    $frm = data_submitted();
    $overrides_path = dirname(__FILE__);

    $doingsearch = false;
    if ($frm) {
        if (confirm_sesskey()) {
            $doingsearch = !empty($frm->combinedsubmit) || !empty($frm->keywords) || (!empty($frm->personsubmit) and ! empty($frm->name));
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
    global $PAGE;
    $options = array();

    if ($countunreadtotal > 0) { //if there are unread messages
        $options[MESSAGE_VIEW_UNREAD_MESSAGES] = $strunreadmessages;
    }

    $str = get_string('contacts', 'message');
    $options[MESSAGE_VIEW_CONTACTS] = $str;

    $options[MESSAGE_VIEW_RECENT_CONVERSATIONS] = get_string('mostrecentconversations', 'message');
    $options[MESSAGE_VIEW_RECENT_NOTIFICATIONS] = get_string('mostrecentnotifications', 'message');

    if (!empty($courses)) {
        $courses_options = array();

        foreach ($courses as $course) {
            if (has_capability('moodle/course:viewparticipants', $coursecontexts[$course->id])) {
                //Not using short_text() as we want the end of the course name. Not the beginning.
                $shortname = format_string($course->shortname, true, array('context' => $coursecontexts[$course->id]));
                if (core_text::strlen($shortname) > MESSAGE_MAX_COURSE_NAME_LENGTH) {
                    $courses_options[MESSAGE_VIEW_COURSE . $course->id] = '...' . core_text::substr($shortname, -MESSAGE_MAX_COURSE_NAME_LENGTH);
                } else {
                    $courses_options[MESSAGE_VIEW_COURSE . $course->id] = $shortname;
                }
            }
        }

        if (!empty($courses_options)) {
            $options[] = array(get_string('courses') => $courses_options);
        }
    }

    if ($countblocked > 0) {
        $str = get_string('blockedusers', 'message', $countblocked);
        $options[MESSAGE_VIEW_BLOCKED] = $str;
    }

    $select = new single_select($PAGE->url, 'viewing', $options, $viewing, false);

    $renderer = $PAGE->get_renderer('core');
    echo $renderer->render($select);
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
function theme_mebis_message_print_contacts($onlinecontacts, $offlinecontacts, $strangers, $contactselecturl = null, $minmessages = 0, $showactionlinks = true, $titletodisplay = null, $user2 = null) {
    global $CFG, $PAGE, $OUTPUT;

    $countonlinecontacts = count($onlinecontacts);
    $countofflinecontacts = count($offlinecontacts);
    $countstrangers = count($strangers);
    $isuserblocked = null;

    if ($countonlinecontacts + $countofflinecontacts == 0) {
        echo html_writer::tag('div', get_string('contactlistempty', 'message'), array('class' => 'heading'));
    }

    if (!empty($titletodisplay)) {
        echo html_writer::tag('div', $titletodisplay, array('class' => 'heading'));
    }

    if ($countonlinecontacts) {
        // Print out list of online contacts.

        if (empty($titletodisplay)) {
            echo html_writer::tag('div', get_string('onlinecontacts', 'message', $countonlinecontacts), array('class' => 'heading'));
        }

        $isuserblocked = false;
        $isusercontact = true;
        $contacts = '';
        foreach ($onlinecontacts as $contact) {
            if ($minmessages == 0 || $contact->messagecount >= $minmessages) {
                $contacts .= theme_mebis_message_print_contactlist_user($contact, $isusercontact, $isuserblocked, $contactselecturl, $showactionlinks, $user2);
            }
        }
        if (strlen($contacts) > 0) {
            echo html_writer::start_tag('table', array('id' => 'message_contacts', 'class' => 'boxaligncenter'));
            echo $contacts;
            echo html_writer::end_tag('table');
        }
    }

    if ($countofflinecontacts) {
        // Print out list of offline contacts.

        if (empty($titletodisplay)) {
            echo html_writer::tag('div', get_string('offlinecontacts', 'message', $countofflinecontacts), array('class' => 'heading'));
        }

        $isuserblocked = false;
        $isusercontact = true;
        $contacts = '';
        foreach ($offlinecontacts as $contact) {
            if ($minmessages == 0 || $contact->messagecount >= $minmessages) {
                $contacts .= theme_mebis_message_print_contactlist_user($contact, $isusercontact, $isuserblocked, $contactselecturl, $showactionlinks, $user2);
            }
        }
        if (strlen($contacts) > 0) {
            echo html_writer::start_tag('table', array('id' => 'message_contacts', 'class' => 'boxaligncenter'));
            echo $contacts;
            echo html_writer::end_tag('table');
        }
    }

    // Print out list of incoming contacts.
    if ($countstrangers) {
        echo html_writer::tag('div', get_string('incomingcontacts', 'message', $countstrangers), array('class' => 'heading'));

        $isuserblocked = false;
        $isusercontact = false;
        $contacts = '';
        foreach ($strangers as $stranger) {
            if ($minmessages == 0 || $stranger->messagecount >= $minmessages) {
                $contacts .= theme_mebis_message_print_contactlist_user($stranger, $isusercontact, $isuserblocked, $contactselecturl, $showactionlinks, $user2);
            }
        }
        if (strlen($contacts) > 0) {
            echo html_writer::start_tag('table', array('id' => 'message_contacts', 'class' => 'boxaligncenter'));
            echo $contacts;
            echo html_writer::end_tag('table');
        }
    }

    if ($countstrangers && ($countonlinecontacts + $countofflinecontacts == 0)) {  // Extra help
        echo html_writer::tag('div', '(' . get_string('addsomecontactsincoming', 'message') . ')', array('class' => 'note'));
    }
}

/**
 * Print a row of contactlist displaying user picture, messages waiting and
 * block links etc
 *
 * @param object $contact contact object containing all fields required for $OUTPUT->user_picture()
 * @param bool $incontactlist is the user a contact of ours?
 * @param bool $isblocked is the user blocked?
 * @param string $selectcontacturl the url to send the user to when a contact's name is clicked
 * @param bool $showactionlinks display action links next to the other users (add contact, block user etc)
 * @param object $selecteduser the user the current user is viewing (if any). They will be highlighted.
 * @return void
 */
function theme_mebis_message_print_contactlist_user($contact, $incontactlist = true, $isblocked = false, $selectcontacturl = null, $showactionlinks = true, $selecteduser = null) {
    global $OUTPUT, $USER, $COURSE;
    $fullname = fullname($contact);
    $fullnamelink = $fullname;
    $output = '';

    $linkclass = '';
    if (!empty($selecteduser) && $contact->id == $selecteduser->id) {
        $linkclass = 'messageselecteduser';
    }

    // Are there any unread messages for this contact?
    if ($contact->messagecount > 0) {
        $fullnamelink = '<strong>' . $fullnamelink . ' (' . $contact->messagecount . ')</strong>';
    }

    $strcontact = $strblock = $strhistory = null;

    if ($showactionlinks) {
        // Show block and delete links if user is real user.
        if (core_user::is_real_user($contact->id)) {
            $strcontact = message_get_contact_add_remove_link($incontactlist, $isblocked, $contact);
            $strblock = message_get_contact_block_link($incontactlist, $isblocked, $contact);
        }
        $strhistory = message_history_link($USER->id, $contact->id, true, '', '', 'icon');
    }

    $output .= html_writer::start_tag('tr');
    $output .= html_writer::start_tag('td', array('class' => 'pix'));
    $output .= $OUTPUT->user_picture($contact, array('size' => 20, 'courseid' => $COURSE->id));
    $output .= html_writer::end_tag('td');

    $popupoptions = array(
        'height' => MESSAGE_DISCUSSION_HEIGHT,
        'width' => MESSAGE_DISCUSSION_WIDTH,
        'menubar' => false,
        'location' => false,
        'status' => true,
        'scrollbars' => true,
        'resizable' => true);

    $link = $action = null;
    if (!empty($selectcontacturl)) {
        $link = new moodle_url($selectcontacturl . '&user2=' . $contact->id);
    } else {
        //can $selectcontacturl be removed and maybe the be removed and hardcoded?
        $link = new moodle_url("/message/index.php?id=$contact->id");
        $action = new popup_action('click', $link, "message_$contact->id", $popupoptions);
    }


    if (strlen($strcontact . $strblock . $strhistory) > 0) {
        $output .= html_writer::start_tag('td', array('class' => 'contact'));
        $linkattr = array('class' => $linkclass, 'title' => get_string('sendmessageto', 'message', $fullname));
        $output .= $OUTPUT->action_link($link, $fullnamelink, $action, $linkattr);
        $output .= html_writer::end_tag('td');

        $output .= html_writer::tag('td', '&nbsp;' . $strcontact . $strblock . '&nbsp;' . $strhistory, array('class' => 'link'));
    } else {
        $output .= html_writer::start_tag('td', array('class' => 'contact nolinks'));
        $linkattr = array('class' => $linkclass, 'title' => get_string('sendmessageto', 'message', $fullname));
        $output .= $OUTPUT->action_link($link, $fullnamelink, $action, $linkattr);
        $output .= html_writer::end_tag('td');
    }

    $output .= html_writer::end_tag('tr');
    return $output;
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
function theme_mebis_message_print_message_history($user1, $user2, $search = '', $messagelimit = 0, $messagehistorylink = false, $viewingnewmessages = false, $showactionlinks = true) {
    global $PAGE, $OUTPUT;

    $PAGE->requires->yui_module(
        array('moodle-core_message-toolbox'), 'M.core_message.toolbox.deletemsg.init', array(array())
    );

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

    $useractionlinks = '';
    if ($showactionlinks && isset($user2->iscontact) && isset($user2->isblocked)) {

        $script = null;
        $text = true;
        $icon = false;

        $strcontact = message_get_contact_add_remove_link($user2->iscontact, $user2->isblocked, $user2, $script, $text, $icon);
        $strblock = message_get_contact_block_link($user2->iscontact, $user2->isblocked, $user2, $script, $text, $icon);

        $useractionlinks .= html_writer::tag('div', $strcontact);
        $useractionlinks .= html_writer::tag('div', $strblock);
    }

    echo html_writer::start_tag('div', array('class' => 'row user-correspondence margin-bottom-small'));

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
        $blockdate = get_string('strftimedaydate');
        $messagenumber = 0;
        foreach ($messages as $message) {
            $messagenumber++;
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

                $datestring = html_writer::empty_tag('a', array('name' => $date['year'] . $date['mon'] . $date['mday']));
                $tablecontents .= html_writer::tag('div', $datestring, array('class' => 'mdl-align heading'));

                $tablecontents .= $OUTPUT->heading(userdate($message->timecreated, $blockdate), 4, 'mdl-align');
            }

            if ($message->useridfrom == $user1->id) {
                $formatted_message = message_format_message($message, $messagedate, $search, 'me');
                $side = 'left';
            } else {
                $formatted_message = message_format_message($message, $messagedate, $search, 'other');
                $side = 'right';
            }

            // Check if it is a read message or not.
            if (isset($message->timeread)) {
                $type = 'message_read';
            } else {
                $type = 'message';
            }

            if (message_can_delete_message($message, $user1->id)) {
                $usergroup = optional_param('usergroup', MESSAGE_VIEW_UNREAD_MESSAGES, PARAM_ALPHANUMEXT);
                $viewing = optional_param('viewing', $usergroup, PARAM_ALPHANUMEXT);
                $deleteurl = new moodle_url('/message/index.php', array('user1' => $user1->id, 'user2' => $user2->id,
                    'viewing' => $viewing, 'deletemessageid' => $message->id, 'deletemessagetype' => $type,
                    'sesskey' => sesskey()));

                $deleteicon = $OUTPUT->action_icon($deleteurl, new pix_icon('t/delete', get_string('delete')));
                $deleteicon = html_writer::tag('div', $deleteicon, array('class' => 'deleteicon accesshide'));
                $formatted_message .= $deleteicon;
            }

            $tablecontents .= html_writer::tag('div', $formatted_message, array('class' => "mdl-left messagecontent
                $side $notificationclass", 'id' => 'message_' . $messagenumber));
        }

        $tablecontents .= '<hr>';

        echo html_writer::nonempty_tag('div', $tablecontents, array('class' => 'mdl-left messagehistory'));
    } else {
        echo html_writer::nonempty_tag('div', '(' . get_string('nomessagesfound', 'message') . ') <hr>', array('class' => 'mdl-align messagehistory'));
    }
}
