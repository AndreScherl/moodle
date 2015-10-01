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
$string['coursename'] = 'Course name';
$string['coursekeyword'] = 'Course keyword';
$string['creator'] = 'Creator';
$string['creationdate'] = 'Creation date';
$string['currentrating'] = 'Current rating';
$string['deploycat'] = 'Template deployment category';
$string['duplcourseforuse'] = 'Duplicate course for use';
$string['duplcourselicense'] = 'This course has been created by {$a} and is licensed under the CC-NC-SA';
$string['editmeta'] = 'Edit meta settings';
$string['emailassignedreviewer_body'] = 'Dear course reviewer,'."\n".'The you have been assigned to review template course {$a->fullname}. You can review it now at at: {$a->url} .';
$string['emailassignedreviewer_subj'] = 'You have been assigned to review';
$string['emailassignedauthor_body'] = 'Dear course author,'."\n".'The you have been asked to make some changes to template course {$a->fullname}. You can review it now at at: {$a->url} .';
$string['emailassignedauthor_subj'] = 'You have been assigned as a template author';
$string['emailcoursepublished_body'] = 'The template course {$a->coursename} has been published by reviewer. It can be viewed at:'."\n".'{$a->url}';
$string['emailcoursepublished_subj'] = 'Template course published';
$string['emaildupldeployed_body'] = 'The template course {$a->fullname} has been duplicated into a new copy. It can be viewed at:'."\n".'{$a->url}';
$string['emaildupldeployed_subj'] = 'Template duplicated';
$string['emailfeedbackauth_body'] = 'Dear template reviewer,'."\n".'The author of course course {$a->fullname}, {$a->reviewer}, has adjusted the course. The following feedback has been given:'."\n".'{$a->feedback}'."\n\n".'You can review it again now at at: {$a->url} .';
$string['emailfeedbackauth_subj'] = 'Template feedback';
$string['emailfeedbackrev_body'] = 'Dear template author,'."\n".'The template you have created for course {$a->fullname} has been reviewed by {$a->reviewer}. The following feedback has been given:'."\n".'{$a->feedback}'."\n\n".'You can adjust the cousre now it now at at: {$a->url} .';
$string['emailfeedbackrev_subj'] = 'Template reviewed';
$string['emailreadyforreview_body'] = 'Dear course manager,'."\n".'The template course {$a->fullname} is ready for review at: {$a->url} .';
$string['emailreadyforreview_subj'] = 'Template course ready for review';
$string['emailtempldeployed_body'] = 'Thank you for your submission. The template course will be reviwed.';
$string['emailtempldeployed_subj'] = 'Template deployed';
$string['errorcannotassignauthor'] = 'You cannot assign authors for this course.';
$string['errorcannotassignreviewer'] = 'You cannot assign a reviewer for this course.';
$string['errorcannotdupcrs'] = 'You cannot duplicate this course.';
$string['errorcannotmovefile'] = 'Could not move course backup file to restore location.';
$string['errorcannotviewfeedback'] = 'You cannot view feedback for this course.';
$string['errorcatnotexists'] = 'Restore category does not exist.';
$string['errorcoursenottemplate'] = 'Course is not a template.';
$string['errordeploying'] = 'Error deploying template.';
$string['erroremailbody'] = 'An error has occurred: {$a->message}'."\n".'{$a->errorstr}';
$string['erroremailsubj'] = 'Templating error';
$string['errormanualenrolnotset'] = 'Manual enrolment not set or enabled for course.';
$string['errornowheretorestore'] = 'There are no categories or courses on this site in which you have permission to restore course.';
$string['errorrestorefilenotexists'] = 'Restore file does not exist.';
$string['errorincorrectdatatype'] = 'Incorrect data type provided.';
$string['errornobackupfound'] = 'No backup was found for backup {$a}';
$string['errornoassignableusers'] = 'No assignable users found.';
$string['errornotallwoedtosendfeedback'] = 'User not allowed to send feedback (neither author nor reviewer).';
$string['errorreviewerrolenotset'] = 'Reviewer role not set. Needs to be set in the plugin settings.';
$string['feedback'] = 'Feedback';
$string['feedbackfiles'] = 'Feedback files';
$string['feedbackfor'] = 'Feedback for {$a}';
$string['field_checklist'] = 'Checklist';
$string['history'] = 'History';
$string['incluserdata'] = 'Publish user data';
$string['initialform'] = 'Initial form';
$string['lastupdate'] = 'Last update';
$string['layout'] = 'Layout';
$string['layoutgrid'] = 'Grid';
$string['layoutlist'] = 'List';
$string['license'] = 'Published under license';
$string['managesearch'] = 'Manage search questions';
$string['manageqforms'] = 'Manage question forms';
$string['mbstpl:addinstance'] = 'Add a my course template information block instance';
$string['mbstpl:assignauthor'] = 'Assign an author to a course';
$string['mbstpl:coursetemplateeditmeta'] = 'Course template edit meta';
$string['mbstpl:coursetemplatereview'] = 'Course template review';
$string['mbstpl:coursetemplatemanager'] = 'Course template manager';
$string['mbstpl:coursetemplatereview'] = 'Course template review';
$string['mbstpl:createcoursefromtemplate'] = 'Create course from template';
$string['mbstpl:myaddinstance'] = 'Add a my course template information block instance to My Home';
$string['mbstpl:ratetemplate'] = 'Rate template';
$string['mbstpl:sendcoursetemplate'] = 'Send course template';
$string['mbstpl:viewcoursetemplatebackups'] = 'View course template backups';
$string['mustselectuser'] = 'You must select a user';
$string['myassigned'] = 'My assigned tasks';
$string['mypublished'] = 'My published courses';
$string['myreview'] = 'My courses under review';
$string['myrevision'] = 'My courses under revision';
$string['nextstatsreport'] = 'Next statistics report';
$string['nextstatsreport_desc'] = 'The time set for the next statistics email report';
$string['norating'] = 'This template has no ratings.';
$string['noresults'] = 'No results found.';
$string['owner'] = 'Name of owner';
$string['notemplates'] = 'You have no active templates in this site.';
$string['pluginname'] = 'Course templating';
$string['qformactive'] = 'Active question form';
$string['qformactivate'] = 'Activate this draft';
$string['qbank'] = 'Question bank';
$string['qformdiscard'] = 'Discard question form';
$string['qformdiscard'] = 'Discard question form';
$string['qformunsaved'] = 'New question form (unsaved draft)';
$string['questionname'] = 'Question name';
$string['questiontitle'] = 'Question title';
$string['questiontype'] = 'Question type';
$string['removefromdraft'] = 'Remove from draft';
$string['reviewerrole'] = 'Reviewer role';
$string['reviewerrole_desc'] = 'The role to use when enrolling a reviewer into a course';
$string['save'] = 'Save';
$string['selectedauthor'] = 'Selected author';
$string['selectedreviewer'] = 'Selected reviewer';
$string['selecteduser'] = 'Selected user';
$string['selectuser'] = 'Select user';
$string['sendcoursetemplate'] = 'Send course template';
$string['sendcoursetemplateheading'] = 'You may publish your course under the following license terms: CC / non-profit / disclosure with attribution / changed). In the CC license, it is necessary that your name is mentioned. You hereby consent to the publication of your name.';
$string['sendforreviewing'] = 'Send for reviewing';
$string['sendtpldate'] = 'Date of sending course template';
$string['sendfeedbacktoauthor'] = 'Send and assign back to author';
$string['sendfeedbacktoreviewer'] = 'Send and assign back to reviewer';
$string['searchresult'] = 'Search Result';
$string['statsreporttooearly'] = 'Too early for the next statistics report. Scheduled for {$a}.';
$string['statusarchived'] = 'Archived';
$string['statuscreated'] = 'Created';
$string['statuspublished'] = 'Published';
$string['statusunderreview'] = 'Under review';
$string['statusunderrevision'] = 'Under revision';
$string['tag'] = 'Tag';
$string['tags'] = 'Tags';
$string['tasknote'] = 'Task note';
$string['templatefeedback'] = 'Template feedback';
$string['templatesearch'] = 'Template Search';
$string['timeassigned'] = 'Time assigned';
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
$string['viewrating'] = 'View rating';
