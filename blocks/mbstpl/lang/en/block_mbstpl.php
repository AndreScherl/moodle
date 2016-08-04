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
 * Language pack from http://localhost/mebis2
 *
 * @package    block
 * @subpackage mbstpl
 * @copyright 2015 Andreas Wagner, andreas.wagner@isb.bayern.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addfrombank'] = 'Add a question from the question bank';
$string['addqtodraft'] = 'Use question';
$string['addquestion'] = 'Add a new question';
$string['ajaxurl'] = 'Ajax-URL for data';
$string['archive'] = 'Archive';
$string['assigned'] = 'Assigned';
$string['assigneddate'] = 'Assigned date';
$string['assignee'] = 'Assignee';
$string['assignauthor'] = 'Assign author';
$string['assignreviewer'] = 'Assign reviewer';
$string['author'] = 'Author';
$string['authorrole'] = 'Author role';
$string['authorrole_desc'] = 'The role to use when enrolling an author into a course';
$string['backtemplatefeedback'] = 'Back to template feedback';
$string['checklistexpln'] = 'Note: questions of this type will be displayed together at the end of the form, to act as a checklist of the features of this course.<br>
Each item can be marked as \'Yes\', \'No\' or \'Not applicable\'.<br>
It is not possible to search for courses based on these fields.';
$string['complaintemail'] = 'Complaint Email';
$string['complaintemail_desc'] = 'Email for template complaints.';
$string['complaintform'] = 'Complaint Report';
$string['complaintformdetails_default'] = 'Details...';
$string['complaintformemail'] = 'Why do we require an email address?';
$string['complaintformemail_default'] = 'Please enter your email';
$string['complaintformemail_help'] = 'We use your email for inquiries. With content mistakes the course author will contact you, perhaps.';
$string['complaintformerrortype'] = 'Kind of mistake';
$string['complaintformerrortype_1'] = 'moral rights infringement';
$string['complaintformerrortype_2'] = 'terms of use infringement';
$string['complaintformerrortype_3'] = 'other';
$string['complainturl'] = 'Complaint URL';
$string['complainturl_desc'] = 'External URL for course complaints. courseid parameter will be appended.';
$string['confirmdelquest'] = 'This question is in use. Deleting it will remove it from this draft, but will still exist in the question bank. Delete this question?';
$string['confirmdelquestforever'] = 'Deleting question will remove it completely from the question bank. Delete this question?';
$string['coursekeyword'] = 'Course keyword';
$string['courselicense'] = 'Course License';
$string['coursename'] = 'Course name';
$string['coursemetadata'] = 'Course Metadata';
$string['coursesfromtemplate'] = 'Courses created from this template';
$string['coursetemplates'] = 'Course templates';
$string['creator'] = 'Creator';
$string['createdby'] = 'Created By';
$string['createdon'] = 'Created On';
$string['creationdate'] = 'Creation date';
$string['currentrating'] = 'Current rating';
$string['datasource'] = 'Data source for interpreting values';
$string['datasource_help'] = 'If the stored values are represented by ids, you may user another attribute stored records.
    Therefore you hav to set up the databasetabe, the indexing field and the field, that is user for display.
    Example: block_mbstpl_subjects,id,subject . The id will be substited by (the name) of the subject for display.';
$string['description'] = 'Description';
$string['delayedrestore'] = 'Delayed Course Duplication';
$string['delayedrestore_desc'] = 'Schedule Course Duplication to run via CRON vs. immediately on request.';
$string['deploycat'] = 'Template deployment category';
$string['deployuserinfo'] = 'Deploy user data';
$string['destination'] = 'Destination';
$string['duplcourseforuse'] = 'Duplicate course for use';
$string['duplcourseforuse1'] = 'Select Sections and Activities to Duplicate';
$string['duplcourseforuse2'] = 'Duplicate Course from Template';
$string['duplcourselicensedefault'] = 'This course has been created by {$a->creator} and is licensed under {$a->licence}';
$string['duplcourselicense'] = 'License';
$string['editmeta'] = 'Edit meta settings';
$string['emailassignedreviewer_body'] = 'Dear course reviewer,
The you have been assigned to review template course {$a->fullname}. You can review it now at at: {$a->url} .';
$string['emailassignedreviewer_subj'] = 'You have been assigned to review';
$string['emailassignedauthor_body'] = 'Dear course author,
You have been asked to make some changes to template course {$a->fullname}. You can review it now at at: {$a->url} .';
$string['emailassignedauthor_subj'] = 'You have been assigned as a template author';
$string['emailcoursepublished_body'] = 'Dear course author,
    many thanks for the submission of your course {$a->coursename} to teachSHARE. Your course was checked by random sampling.
    By the examination no discrepancies have struck. Please, note that the responsibility for the course contents in spite of this
    process always remains with the submitting author.
    The course has been published and can be viewed at: '."\n"
    .'{$a->url}'."\n"
    .'Should you still want to carry out changes, read the following Tutorial: '."\n"
    .'https://www.mebis.bayern.de/infoportal/fortbildung/tutorials/weitere/teachshare/austauschkurs-ueberarbeiten/';
