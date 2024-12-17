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
 * Library of interface functions and constants.
 *
 * @package     mod_aiquiz
 * @copyright   2024 Zakaria Lasry Sahraou zsahraoui20@gmail.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assignquiz/accessmanager.php');

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */

function assignquiz_supports($feature) {
    switch ($feature) {
        case FEATURE_PLAGIARISM:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        case FEATURE_GRADE_HAS_GRADE:
            return FEATURE_GRADE_HAS_GRADE;
    }
}
/**
 * Saves a new instance of the mod_aiquiz into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_aiquiz_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function assignquiz_add_instance($moduleinstance ,$mform = null) {
    error_log('FORM VALUE= '.print_r($moduleinstance, true));
    global $DB;

    $assign = new stdClass();

    $assign->id = $moduleinstance->id;
    $assign->course = $moduleinstance->course;
    $assign->name = $moduleinstance->name;
    $assign->intro = $moduleinstance->intro;
    $assign->introformat = $moduleinstance->introformat;
    $assign->submissiondrafts = $moduleinstance->submissiondrafts;
    $assign->sendnotifications = $moduleinstance->sendnotifications;
    $assign->sendlatenotifications = $moduleinstance->sendlatenotifications;
    $assign->duedate = $moduleinstance->duedate;
    $assign->allowsubmissionsfromdate = $moduleinstance->allowsubmissionsfromdate;
    $assign->grade = $moduleinstance->grade;
    $assign->requiresubmissionstatement = $moduleinstance->requiresubmissionstatement;
    $assign->cutoffdate = $moduleinstance->cutoffdate;
    $assign->gradingduedate = $moduleinstance->gradingduedate;
    $assign->teamsubmission = $moduleinstance->teamsubmission;
    $assign->attemptreopenmethod = $moduleinstance->attemptreopenmethod;
    $assign->sendstudentnotifications = $moduleinstance->sendstudentnotifications;

    $assign_id = $DB->insert_record('aiassign', $assign);
    // Process the options from the form.
    $moduleinstance->timecreated = time();
    $result = quiz_process_options($moduleinstance);
    if ($result && is_string($result)) {
        return $result;
    }
    // Try to store it in the database.
    $quiz_id = $DB->insert_record('aiquiz', $moduleinstance);

    $DB->set_field('aiquiz','assignment_id', $assign_id ,['id' => $quiz_id]);
    // Create the first section for this quiz.
    $DB->insert_record('aiquiz_sections', array('quizid' => $quiz_id,
        'firstslot' => 1, 'heading' => '', 'shufflequestions' => 0));

    // Do the processing required after an add or an update.
    //assignquiz_after_add_or_update($moduleinstance);
    error_log('COURSE = '.print_r($moduleinstance->course, true));
    $quiz = new stdClass();
    $quiz->assignment_id = $assign_id;
    $quiz->quiz_id = $quiz_id;
    $quiz->course = 2;
    $quiz->name = $moduleinstance->name;
    $quiz->intro = $moduleinstance->intro;
    $quiz->introformat = $moduleinstance->introformat;
    $quiz->timecreated = $moduleinstance->timecreated;
    $quiz->timemodified = $moduleinstance->timemodified;
    $DB->insert_record('assignquiz', $quiz);

    return $moduleinstance->id;
}

function assignquiz_after_add_or_update($aiquiz) {

    global $DB;
    error_log('ASSIGNQUIZ COURSE MODULE = '.print_r($aiquiz, true));

    // We need to use context now, so we need to make sure all needed info is already in db.
    $DB->set_field('course_modules', 'instance', $aiquiz->id, array('id' => $aiquiz->coursemodule));
    $context = context_module::instance($aiquiz->coursemodule);

    // Save the feedback.
    $DB->delete_records('aiquiz_feedback', array('quizid' => $aiquiz->id));

    for ($i = 0; $i <= $aiquiz->feedbackboundarycount; $i++) {
        $feedback = new stdClass();
        $feedback->quizid = $aiquiz->id;
        $feedback->feedbacktext = $aiquiz->feedbacktext[$i]['text'];
        $feedback->feedbacktextformat = $aiquiz->feedbacktext[$i]['format'];
        $feedback->mingrade = $aiquiz->feedbackboundaries[$i];
        $feedback->maxgrade = $aiquiz->feedbackboundaries[$i - 1];
        $feedback->id = $DB->insert_record('aiquiz_feedback', $feedback);
        $feedbacktext = file_save_draft_area_files((int)$aiquiz->feedbacktext[$i]['itemid'],
            $context->id, 'mod_aiquiz', 'feedback', $feedback->id,
            array('subdirs' => false, 'maxfiles' => -1, 'maxbytes' => 0),
            $aiquiz->feedbacktext[$i]['text']);
        $DB->set_field('aiquiz_feedback', 'feedbacktext', $feedbacktext,
            array('id' => $feedback->id));
    }

    // Store any settings belonging to the access rules.
    aiquiz_access_manager::save_settings($aiquiz);

    // Update the events relating to this quiz.
    quiz_update_events($aiquiz);
    $completionexpected = (!empty($aiquiz->completionexpected)) ? $aiquiz->completionexpected : null;
    \core_completion\api::update_completion_date_event($aiquiz->coursemodule, 'aiquiz', $aiquiz->id, $completionexpected);

    // Update related grade item.
    quiz_grade_item_update($aiquiz);
}
/**
 * Updates an instance of the mod_aiquiz in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_aiquiz_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function assignquiz_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('aiquiz', $moduleinstance);
}

/**
 * Removes an instance of the mod_aiquiz from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function assignquiz_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('aiquiz', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('aiquiz', array('id' => $id));

    return true;
}
