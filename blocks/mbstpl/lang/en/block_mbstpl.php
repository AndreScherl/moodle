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
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addassets'] = 'Add {no} more asset spaces';
$string['addfrombank'] = 'Add a question from the question bank';
$string['addqtodraft'] = 'Use question';
$string['addquestion'] = 'Add a new question';
$string['archive'] = 'Archive';
$string['assets'] = '3rd-party asset used';
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
$string['comment'] = 'Comment';
$string['complainturl'] = 'Complaint URL';
$string['complainturl_desc'] = 'External URL for course complaints. courseid parameter will be appended.';
$string['confirmdelquest'] = 'This question is in use. Deleting it will remove it from this draft, but will still exist in the question bank. Delete this question?';
$string['confirmdelquestforever'] = 'Deleting question will remove it completely from the question bank. Delete this question?';
$string['copyright'] = 'Alle Inhalte des Kurses sind frei von Rechten Dritter';
$string['coursekeyword'] = 'Course keyword';
$string['coursename'] = 'Course name';
$string['coursesfromtemplate'] = 'Courses created from this template';
$string['cousretemplates'] = 'Course templates';
$string['creator'] = 'Creator';
$string['createdby'] = 'Created By';
$string['createdon'] = 'Created On';
$string['creationdate'] = 'Creation date';
$string['currentrating'] = 'Current rating';
$string['delayedrestore'] = 'Delayed Course Duplication';
$string['delayedrestore_desc'] = 'Schedule Course Duplication to run via CRON vs. immediately on request.';
$string['deploycat'] = 'Template deployment category';
$string['destination'] = 'Destination';
$string['duplcourseforuse'] = 'Duplicate course for use';
$string['duplcourseforuse1'] = 'Select Sections and Activities to Duplicate';
$string['duplcourseforuse2'] = 'Duplicate Course from Template';
$string['duplcourselicensedefault'] = 'This course has been created by {$a} and is licensed under the CC-NC-SA';
$string['duplcourselicense'] = 'License';
$string['editmeta'] = 'Edit meta settings';
$string['emailassignedreviewer_body'] = 'Dear course reviewer,
The you have been assigned to review template course {$a->fullname}. You can review it now at at: {$a->url} .';
$string['emailassignedreviewer_subj'] = 'You have been assigned to review';
$string['emailassignedauthor_body'] = 'Dear course author,
You have been asked to make some changes to template course {$a->fullname}. You can review it now at at: {$a->url} .';
$string['emailassignedauthor_subj'] = 'You have been assigned as a template author';
$string['emailcoursepublished_body'] = 'The template course {$a->coursename} has been published by reviewer. It can be viewed at:
{$a->url}';
$string['emailcoursepublished_subj'] = 'Template course published';
$string['emaildupldeployed_body'] = 'The template course {$a->fullname} has been duplicated into a new copy. It can be viewed at:
{$a->url}';
$string['emaildupldeployed_subj'] = 'Template duplicated';
$string['emailfeedbackauth_body'] = 'Dear template reviewer,
The author of course course {$a->fullname}, {$a->reviewer}, has adjusted the course. The following feedback has been given:
{$a->feedback}

You can review it again now at at: {$a->url} .';
$string['emailfeedbackauth_subj'] = 'Template feedback';
$string['emailfeedbackrev_body'] = 'Dear template author,
The template you have created for course {$a->fullname} has been reviewed by {$a->reviewer}. The following feedback has been given:
{$a->feedback}