$string['emailcoursepublished_subj'] = 'Template course published';
$string['emailcomplaint_body'] = 'Dear Support,

    there is a complaint for the template course {$a->coursename}.
    From: {$a->from}
    Error: {$a->error}
    Details: {$a->details}
    Course-URL: {$a->url}
    Revision-URL: {$a->revision}

Best regards
Your ISB programers';
$string['emailcomplaint_subj'] = 'Complaint';
$string['emailcomplaintsend_body'] = 'Dear teachSHARE user,

    thanks for your complaint.

Best regards,
your support team
Andrea Taras
Akademie für Lehrerfortbildung und Personalführung
Kardinal-von-Waldburg-Str. 6-7
Hotline: 09071 - 53 300
mebis@alp.dillingen.de
www.mebis.bayern.de';
$string['emailcomplaintsend_subj'] = 'Complaint sent';
$string['emaildupldeployed_body'] = 'The template course {$a->fullname} has been duplicated into a new copy. It can be viewed at:'."\n".'{$a->url}';
$string['emaildupldeployed_subj'] = 'Template duplicated';
$string['emailfeedbackauth_body'] = 'Dear template reviewer,
The author of course course {$a->fullname}, {$a->reviewer}, has adjusted the course. The following feedback has been given:
{$a->feedback}

You can review it again now at at: {$a->url} .';
$string['emailfeedbackauth_subj'] = 'Template feedback';
$string['emailfeedbackrev_body'] = 'Dear template author,
The template you have created for course {$a->fullname} has been reviewed by {$a->reviewer}. The following feedback has been given:
{$a->feedback}
URL to course: {$a->url} .';
$string['emailfeedbackrev_subj'] = 'Template reviewed';
$string['emailreadyforreview_body'] = 'Dear course manager,
The template course {$a->fullname} is ready for review at: {$a->url} .';
$string['emailreadyforreview_subj'] = 'Template course ready for review';
$string['emailstatsrep_body'] = 'Please find attached the template statistics report.';
$string['emailstatsrep_subj'] = 'Template statistics report.';
$string['emailtempldeployed_body'] = 'Thank you for your submission. The template course will be reviwed.';
$string['emailtempldeployed_subj'] = 'Template deployed';
$string['errorcannotassignauthor'] = 'You cannot assign authors for this course.';
$string['errorcannotassignreviewer'] = 'You cannot assign a reviewer for this course.';
$string['errorcannotcomplain'] = 'You cannot send a complaint.';
$string['errorcannotdupcrs'] = 'You cannot duplicate this course.';
$string['errorcannoteditmeta'] = 'You cannot edit the meta data settings.';
$string['errorcannotmovefile'] = 'Could not move course backup file to restore location.';
$string['errorcannotsearch'] = 'You cannot search for templates on this site.';
$string['errorcannotsendforrevision'] = 'You cannot send this course template for revision.';
$string['errorcannotviewabout'] = 'You cannot view more informations for this course.';
$string['errorcannotviewfeedback'] = 'You cannot view feedback for this course.';
$string['errorcatnotexists'] = 'Restore category does not exist.';
$string['errorcoursenottemplate'] = 'Course is not a template.';
$string['errordeploying'] = 'Error deploying template.';
$string['erroremailbody'] = 'An error has occurred: {$a->message}
{$a->errorstr}';
$string['erroremailsubj'] = 'Templating error';
$string['errormanualenrolnotset'] = 'Manual enrolment not set or enabled for course.';
$string['errornowheretorestore'] = 'There are no categories or courses on this site in which you have permission to restore course.';
$string['errorrestorefilenotexists'] = 'Restore file does not exist.';
$string['errorincorrectdatatype'] = 'Incorrect data type provided.';
$string['errornobackupfound'] = 'No backup was found for backup {$a}';
$string['errornoassignableusers'] = 'No assignable users found.';
$string['errornotallwoedtosendfeedback'] = 'User not allowed to send feedback (neither author nor reviewer).';
$string['errorreviewerrolenotset'] = 'Reviewer role not set. Needs to be set in the plugin settings.';
$string['exceptiondeletingusedlicense'] = 'Unable to delete a license that is being used';
$string['errorteacherrolenotset'] = 'Teacher role not set. Needs to be set in the plugin settings.';
$string['feedback'] = 'Feedback';
$string['feedbackfiles'] = 'Feedback files';
$string['feedbackfor'] = 'Feedback for {$a}';
$string['field_checklist'] = 'Checklist';
$string['field_checkboxgroup'] = 'Checkboxgroup';
$string['field_lookupset'] = 'Lookupset';
$string['forrevision'] = 'For revision';
$string['history'] = 'History';
$string['incluserdata'] = 'Publish user data';
$string['incorrectfieldname'] = 'Incorrect field name provided.';
$string['initialform'] = 'Initial form';
$string['lastupdate'] = 'Last update';
$string['layout'] = 'Layout';
$string['layoutgrid'] = 'Grid';
$string['layoutlist'] = 'List';
$string['leastoneoption'] = 'Please select at least one option.';
$string['legalinfo'] = 'Legal Information';
$string['license'] = 'Published under license';
$string['license_help'] = '<ul><li><a href="https://creativecommons.org/licenses/by/3.0/de/" target="_blank">BY - Attribution</a></li>'
        . '<li><a href="https://creativecommons.org/licenses/by-sa/3.0/de/" target="_blank">SA - ShareAlike</a></li>'
        . '<li><a href="https://creativecommons.org/licenses/by-nc/3.0/de/" target="_blank">NC - NonCommercial</a></li>'
        . '<li><a href="https://creativecommons.org/licenses/by-nd/3.0/de/" target="_blank">ND - NoDerivs</a></li></ul>';
