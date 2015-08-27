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

$string['addfrombank'] = 'Add a question from the question bank';
$string['addqtodraft'] = 'Use question';
$string['addquestion'] = 'Add a new question';
$string['archive'] = 'Archive';
$string['assigned'] = 'Assigned';
$string['assigneddate'] = 'Assigned date';
$string['assignee'] = 'Assignee';
$string['assignreviewer'] = 'Assign reviewer';
$string['confirmdelquest'] = 'This question is in use. Deleting it will remove it from this draft, but will still exist in the question bank. Delete this question?';
$string['confirmdelquestforever'] = 'Deleting question will remove it completely from the question bank. Delete this question?';
$string['copyright'] = 'Alle Inhalte des Kurses sind frei von Rechten Dritter';
$string['coursename'] = 'Course name';
$string['creator'] = 'Creator';
$string['creationdate'] = 'Creation date';
$string['deploycat'] = 'Template deployment category';
$string['editmeta'] = 'Edit meta settings';
$string['emailassignedreviewer_body'] = 'Dear course reviewer,'."\n".'The you have been assigned to review template course {$a->fullname}. You can review it now at at: {$a->url} .';
$string['emailassignedreviewer_subj'] = 'You have been assigned to review';
$string['emailcoursepublished_body'] = 'Template course published';
$string['emailcoursepublished_subj'] = 'The template course {$a->coursename} has been published by reviewer. It can be viewed at:'."\n".'{$a->url}';
$string['emailfeedbackauth_body'] = 'Dear template reviewer,'."\n".'The author of course course {$a->fullname}, {$a->reviewer}, has adjusted the course. The following feedback has been given:'."\n".'{$a->feedback}'."\n\n".'You can review it again now at at: {$a->url} .';
$string['emailfeedbackauth_subj'] = 'Template feedback';
$string['emailfeedbackrev_body'] = 'Dear template author,'."\n".'The template you have created for course {$a->fullname} has been reviewed by {$a->reviewer}. The following feedback has been given:'."\n".'{$a->feedback}'."\n\n".'You can adjust the cousre now it now at at: {$a->url} .';
$string['emailfeedbackrev_subj'] = 'Template reviewed';
$string['emailreadyforreview_body'] = 'Dear course manager,'."\n".'The template course {$a->fullname} is ready for review at: {$a->url} .';
$string['emailreadyforreview_subj'] = 'Template course ready for review';
$string['emailtempldeployed_body'] = 'Template deployed';
$string['emailtempldeployed_subj'] = 'Thank you for your submission. The template course will be reviwed.';
$string['errorcannotassignreviewer'] = 'You cannot assign reviewers for this course.';
$string['errorcannotviewfeedback'] = 'You cannot view feedback for this course.';
$string['errorcoursenottemplate'] = 'Course is not a template.';
$string['errordeploying'] = 'Error deploying template.';
$string['erroremailbody'] = 'An error has occurred: {$a->message}'."\n".'{$a->errorstr}';
$string['erroremailsubj'] = 'Templating error';
$string['errormanualenrolnotset'] = 'Manual enrolment not set or enabled for course.';
$string['errorincorrectdatatype'] = 'Incorrect data type provided.';
$string['errornobackupfound'] = 'No backup was found for backup {$a}';
$string['errornoassignableusers'] = 'No assignable users found.';
$string['errornotallwoedtosendfeedback'] = 'User not allowed to send feedback (neither author nor reviewer).';
$string['errorreviewerrolenotset'] = 'Reviewer role not set. Needs to be set in the plugin settings.';
$string['feedback'] = 'Feedback';
$string['history'] = 'History';
$string['incluserdata'] = 'Publish user data';
$string['initialform'] = 'Initial form';
$string['lastupdate'] = 'Last update';
$string['layout'] = 'Layout';
$string['layoutgrid'] = 'Grid';
$string['layoutlist'] = 'List';
$string['manageqforms'] = 'Manage question forms';
$string['mbstpl:addinstance'] = 'Add a my course template information block instance';
$string['mbstpl:coursetemplateeditmeta'] = 'Course template edit meta';
$string['mbstpl:coursetemplatereview'] = 'Course template review';
$string['mbstpl:coursetemplatemanager'] = 'Course template manager';
$string['mbstpl:coursetemplatereview'] = 'Course template review';
$string['mbstpl:createcoursefromtemplate'] = 'Create course from template';
$string['mbstpl:myaddinstance'] = 'Add a my course template information block instance to My Home';
$string['mbstpl:ratetemplate'] = 'Rate template';
$string['mbstpl:sendcoursetemplate'] = 'Send course template';
$string['mbstpl:viewcoursetemplatebackups'] = 'View course template backups';
$string['myassigned'] = 'My assigned tasks';
$string['mypublished'] = 'My published courses';
$string['myreview'] = 'My courses under review';
$string['myrevision'] = 'My courses under revision';
$string['notemplates'] = 'You have no active templates in this site.';
$string['noresults'] = 'No results found.';
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
$string['save'] = 'Save';
$string['selectedreviewer'] = 'Selected reviewer';
$string['sendcoursetemplate'] = 'Send course template';
$string['sendcoursetemplateheading'] = 'You may publish your course under the following license terms: CC / non-profit / disclosure with attribution / changed). In the CC license, it is necessary that your name is mentioned. You hereby consent to the publication of your name.';
$string['sendforreviewing'] = 'Send for reviewing';
$string['sendtpldate'] = 'Date of sending course template';
$string['sendfeedbacktoauthor'] = 'Send and assign back to author';
$string['sendfeedbacktoreviewer'] = 'Send and assign back to reviewer';
$string['searchresult'] = 'Search Result';
$string['statusarchived'] = 'Archived';
$string['statuscreated'] = 'Created';
$string['statuspublished'] = 'Published';
$string['statusunderreview'] = 'Under review';
$string['statusunderrevision'] = 'Under revision';
$string['templatefeedback'] = 'Template feedback';
$string['templatesearch'] = 'Template Search';
$string['useq'] = 'Use question';
$string['viewfeedback'] = 'View feedback for this revision';
$string['withanon'] = 'With anonymised user data';
$string['withoutanon'] = 'Without user data';