You can adjust the cousre now it now at at: {$a->url} .';
$string['emailfeedbackrev_subj'] = 'Template reviewed';
$string['emailreadyforreview_body'] = 'Dear course manager,
The template course {$a->fullname} is ready for review at: {$a->url} .';
$string['emailreadyforreview_subj'] = 'Template course ready for review';
$string['emailrevision_body'] = 'A copy of template {$a->fullname} has been created for revision. The reason provided is: 
{$a->reason} 

 The new course is available at:
{$a->url} .';
$string['emailrevision_subj'] = 'Revision created';
$string['emailstatsrep_body'] = 'Please find attached the template statistics report.';
$string['emailstatsrep_subj'] = 'Template statistics report.';
$string['emailtempldeployed_body'] = 'Thank you for your submission. The template course will be reviwed.';
$string['emailtempldeployed_subj'] = 'Template deployed';
$string['errorcannotassignauthor'] = 'You cannot assign authors for this course.';
$string['errorcannotassignreviewer'] = 'You cannot assign a reviewer for this course.';
$string['errorcannotdupcrs'] = 'You cannot duplicate this course.';
$string['errorcannotmovefile'] = 'Could not move course backup file to restore location.';
$string['errorcannotsendforrevision'] = 'You cannot send this course template for revision.';
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
$string['forrevision'] = 'For revision';
$string['history'] = 'History';
$string['incluserdata'] = 'Publish user data';
$string['initialform'] = 'Initial form';
$string['lastupdate'] = 'Last update';
$string['layout'] = 'Layout';
$string['layoutgrid'] = 'Grid';
$string['layoutlist'] = 'List';
$string['license'] = 'Published under license';
$string['licenses_header'] = 'Available licenses';
$string['licenses_edit'] = 'Manage licenses';
$string['license_fullname'] = 'Full name';
$string['license_shortname'] = 'Short name';
$string['license_source'] = 'Source';
$string['license_used'] = 'Used';
$string['managesearch'] = 'Manage search questions';
$string['manageqforms'] = 'Manage Template Metadata questions';
$string['mbstpl:addinstance'] = 'Add a my course template information block instance';
$string['mbstpl:assignauthor'] = 'Assign an author to a course';
$string['mbstpl:coursetemplateeditmeta'] = 'Course template edit meta';
$string['mbstpl:coursetemplatereview'] = 'Course template review';
$string['mbstpl:coursetemplatemanager'] = 'Course template manager';
$string['mbstpl:createcoursefromtemplate'] = 'Create course from template';
$string['mbstpl:myaddinstance'] = 'Add a my course template information block instance to My Home';
$string['mbstpl:ratetemplate'] = 'Rate template';
$string['mbstpl:sendcoursetemplate'] = 'Send course template';
$string['mbstpl:viewcoursetemplatebackups'] = 'View course template backups';
$string['mbstpl:viewhistory'] = 'View course history';
$string['mbstpl:viewrating'] = 'View course rating';
$string['mustselectuser'] = 'You must select a user';
$string['myassigned'] = 'My assigned tasks';
$string['mypublished'] = 'My published courses';
$string['myreview'] = 'My courses under review';
$string['myrevision'] = 'My courses under revision';
$string['na'] = 'N/A';
$string['newblocktitle'] = 'Course Template Information';
$string['newlicense'] = 'New License ...';
$string['newlicense_add'] = 'Add new license';
$string['newlicense_shortname'] = 'New license short name';
$string['newlicense_fullname'] = 'New license full name';
$string['newlicense_source'] = 'New license source';
$string['newlicense_exists'] = 'License "{$a}" already exists - please specify a different short name';
$string['newlicense_required'] = 'A short name is required';
$string['nextstatsreport'] = 'Next statistics report';
$string['nextstatsreport_desc'] = 'Date to run the next statistics report';
$string['norating'] = 'This template has no ratings.';
$string['noactiontpls_body'] = 'The following template(s) have been found where no action has been taken during the last set period:
{$a}';
$string['noactiontpls_subj'] = 'Untouched templates';
$string['notemplates'] = 'You have no active templates in this site.';
$string['noresults'] = 'No results found.';
$string['nountouchedtemplates'] = 'No templates untouched during the set period.';
$string['owner'] = 'Name of owner';
$string['pluginname'] = 'Course templating';
$string['pluginnamecategory'] = 'Course templating (more)';
$string['qformactivate'] = 'Activate this draft';
$string['qbank'] = 'Question bank';
$string['qformdiscard'] = 'Discard question form';
$string['qformunsaved'] = 'New question form (unsaved draft)';
$string['questionhelp'] = 'Helptext for question';
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
$string['sendcoursetemplateheading'] = 'You may publish your course under the following license terms: CC / non-profit / disclosure with attribution / changed). In the CC license, it is necessary that your name is mentioned. You hereby consent to the publication of your name.';
$string['sendforreviewing'] = 'Send for reviewing';
$string['sendtpldate'] = 'Date of sending course template';
$string['sendfeedbacktoauthor'] = 'Send and assign back to author';
$string['sendfeedbacktoreviewer'] = 'Send and assign back to reviewer';
$string['searchresult'] = 'Search Result';
$string['startsreportsent'] = 'Statistics report sent successfully.';
$string['statsreporttooearly'] = 'Too early for the next statistics report. Scheduled for {$a}.';
$string['source'] = 'Name of Primary Source';
$string['sourcesblock:title'] = 'Used References';
$string['statusarchived'] = 'Archived';
$string['statuscreated'] = 'Created';
$string['statuspublished'] = 'Published';
$string['statusunderreview'] = 'Under review';
$string['statusunderrevision'] = 'Under revision';
$string['tag'] = 'Tag';
$string['tags'] = 'Tags';
$string['tasknote'] = 'Task note';
$string['teacherrole'] = 'Teacher role';
$string['teacherrole_desc'] = 'The role to use when enrolling a teacher into a duplicated course.';
$string['templatefeedback'] = 'Template feedback';
$string['templatehistoryreport'] = 'Template History for "{$a->fullname}" ({$a->shortname})';
$string['templatesearch'] = 'Template Search';
$string['timeassigned'] = 'Time assigned';
$string['tplremindafter'] = 'Send template reminder after';
$string['tplremindafter_desc'] = 'Anyone with the system context capability of cousretemplatemanager will receive notification when a template has not been updated for the specified time.';
$string['tplremindersent'] = 'Template reminders sent.';
$string['uploadfile'] = 'Upload file';
$string['url'] = 'Site URL';
$string['useq'] = 'Use question';
$string['viewfeedback'] = 'View feedback for this revision';
$string['withanon'] = 'With anonymised user data';
$string['withoutanon'] = 'Without user data';
$string['rating'] = 'Rating';
$string['ratingavg'] = 'Rating (average)';
$string['rating_header'] = 'Rate this course\'s template';
$string['rating_comments'] = 'Feedback / comments';
$string['rating_submitbutton'] = 'Submit';
$string['rating_cancelbutton'] = 'Cancel';
$string['rating_star'] = '{$a} star';
$string['redirectdupcrsmsg'] = 'Your duplication request has been received. You will receive an email once the action has been completed.';
$string['viewhistory'] = 'View history';
$string['viewrating'] = 'View rating';
$string['redirectdupcrsmsg_done'] = 'The course has been duplicated, you will receive an email confirming this. Redirecting you to the new course.';