$string['licenses_header'] = 'Available licenses';
$string['licenses_edit'] = 'Manage licenses';
$string['license_fullname'] = 'Full name';
$string['license_shortname'] = 'Short name';
$string['license_source'] = 'Source';
$string['license_used'] = 'Used';
$string['loadmoreresults'] = 'Load more results';
$string['managesearch'] = 'Manage search questions';
$string['manageqforms'] = 'Manage Template Metadata questions';
$string['mbstpl:abouttemplate'] = 'About this template';
$string['mbstpl:addinstance'] = 'Add a my course template information block instance';
$string['mbstpl:assignauthor'] = 'Assign an author to a course';
$string['mbstpl:coursetemplateeditmeta'] = 'Course template edit meta';
$string['mbstpl:coursetemplatereview'] = 'Course template review';
$string['mbstpl:coursetemplatemanager'] = 'Course template manager';
$string['mbstpl:createcoursefromtemplate'] = 'Create course from template';
$string['mbstpl:notanonymised'] = 'Not anonymised during template creation';
$string['mbstpl:myaddinstance'] = 'Add a my course template information block instance to My Home';
$string['mbstpl:ratetemplate'] = 'Rate template';
$string['mbstpl:sendcoursetemplate'] = 'Send course template';
$string['mbstpl:viewcoursetemplatebackups'] = 'View course template backups';
$string['mbstpl:viewhistory'] = 'View course history';
$string['mbstpl:viewrating'] = 'View course rating';
$string['message'] = 'Message';
$string['messageprovider:assignedauthor'] = 'Course Template: Assigned Author';
$string['messageprovider:assignedreviewer'] = 'Course Template: Assigned Reviewer';
$string['messageprovider:complaint'] = 'Course Template: Complaint Report';
$string['messageprovider:deployed'] = 'Course Template: Template Created';
$string['messageprovider:duplicated'] = 'Course Template: Course Duplicated from Template';
$string['messageprovider:error'] = 'Course Template: Error';
$string['messageprovider:feedback'] = 'Course Template: Feedback';
$string['messageprovider:forrevision'] = 'Course Template: For Revision';
$string['messageprovider:published'] = 'Course Template: Published';
$string['messageprovider:stats'] = 'Course Templates: Statistics';
$string['messageprovider:reminder'] = 'Course Templates: Reminders';
$string['mustselectuser'] = 'You must select a user';
$string['myassigned'] = 'My assigned tasks';
$string['mypublished'] = 'My published courses';
$string['myreview'] = 'My courses under review';
$string['myreview_help'] = '<B>bold</B>: The course is assigned to me. I have to do something.<br />'
        . '<span style="background-color:#ff6600;color:#fff;"> orange </span>: The course is assigned to author.<br />'
        . '<span style="background-color:#00a8d5;color:#fff;"> blue </span>: The course is assigned to reviewer.';
