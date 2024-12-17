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
require_once($CFG->dirroot.'/mod/assign/locallib.php');
class assignquiz extends assign
{
    private $assign_instance;
    public function add_instance(stdClass $formdata, $callplugins)
    {
        global $DB;

        $adminconfig = $this->get_admin_config();

//        $this->name = $formdata->name;
//        $this->timemodified = time();
//        $this->timecreated = time();
//        $this->course = $formdata->course;
//
//        $this->intro = $formdata->intro;
//        $this->introformat = $formdata->introformat;
//        $this->alwaysshowdescription = !empty($formdata->alwaysshowdescription);
//        if (isset($formdata->activityeditor)) {
//            $this->activity = $this->save_editor_draft_files($formdata);
//            $this->activityformat = $formdata->activityeditor['format'];
//        }
//        if (isset($formdata->submissionattachments)) {
//            $this->submissionattachments = $formdata->submissionattachments;
//        }
//        $this->submissiondrafts = $formdata->submissiondrafts;
//        $this->requiresubmissionstatement = $formdata->requiresubmissionstatement;
//        $this->sendnotifications = $formdata->sendnotifications;
//        $this->sendlatenotifications = $formdata->sendlatenotifications;
//        $this->sendstudentnotifications = $adminconfig->sendstudentnotifications;
//        if (isset($formdata->sendstudentnotifications)) {
//            $this->sendstudentnotifications = $formdata->sendstudentnotifications;
//        }
//        $this->gradingduedate = $formdata->gradingduedate;
//        if (isset($formdata->timelimit)) {
//            $this->timelimit = $formdata->timelimit;
//        }
//        $this->allowsubmissionsfromdate = $formdata->allowsubmissionsfromdate;
//        $this->grade = $formdata->grade;
//        $this->completionsubmit = !empty($formdata->completionsubmit);
//        $this->teamsubmission = $formdata->teamsubmission;
//        $this->requireallteammemberssubmit = $formdata->requireallteammemberssubmit;
//        if (isset($formdata->teamsubmissiongroupingid)) {
//            $this->teamsubmissiongroupingid = $formdata->teamsubmissiongroupingid;
//        }
//        $this->blindmarking = $formdata->blindmarking;
//        if (isset($formdata->hidegrader)) {
//            $this->hidegrader = $formdata->hidegrader;
//        }
//        $this->attemptreopenmethod = ASSIGN_ATTEMPT_REOPEN_METHOD_NONE;
//        if (!empty($formdata->attemptreopenmethod)) {
//            $this->attemptreopenmethod = $formdata->attemptreopenmethod;
//        }
//        if (!empty($formdata->maxattempts)) {
//            $this->maxattempts = $formdata->maxattempts;
//        }
//        if (isset($formdata->preventsubmissionnotingroup)) {
//            $this->preventsubmissionnotingroup = $formdata->preventsubmissionnotingroup;
//        }
//        $this->markingworkflow = $formdata->markingworkflow;
//        $this->markingallocation = $formdata->markingallocation;
//        if (empty($this->markingworkflow)) { // If marking workflow is disabled, make sure allocation is disabled.
//            $this->markingallocation = 0;
//        }
        $returnid = $DB->insert_record('aiassign', $this);
        $this->instance = $DB->get_record('aiassign', array('id' => $returnid), '*', MUST_EXIST);
        $this->assign_instance = $DB->get_record('aiassign', array('id' => $returnid), '*', MUST_EXIST);

        $this->save_intro_draft_files($formdata);
        $this->save_editor_draft_files($formdata);
//
//        if ($callplugins) {
//            // Call save_settings hook for submission plugins.
//            foreach ($this->submissionplugins as $plugin) {
//                if (!$this->update_plugin_instance($plugin, $formdata)) {
//                    throw new \moodle_exception($plugin->get_error());
//                    return false;
//                }
//            }
//            foreach ($this->feedbackplugins as $plugin) {
//                if (!$this->update_plugin_instance($plugin, $formdata)) {
//                    throw new \moodle_exception($plugin->get_error());
//                    return false;
//                }
//            }
//
//            // In the case of upgrades the coursemodule has not been set,
//            // so we need to wait before calling these two.
//            // $this->update_calendar($formdata->coursemodule);
//            if (!empty($formdata->completionexpected)) {
//                \core_completion\api::update_completion_date_event($formdata->coursemodule, 'aiassign', $this->instance,
//                    $formdata->completionexpected);
//            }
//        }
        /*
            $update = new stdClass();
            $update->id = $this->get_instance()->id;
            $update->nosubmissions = (!$this->is_any_submission_plugin_enabled()) ? 1: 0;
            $DB->update_record('aiassign', $update);
        */

        return $returnid;
    }

