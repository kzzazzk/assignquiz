<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     mod_aiquiz
 * @category    string
 * @copyright   2024 Zakaria Lasry z.lsahraoui@alumnos.upm.es
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$string['pluginname'] = 'AssignQuiz';
$string['modulename'] = 'AssignQuiz';
$string['modulenameplural'] = 'AssignQuizzes';
$string['modulename_help'] = 'The AssignQuiz plugin enables teachers to create personalized quizzes with AI-generated questions based on student-uploaded content. It includes all standard quiz features plus:

* A long-text input for specifying required knowledge
* Custom dates for practice submission and quiz opening
* File upload option for students before practice submission deadline
* AI-generated quizzes based on uploaded content
* AI-generated feedback on quiz performance

Quizzes may be used

* As personalized course exams
* As practice tests tailored to student submissions
* To provide immediate and specific feedback on performance
* For customized self-assessment';
$string['aiquizname'] = '';
$string['aiquizfieldset'] = 'Settings';
$string['assignmenttiming'] = 'Availability';
$string['assignmentname'] = 'Task name';
$string['assigninstructions'] = 'Assignment submission instructions';
$string['assigninstructions_help'] = 'The actions you would like the student to complete for this assignment. This is only shown on the submission page where a student edits and submits their assignment.';
$string['activityname'] = 'Activity name';
$string['quiztiming'] = 'Timing';
$string['dynamic'] = 'Dynamic';
$string['static'] = 'Static';
$string['descriptiontype'] = 'Description type';
$string['descriptiontype_help'] = "Types of descriptions:
 
* Static description <b> will not change </b> depending on the activity phase.
* Dynamic description <b> will change </b> from the activity's submission phase to the quiz phase.";
$string['activitydescription'] = 'Required knowledge';
$string['submissionphasedescription'] = 'Submission phase description';
$string['quizphasedescription'] = 'Quiz phase description';
$string['description'] = 'Description';
$string['requiredknowledge_help'] = 'Describe in detail what knowledge is required for the users to properly meet with the standards of grading.';
$string['activityeditor_help'] = 'The actions you would like the student to complete for this assignment. This is only shown on the submission page where a student edits and submits their assignment.';
$string['aiassignconfigtitle'] = 'Assignment Configuration';
$string['aiquizconfigtitle'] = 'AI Quiz Configuration';
$string['coursemoduleconfigtitle'] = 'Other course module settings';
$string['basicsettings'] = 'Basic settings';
$string['aiquiz:view'] = 'Ability to see that the AssignQuiz exists, and the basic information
about it, for example the start date and time limit.';
$string['aiquiz:addinstance'] = 'Ability to add a new AssignQuiz to the course.';
$string['aiquiz:attempt'] = 'Ability to do the AssignQuiz as a student.';
$string['aiquiz:reviewmyattempts'] = 'Ability for a student to review their previous attempts.';
$string['aiquiz:manage'] = 'Edit the AssignQuiz settings, add and remove questions.';
$string['aiquiz:manageoverrides'] = 'Edit the AssignQuiz overrides.';
$string['aiquiz:viewoverrides'] = 'View the AssignQuiz overrides';
$string['aiquiz:preview'] = 'Preview the AssignQuiz.';
$string['aiquiz:gradequiz'] = 'Manually grade and comment on student attempts at a question.';
$string['aiquiz:regrade'] = 'Regrade quizzes.';
$string['aiquiz:viewreports'] = 'View the AssignQuiz reports.';
$string['aiquiz:deleteattempts'] = 'Delete attempts using the overview report.';
$string['aiquiz:ignoretimelimits'] = 'Do not have the time limit imposed. Used for accessibility legislation compliance.';
$string['aiquiz:emailconfirmsubmission'] = 'Receive a confirmation message of own AssignQuiz submission.';
$string['aiquiz:emailnotifysubmission'] = 'Receive a notification message of other peoples AssignQuiz submissions.';
$string['aiquiz:emailwarnoverdue'] = 'Receive a notification message when an AssignQuiz attempt becomes overdue.';
$string['emailnotifyattemptgraded'] = 'Receive a notification message when an AssignQuiz attempt manual graded.';
$string['aiquiz:submitassignment'] = 'Ability to submit an assignment as a student.';
$string['aiquiz:gradeassignment'] = 'Manually grade and comment on student assignments.';
$string['aiquiz:exportownsubmission'] = 'Ability to export their own submitted assignments';