$string['myrevision'] = 'My courses under revision';
$string['na'] = 'N/A';
$string['newblocktitle'] = 'teachSHARE';
$string['newlicense'] = 'Add new license';
$string['nextstatsreport'] = 'Next statistics report';
$string['nextstatsreport_desc'] = 'Date to run the next statistics report';
$string['noactiontpls_body'] = 'The following template(s) have been found where no action has been taken during the last set period:
{$a}';
$string['noactiontpls_subj'] = 'Untouched templates';
$string['noresults'] = 'No results found. Please change searchparams.';
$string['nountouchedtemplates'] = 'No templates untouched during the set period.';
$string['pluginname'] = 'Course templating';
$string['pluginnamecategory'] = 'Course templating (more)';
$string['qformactivate'] = 'Activate this draft';
$string['qbank'] = 'Question bank';
$string['qformdiscard'] = 'Discard question form';
$string['qformunsaved'] = 'New question form (unsaved draft)';
$string['questionhelp'] = 'Helptext for question';
$string['questionhelppopupheading'] = 'Help: {$a}';
$string['questionname'] = 'Question name';
$string['questionrequired'] = 'Required';
$string['questiontitle'] = 'Question title';
$string['questiontype'] = 'Question type';
$string['reasonforrevision'] = 'Reasons for revision';
$string['removefromdraft'] = 'Remove from draft';
$string['reviewerrole'] = 'Reviewer role';
$string['reviewerrole_desc'] = 'The role to use when enrolling a reviewer into a course';
$string['save'] = 'Save';
$string['scheduledreporting'] = 'Scheduled template reporting';
$string['selectedauthor'] = 'Selected author';
$string['selectedreviewer'] = 'Selected reviewer';
$string['selecteduser'] = 'Selected user';
$string['selectsectionsandactivities'] = 'Select which sections and activities and user data you want to include in the duplicated course.';
$string['selectuser'] = 'Select user';
$string['sendcoursetemplate'] = 'Send course template';
$string['sendforreviewing'] = 'Send for reviewing';
$string['sendtpldate'] = 'Date of sending course template';
$string['sendfeedbacktoauthor'] = 'Send and assign back to author';
$string['sendfeedbacktoreviewer'] = 'Send and assign back to reviewer';
$string['sentforreview'] = 'This course has been sent for review. You should shortly receive email confirmation of this.';
$string['searchpagesize'] = 'Pagesize for searchresult of templates';
$string['searchresult'] = 'Search Result';
$string['startsreportsent'] = 'Statistics report sent successfully.';
$string['statsreporttooearly'] = 'Too early for the next statistics report. Scheduled for {$a}.';
$string['statusarchived'] = 'Archived';
$string['statusassignedreviewer'] = 'Assigned reviewer';
$string['statuscreated'] = 'Created';
$string['statuspublished'] = 'Published';
$string['statusunderreview'] = 'Under review';
$string['statusunderrevision'] = 'Under revision';
$string['tag'] = 'Tag';
$string['tags'] = 'Tags';
$string['tagshelpbutton'] = 'Help: Tags';
$string['tagshelpbutton_help'] = 'Give the tags which describe the contents or application of your course, e. g. SCHILF-course: schilf, fortbildung';
$string['tagsplaceholder'] = 'tag1, tag2';
$string['tasknote'] = 'Task note';
$string['teacherrole'] = 'Teacher role';
$string['teacherrole_desc'] = 'The role to use when enrolling a teacher into a duplicated course.';
$string['templatefeedback'] = 'Template feedback';
$string['templatehistoryreport'] = 'Template History for "{$a->fullname}" ({$a->shortname})';
$string['templatesearch'] = 'Template Search';
$string['termsofuse'] = 'terms of use';
$string['termsofuse_descr'] = 'Using this feature you agree to acccept the <a href="https://www.mebis.bayern.de/nutzungsbedingungenteachshare/">terms of use</a>.';
$string['timeassigned'] = 'Time assigned';
$string['to'] = 'To:';
$string['tplremindafter'] = 'Send template reminder after';
$string['tplremindafter_desc'] = 'Anyone with the teachSHARE course context capability of cousretemplatemanager will receive notification when a template has not been updated for the specified time.';
$string['tplremindersent'] = 'Template reminders sent.';
$string['updated'] = 'Updated';
$string['uploadfile'] = 'Upload file';
$string['useq'] = 'Use question';
$string['viewfeedback'] = 'View feedback for this revision';
$string['withanon'] = 'With anonymised user data';
$string['withoutanon'] = 'Without user data';
$string['rating'] = 'Rating';
$string['ratingavg'] = 'Rating (average)';
$string['rating_header'] = 'Rate this course\'s template';
$string['submitbutton'] = 'Submit';
$string['cancelbutton'] = 'Cancel';
$string['rating_star'] = '{$a} star';
$string['redirectdupcrsmsg'] = 'Your duplication request has been received. You will receive an email once the action has been completed.';
$string['viewhistory'] = 'View history';
$string['viewrating'] = 'View rating';
$string['redirectdupcrsmsg_done'] = 'The course has been duplicated, you will receive an email confirming this. Redirecting you to the new course.';
$string['yourrating'] = 'Your Rating';