    public function update_calendar($coursemoduleid)
    {
        return parent::update_calendar($coursemoduleid); // TODO: Change the autogenerated stub
    }
    public function view($action = '', $args = array())
    {
        global $PAGE;
        error_log('AAAAAAAAAAA: '.print_r($this->instance->assignment_id,true));
        error_log('BBBBBBBBBBB: '.print_r($this->assign_instance,true)); // AQUI LO DEJÉ

        $o = '';
        $mform = null;
        $notices = array();
        $nextpageparams = array();

        if (!empty($this->get_course_module()->id)) {
            $nextpageparams['id'] = $this->get_course_module()->id;
        }

        if (empty($action)) {
            $PAGE->add_body_class('limitedwidth');
        }

        // Handle form submissions first.
        if ($action == 'savesubmission') {
            $action = 'editsubmission';
            if ($this->process_save_submission($mform, $notices)) {
                $action = 'redirect';
                if ($this->can_grade()) {
                    $nextpageparams['action'] = 'grading';
                } else {
                    $nextpageparams['action'] = 'view';
                }
            }
        } else if ($action == 'editprevioussubmission') {
            $action = 'editsubmission';
            if ($this->process_copy_previous_attempt($notices)) {
                $action = 'redirect';
                $nextpageparams['action'] = 'editsubmission';
            }
        } else if ($action == 'lock') {
            $this->process_lock_submission();
            $action = 'redirect';
            $nextpageparams['action'] = 'grading';
        } else if ($action == 'removesubmission') {
            $this->process_remove_submission();
            $action = 'redirect';
            if ($this->can_grade()) {
                $nextpageparams['action'] = 'grading';
            } else {
                $nextpageparams['action'] = 'view';
            }
        } else if ($action == 'addattempt') {
            $this->process_add_attempt(required_param('userid', PARAM_INT));
            $action = 'redirect';
            $nextpageparams['action'] = 'grading';
        } else if ($action == 'reverttodraft') {
            $this->process_revert_to_draft();
            $action = 'redirect';
            $nextpageparams['action'] = 'grading';
        } else if ($action == 'unlock') {
            $this->process_unlock_submission();
            $action = 'redirect';
            $nextpageparams['action'] = 'grading';
        } else if ($action == 'setbatchmarkingworkflowstate') {
            $this->process_set_batch_marking_workflow_state();
            $action = 'redirect';
            $nextpageparams['action'] = 'grading';
        } else if ($action == 'setbatchmarkingallocation') {
            $this->process_set_batch_marking_allocation();
            $action = 'redirect';
            $nextpageparams['action'] = 'grading';
        } else if ($action == 'confirmsubmit') {
            $action = 'submit';
            if ($this->process_submit_for_grading($mform, $notices)) {
                $action = 'redirect';
                $nextpageparams['action'] = 'view';
            } else if ($notices) {
                $action = 'viewsubmitforgradingerror';
            }
        } else if ($action == 'submitotherforgrading') {
            if ($this->process_submit_other_for_grading($mform, $notices)) {
                $action = 'redirect';
                $nextpageparams['action'] = 'grading';
            } else {
                $action = 'viewsubmitforgradingerror';
            }
        } else if ($action == 'gradingbatchoperation') {
            $action = $this->process_grading_batch_operation($mform);
            if ($action == 'grading') {
                $action = 'redirect';
                $nextpageparams['action'] = 'grading';
            }
        } else if ($action == 'submitgrade') {
            if (optional_param('saveandshownext', null, PARAM_RAW)) {
                // Save and show next.
                $action = 'grade';
                if ($this->process_save_grade($mform)) {
                    $action = 'redirect';
                    $nextpageparams['action'] = 'grade';
                    $nextpageparams['rownum'] = optional_param('rownum', 0, PARAM_INT) + 1;
                    $nextpageparams['useridlistid'] = optional_param('useridlistid', $this->get_useridlist_key_id(), PARAM_ALPHANUM);
                }
            } else if (optional_param('nosaveandprevious', null, PARAM_RAW)) {
                $action = 'redirect';
                $nextpageparams['action'] = 'grade';
                $nextpageparams['rownum'] = optional_param('rownum', 0, PARAM_INT) - 1;
                $nextpageparams['useridlistid'] = optional_param('useridlistid', $this->get_useridlist_key_id(), PARAM_ALPHANUM);
            } else if (optional_param('nosaveandnext', null, PARAM_RAW)) {
                $action = 'redirect';
                $nextpageparams['action'] = 'grade';
                $nextpageparams['rownum'] = optional_param('rownum', 0, PARAM_INT) + 1;
                $nextpageparams['useridlistid'] = optional_param('useridlistid', $this->get_useridlist_key_id(), PARAM_ALPHANUM);
            } else if (optional_param('savegrade', null, PARAM_RAW)) {
                // Save changes button.
                $action = 'grade';
                if ($this->process_save_grade($mform)) {
                    $action = 'redirect';
                    $nextpageparams['action'] = 'savegradingresult';
                }
            } else {
                // Cancel button.
                $action = 'redirect';
                $nextpageparams['action'] = 'grading';
            }
        } else if ($action == 'quickgrade') {
            $message = $this->process_save_quick_grades();
            $action = 'quickgradingresult';
        } else if ($action == 'saveoptions') {
            $this->process_save_grading_options();
            $action = 'redirect';
            $nextpageparams['action'] = 'grading';
        } else if ($action == 'saveextension') {
            $action = 'grantextension';
            if ($this->process_save_extension($mform)) {
                $action = 'redirect';
                $nextpageparams['action'] = 'grading';
            }
        } else if ($action == 'revealidentitiesconfirm') {
            $this->process_reveal_identities();
            $action = 'redirect';
            $nextpageparams['action'] = 'grading';
        }

        $returnparams = array('rownum' => optional_param('rownum', 0, PARAM_INT),
            'useridlistid' => optional_param('useridlistid', $this->get_useridlist_key_id(), PARAM_ALPHANUM));
        $this->register_return_link($action, $returnparams);

        // Include any page action as part of the body tag CSS id.
        if (!empty($action)) {
            $PAGE->set_pagetype('mod-assign-' . $action);
        }
        // Now show the right view page.
        if ($action == 'redirect') {
            $nextpageurl = new moodle_url('/mod/assign/view.php', $nextpageparams);
            $messages = '';
            $messagetype = \core\output\notification::NOTIFY_INFO;
            $errors = $this->get_error_messages();
            if (!empty($errors)) {
                $messages = html_writer::alist($errors, ['class' => 'mb-1 mt-1']);
                $messagetype = \core\output\notification::NOTIFY_ERROR;
            }
            redirect($nextpageurl, $messages, null, $messagetype);
            return;
        } else if ($action == 'savegradingresult') {
            $message = get_string('gradingchangessaved', 'assign');
            $o .= $this->view_savegrading_result($message);
        } else if ($action == 'quickgradingresult') {
            $mform = null;
            $o .= $this->view_quickgrading_result($message);
        } else if ($action == 'gradingpanel') {
            $o .= $this->view_single_grading_panel($args);
        } else if ($action == 'grade') {
            $o .= $this->view_single_grade_page($mform);
        } else if ($action == 'viewpluginassignfeedback') {
            $o .= $this->view_plugin_content('assignfeedback');
        } else if ($action == 'viewpluginassignsubmission') {
            $o .= $this->view_plugin_content('assignsubmission');
        } else if ($action == 'editsubmission') {
            $PAGE->add_body_class('limitedwidth');
            $o .= $this->view_edit_submission_page($mform, $notices);
        } else if ($action == 'grader') {
            $o .= $this->view_grader();
        } else if ($action == 'grading') {
            $o .= $this->view_grading_page();
        } else if ($action == 'downloadall') {
            $o .= $this->download_submissions();
        } else if ($action == 'submit') {
            $o .= $this->check_submit_for_grading($mform);
        } else if ($action == 'grantextension') {
            $o .= $this->view_grant_extension($mform);
        } else if ($action == 'revealidentities') {
            $o .= $this->view_reveal_identities_confirm($mform);
        } else if ($action == 'removesubmissionconfirm') {
            $o .= $this->view_remove_submission_confirm();
        } else if ($action == 'plugingradingbatchoperation') {
            $o .= $this->view_plugin_grading_batch_operation($mform);
        } else if ($action == 'viewpluginpage') {
            $o .= $this->view_plugin_page();
        } else if ($action == 'viewcourseindex') {
            $o .= $this->view_course_index();
        } else if ($action == 'viewbatchsetmarkingworkflowstate') {
            $o .= $this->view_batch_set_workflow_state($mform);
        } else if ($action == 'viewbatchmarkingallocation') {
            $o .= $this->view_batch_markingallocation($mform);
        } else if ($action == 'viewsubmitforgradingerror') {
            $o .= $this->view_error_page(get_string('submitforgrading', 'assign'), $notices);
        } else if ($action == 'fixrescalednullgrades') {
            $o .= $this->view_fix_rescaled_null_grades();
        } else {
            $PAGE->add_body_class('limitedwidth');
            $o .= $this->view_submission_page();
        }

        return $o;
    }
    public function view_student_summary($user, $showlinks) {

        $o = '';

        if ($this->can_view_submission($user->id)) {
            if (has_capability('mod/assign:viewownsubmissionsummary', $this->get_context(), $user, false)) {
                // The user can view the submission summary.
                $submissionstatus = $this->get_assign_submission_status_renderable($user, $showlinks);
                $o .= $this->get_renderer()->render($submissionstatus);
            }

            // If there is a visible grade, show the feedback.
            $feedbackstatus = $this->get_assign_feedback_status_renderable($user);
            if ($feedbackstatus) {
                $o .= $this->get_renderer()->render($feedbackstatus);
            }

            // If there is more than one submission, show the history.
            $history = $this->get_assign_attempt_history_renderable($user);
            if (count($history->submissions) > 1) {
                $o .= $this->get_renderer()->render($history);
            }
        }
        return $o;
    }

