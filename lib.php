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
global $CFG;

const PHASE_SUBMISSION = 1;
const PHASE_QUIZ = 2;



require_once($CFG->dirroot . '/mod/assignquiz/accessmanager.php');

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */

function assignquiz_supports($feature)
{
    switch ($feature) {
        case FEATURE_PLAGIARISM:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        case FEATURE_GRADE_HAS_GRADE:
            return FEATURE_GRADE_HAS_GRADE;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
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
function assignquiz_add_instance($moduleinstance, $mform = null)
{
    error_log('Submitted Data for add_instance: ' . print_r($moduleinstance, true));
    global $DB;
    $moduleinstance->timecreated = time();
    recreate_editors($moduleinstance);
    $moduleinstance->intro = $moduleinstance->assignintro;
    $moduleinstance->introformat = $moduleinstance->assignintroformat;

    $assignquizid = $DB->insert_record('assignquiz', $moduleinstance);


    $assign_id = $DB->insert_record('aiassign', $moduleinstance);
    $moduleinstance->assignment_id = $assign_id;
    $DB->set_field('aiassign', 'assignquizid', $assignquizid, array('id' => $assign_id));

    $quiz_id = $DB->insert_record('aiquiz', $moduleinstance);
    $moduleinstance->quiz_id = $quiz_id;
    $DB->set_field('aiquiz', 'assignquizid', $assignquizid, array('id' => $quiz_id));
    assignquiz_after_add_or_update($moduleinstance, $assignquizid);
    return $assignquizid;
}
function assignquiz_after_add_or_update($moduleinstance, $assignquizid, $mform = null)
{
    global $DB;
    $gradeitem = new stdClass();
    $gradeitem->grademax = $moduleinstance->maxgrade;
    $gradeitem->grademin = $moduleinstance->mingrade;
    $gradeitem->gradepass = $moduleinstance->gradepass;
    $gradeitem->iteminstance = $assignquizid;

    if (!$DB->record_exists('grade_items', ['iteminstance' => $assignquizid, 'itemmodule' => 'assignquiz'])) {
        $gradeitem->itemname = $moduleinstance->name; // Name of the instance of the module
        $gradeitem->itemtype = 'mod'; // Because it is an activity
        $gradeitem->itemmodule = 'assignquiz'; // Our module name
        $gradeitem->gradetype = 1; // Default value
        $DB->insert_record('grade_items', $gradeitem);
    } else {
        $existing_grade_item = $DB->get_record('grade_items', ['iteminstance' => $assignquizid, 'itemmodule' => 'assignquiz'], 'id');
        $gradeitem->id = $existing_grade_item->id;
        $DB->update_record('grade_items', $gradeitem);
    }
}



/**
 * @param object $moduleinstance
 * @return void
 */
function recreate_editors(object $moduleinstance): void
{
    $moduleinstance->introformat = $moduleinstance->intro['format'];
    $moduleinstance->intro = $moduleinstance->intro['text'];

    $moduleinstance->assignintroformat = $moduleinstance->assignintro['format'];
    $moduleinstance->assignintro = $moduleinstance->assignintro['text'];

    $moduleinstance->activity = $moduleinstance->activityeditor['text'];
    $moduleinstance->activityformat = $moduleinstance->activityeditor['format'];

    $moduleinstance->quizintroformat = $moduleinstance->quizintro['format'];
    $moduleinstance->quizintro = $moduleinstance->quizintro['text'];

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
function assignquiz_update_instance($moduleinstance, $mform = null)
{

    global $DB;
    recreate_editors($moduleinstance);
    error_log('Submitted Data for update_instance: ' . print_r($moduleinstance, true));


    $moduleinstance->timemodified = time();

    //When the assignquiz is created it will use the description in the submission phase
    $moduleinstance->intro = $moduleinstance->assignintro;
    $moduleinstance->introformat = $moduleinstance->assignintroformat;


    $moduleinstance->id = $DB->get_field('assignquiz', 'id', array('id' => $moduleinstance->instance));
    $DB->update_record('aiquiz', $moduleinstance);

    $moduleinstance->id = $DB->get_field('assignquiz', 'id', array('id' => $moduleinstance->instance));
    $DB->update_record('aiassign', $moduleinstance);

    $moduleinstance->id = $moduleinstance->instance;
    assignquiz_after_add_or_update($moduleinstance, $moduleinstance->id);

    return $DB->update_record('assignquiz', $moduleinstance);
}

/**
 * Removes an instance of the mod_aiquiz from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function assignquiz_delete_instance($id)
{
    global $DB;

    $exists_quiz = $DB->get_record('aiquiz', array('assignquizid' => $id));
    $exists_assign = $DB->get_record('aiassign', array('assignquizid' => $id));
    $exists_assignquiz = $DB->get_record('assignquiz', array('id' => $id));

    if (!$exists_quiz || !$exists_assign || !$exists_assignquiz) {
        return false;
    }

    $DB->delete_records('aiquiz', array('assignquizid' => $id));
    $DB->delete_records('aiassign', array('assignquizid' => $id));
    $DB->delete_records('assignquiz', array('id' => $id));

    return true;
}


function assignquiz_get_coursemodule_info($coursemodule) {
    global $DB;

    // Fetch your plugin's record from the database.
    $record = $DB->get_record('assignquiz', array('id' => $coursemodule->instance), '*', MUST_EXIST);

    // Create a new course module info object.
    $info = new cached_cm_info();

    // Set the name of the activity (this is required).
    $info->name = $record->name;

    // Add availability (open/close) information if set.
   // if ($record->timeopen && $record->timeclose) {
        if ($record->phase == PHASE_SUBMISSION) {
            $info->content = get_string('availablefromuntilassign', 'assignquiz',
                array(
                    'open' => userdate($record->allowsubmissionsfromdate),
                    'due' => userdate($record->duedate),
                )
            );
        } else if ($record->phase == PHASE_QUIZ) {
            $info->content = get_string('availablefromuntilquiz', 'assignquiz',
                array(
                    'open' => userdate($record->timeopen),
                    'close' => userdate($record->timeclose),
                )
            );
        }
    //}
//    elseif ($record->timeopen) {
//        $info->content = get_string('availablefrom', 'assignquiz', userdate($record->timeopen));
//    }
//    elseif ($record->timeclose) {
//        $info->content = get_string('availableuntil', 'assignquiz', userdate($record->timeclose));
//    }
        $showdescription = $DB->get_field('course_modules','showdescription',['instance'=>$coursemodule->instance],MUST_EXIST);
        if($showdescription){
            if($record->intro){
                $info->content = $info->content . '  <hr/>'. $record->intro;
            }
        }
    // Return the course module info.
    return $info;
}

//function aiquiz_after_add_or_update($aiquiz)
//{
//
//    global $DB;
//
//    // We need to use context now, so we need to make sure all needed info is already in db.
//    $DB->set_field('course_modules', 'instance', $aiquiz->id, array('id' => $aiquiz->coursemodule));
//    $context = context_module::instance($aiquiz->coursemodule);
//
//    // Save the feedback.
//    $DB->delete_records('aiquiz_feedback', array('quizid' => $aiquiz->id));
//
//    for ($i = 0; $i <= $aiquiz->feedbackboundarycount; $i++) {
//        $feedback = new stdClass();
//        $feedback->quizid = $aiquiz->id;
//        $feedback->feedbacktext = $aiquiz->feedbacktext[$i]['text'];
//        $feedback->feedbacktextformat = $aiquiz->feedbacktext[$i]['format'];
//        $feedback->mingrade = $aiquiz->feedbackboundaries[$i];
//        $feedback->maxgrade = $aiquiz->feedbackboundaries[$i - 1];
//        $feedback->id = $DB->insert_record('aiquiz_feedback', $feedback);
//        $feedbacktext = file_save_draft_area_files((int)$aiquiz->feedbacktext[$i]['itemid'],
//            $context->id, 'mod_aiquiz', 'feedback', $feedback->id,
//            array('subdirs' => false, 'maxfiles' => -1, 'maxbytes' => 0),
//            $aiquiz->feedbacktext[$i]['text']);
//        $DB->set_field('aiquiz_feedback', 'feedbacktext', $feedbacktext,
//            array('id' => $feedback->id));
//    }
//
//    // Store any settings belonging to the access rules.
//    aiquiz_access_manager::save_settings($aiquiz);
//
//    // Update the events relating to this quiz.
//    quiz_update_events($aiquiz);
//    $completionexpected = (!empty($aiquiz->completionexpected)) ? $aiquiz->completionexpected : null;
//    \core_completion\api::update_completion_date_event($aiquiz->coursemodule, 'aiquiz', $aiquiz->id, $completionexpected);
//
//    // Update related grade item.
//    quiz_grade_item_update($aiquiz);
//}