// New strings to be sorted in alphabetically, after translation.
$string['backupcreated'] = 'Backup created';
$string['backupinformation'] = 'Backup information';
$string['courseresetstrategy'] = 'Course reset';
$string['courseresetstrategydescription'] = 'Course reset';
$string['coursebackupwithuserdata'] = '<p>This course template contains userdata. When other users are producing additional user data by testing the course,
    a course reset is done by a restore of the latest available backup file, that is created after publishing the course. These files (published backups)
    are named like pubbkp_[number of course].mbz and listed below.</p>
    <p>A course reset also includes the unenrolment of every user, that is participating the course.</p>';
$string['coursebackupnopubbackup'] = '<p><b>No (published) backup file?</b></p><p>If there is no published backup file available or you have changed the course and want to keep this changes during the next resets,
    you may create a new one:
    <ul>
    <li>Switch the visibility of the course to off</li>
    <li>Be sure to remove all the data, that should not be restored later on</li>
    <li>Click the button to backup the course. This will:<ul>
    <li>Unenrol all users</li><li>Backup the course to pubbkp_[number of course].mbz</li></ul></ul>';
$string['courseresetwithuserdata'] = '<p>Reset of the course will happen by adhoc and scheduled tasks, if you want to reset the course immediately (by restoring a published backup), just
    click the button below</p>';
$string['courseresetnouserdata'] = '<p>This course template contains <b>no userdata</b>.
    Reset of the course will be done by adhoc and scheduled tasks, that are resetting the course with standard setup, see: {$a}</p>';
$string['courserestored'] = 'Course successfully restored';
$string['containsuserdata'] = 'Contains user data';
$string['createanewbackupfile'] = 'Create a new backup file to use for course reset.';
$string['createpublishedbackup'] = 'Create a new published backup file';
$string['creator'] = 'Creator of backup';
$string['deletebackupfile'] = 'Delete the backup file';
$string['deletebackupfiledesc'] = 'Do you really want to delete the file: {$a}?';
$string['dothecoursereset'] = 'Do the course reset';
$string['filedeleted'] = 'File deleted';
$string['id'] = 'ID';
$string['notavailable'] = 'Not available';
$string['pubbackupfile'] = 'Published backup file';
$string['publishedbackup'] = 'Published template overview';
$string['origbackupfile'] = 'Original backup file';
$string['restorebackupfile'] = 'Reset course by restoring newest backup file';
$string['templatereset'] = 'Resetting template';
$string['timecreated'] = 'Time created';
$string['unknowncourse'] = 'Unkown course (probably deleted), course id was: {$a}';
$string['unkowncreator'] = 'Unkown creator of this backup';
$string['userdataids'] = 'Included user data ids';