    protected function view_submission_page() {
        global $CFG, $DB, $USER, $PAGE;

        $instance = $this->get_instance();

        $this->add_grade_notices();

        $o = '';

        $postfix = '';
        if ($this->has_visible_attachments() && (!$this->get_instance($USER->id)->submissionattachments)) {
            $postfix = $this->render_area_files('mod_assign', ASSIGN_INTROATTACHMENT_FILEAREA, 0);
        }

        $o .= $this->get_renderer()->render(new assign_header($instance,
            $this->get_context(),
            $this->show_intro(),
            $this->get_course_module()->id,
            '', '', $postfix));

        // Display plugin specific headers.
        $plugins = array_merge($this->get_submission_plugins(), $this->get_feedback_plugins());
        foreach ($plugins as $plugin) {
            if ($plugin->is_enabled() && $plugin->is_visible()) {
                $o .= $this->get_renderer()->render(new assign_plugin_header($plugin));
            }
        }

        if ($this->can_view_grades()) {
            //error_log('CUT OFF DATE VALUE: '.print_r($this->get_cutoffdate(),true));
            $actionbuttons = new \mod_assign\output\actionmenu($this->get_course_module()->id);
            $o .= $this->get_renderer()->submission_actionmenu($actionbuttons);
            $summary = $this->get_assign_grading_summary_renderable();
            $o .= $this->get_renderer()->render($summary);

        }

        if ($this->can_view_submission($USER->id)) {
            $o .= $this->view_submission_action_bar($instance, $USER);
            $o .= $this->view_student_summary($USER, true);
        }

        $o .= $this->view_footer();

        \mod_assign\event\submission_status_viewed::create_from_assign($this)->trigger();

        return $o;
    }
    public function get_assign_grading_summary_renderable($activitygroup = null) {

        $instance = $this->get_default_instance(); // Grading summary requires the raw dates, regardless of relativedates mode.
        $cm = $this->get_course_module();
        $course = $this->get_course();

        $draft = ASSIGN_SUBMISSION_STATUS_DRAFT;
        $submitted = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $isvisible = $cm->visible;

        if ($activitygroup === null) {
            $activitygroup = groups_get_activity_group($cm);
        }

        if ($instance->teamsubmission) {
            $warnofungroupedusers = assign_grading_summary::WARN_GROUPS_NO;
            $defaultteammembers = $this->get_submission_group_members(0, true);
            if (count($defaultteammembers) > 0) {
                if ($instance->preventsubmissionnotingroup) {
                    $warnofungroupedusers = assign_grading_summary::WARN_GROUPS_REQUIRED;
                } else {
                    $warnofungroupedusers = assign_grading_summary::WARN_GROUPS_OPTIONAL;
                }
            }

            $summary = new assign_grading_summary(
                $this->count_teams($activitygroup),
                $instance->submissiondrafts,
                $this->count_submissions_with_status($draft, $activitygroup),
                $this->is_any_submission_plugin_enabled(),
                $this->count_submissions_with_status($submitted, $activitygroup),
                $this->get_cutoffdate($activitygroup),
                $this->get_duedate($activitygroup),
                $this->get_timelimit($activitygroup),
                $this->get_course_module()->id,
                $this->count_submissions_need_grading($activitygroup),
                $instance->teamsubmission,
                $warnofungroupedusers,
                $course->relativedatesmode,
                $course->startdate,
                $this->can_grade(),
                $isvisible,
                $this->get_course_module()
            );
        } else {

            // The active group has already been updated in groups_print_activity_menu().
            $countparticipants = $this->count_participants($activitygroup);
            $summary = new assign_grading_summary(
                $countparticipants,
                $instance->submissiondrafts,
                $this->count_submissions_with_status($draft, $activitygroup),
                $this->is_any_submission_plugin_enabled(),
                $this->count_submissions_with_status($submitted, $activitygroup),
                $this->get_cutoffdate($activitygroup),
                $this->get_duedate($activitygroup),
                $this->get_timelimit($activitygroup),
                $this->get_course_module()->id,
                $this->count_submissions_need_grading($activitygroup),
                $instance->teamsubmission,
                assign_grading_summary::WARN_GROUPS_NO,
                $course->relativedatesmode,
                $course->startdate,
                $this->can_grade(),
                $isvisible,
                $this->get_course_module()
            );
        }

        return $summary;
    }
    private function get_cutoffdate(?int $activitygroup = null): int {
        //error_log('ESTE ES EL VALOR DE ACTIVITY GROUP: '.print($activitygroup));
        if ($activitygroup === null) {
            $activitygroup = groups_get_activity_group($this->get_course_module());
        }
        if ($this->can_view_grades() && !empty($activitygroup)) {
            $groupoverride = $this->get_override_data($activitygroup);
            if (!empty($groupoverride->cutoffdate)) {
                return $groupoverride->cutoffdate;
            }
        }

        return $this->get_instance()->cutoffdate;
    }
    private function get_override_data(int $activitygroup) {
        global $DB;

        $instanceid = $this->get_instance()->id;
        $cachekey = "$instanceid-$activitygroup";
        if (isset($this->overridedata[$cachekey])) {
            return $this->overridedata[$cachekey];
        }

        $params = ['groupid' => $activitygroup, 'assignid' => $instanceid];
        $this->overridedata[$cachekey] = $DB->get_record('assign_overrides', $params);
        return $this->overridedata[$cachekey];
    }
    private function get_duedate($activitygroup = null) {
        if ($activitygroup === null) {
            $activitygroup = groups_get_activity_group($this->get_course_module());
        }
        if ($this->can_view_grades() && !empty($activitygroup)) {
            $groupoverride = $this->get_override_data($activitygroup);
            if (!empty($groupoverride->duedate)) {
                return $groupoverride->duedate;
            }
        }
        return $this->get_instance()->duedate;
    }
    public function set_module_viewed()
    {
        $completion = new completion_info($this->get_course());
        $completion->set_module_viewed($this->get_course_module());

        // Trigger the course module viewed event.
        $assigninstance = $this->get_instance();
        $params = [
            'objectid' => $assigninstance->id,
            'context' => $this->get_context()
        ];
        if ($this->is_blind_marking()) {
            $params['anonymous'] = 1;
        }

        $event = \mod_aiquiz\event\course_module_viewed::create($params);

        $event->add_record_snapshot('aiassign', $assigninstance);
        $event->trigger();
    }
    private function calculate_properties(\stdClass $record, int $userid) : \stdClass {
        $record = clone ($record);

        // Relative dates.
        if (!empty($record->duedate)) {
            $course = $this->get_course();
            $usercoursedates = course_get_course_dates_for_user_id($course, $userid);
            if ($usercoursedates['start']) {
                $userprops = ['duedate' => $record->duedate + $usercoursedates['startoffset']];
                $record = (object) array_merge((array) $record, (array) $userprops);
            }
        }
        return $record;
    }
    public function get_instance(int $userid = null): stdClass
    {
        global $USER;
        $userid = $userid ?? $USER->id;

        $this->instance = $this->get_default_instance();

        // If we have the user instance already, just return it.
        if (isset($this->userinstances[$userid])) {
            return $this->userinstances[$userid];
        }

        // Calculate properties which vary per user.
        $this->userinstances[$userid] = $this->calculate_properties($this->instance, $userid);

        return $this->userinstances[$userid];
    }
    public function get_default_instance()
    {
        global $DB;
        if (!$this->instance && $this->get_course_module()) {
            $params = array('id' => $this->get_course_module()->instance);
            $this->instance = $DB->get_record('aiquiz', $params, '*', MUST_EXIST);

            $this->userinstances = [];
        }
        return $this->instance;
    }

}