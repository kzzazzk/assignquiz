<?php

namespace mod_assignquiz\output;

use mod_assign\output\assign_header;
use mod_assignquiz\output\aiassign_submission_plugin_submission;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assignquiz/locallib.php');

class renderer extends \plugin_renderer_base
{
    public function render_assign_plugin_header(\assign_plugin_header $header) {
        $o = $header->plugin->view_header();
        return $o;
    }
    public function submission_actionmenu(\mod_assign\output\actionmenu $actionmenu): string {
        $context = $actionmenu->export_for_template($this);
        return $this->render_from_template('mod_assign/submission_actionmenu', $context);
    }
    public function render_assign_grading_summary(\assign_grading_summary $summary) {
        // Create a table for the data.
        $o = '';
        $o .= $this->output->container_start('gradingsummary');
        $o .= $this->output->heading(get_string('gradingsummary', 'assign'), 3);

        if (isset($summary->cm)) {
            $currenturl = new \moodle_url('/mod/assign/view.php', array('id' => $summary->cm->id));
            $o .= groups_print_activity_menu($summary->cm, $currenturl->out(), true);
        }

        $o .= $this->output->box_start('boxaligncenter gradingsummarytable');
        $t = new \html_table();
        $t->attributes['class'] = 'generaltable table-bordered';

        // Visibility Status.
        $cell1content = get_string('hiddenfromstudents');
        $cell2content = (!$summary->isvisible) ? get_string('yes') : get_string('no');
        $this->add_table_row_tuple($t, $cell1content, $cell2content);

        // Status.
        if ($summary->teamsubmission) {
            if ($summary->warnofungroupedusers === \assign_grading_summary::WARN_GROUPS_REQUIRED) {
                $o .= $this->output->notification(get_string('ungroupedusers', 'assign'));
            } else if ($summary->warnofungroupedusers === \assign_grading_summary::WARN_GROUPS_OPTIONAL) {
                $o .= $this->output->notification(get_string('ungroupedusersoptional', 'assign'));
            }
            $cell1content = get_string('numberofteams', 'assign');
        } else {
            $cell1content = get_string('numberofparticipants', 'assign');
        }

        $cell2content = $summary->participantcount;
        $this->add_table_row_tuple($t, $cell1content, $cell2content);

        // Drafts count and dont show drafts count when using offline assignment.
        if ($summary->submissiondraftsenabled && $summary->submissionsenabled) {
            $cell1content = get_string('numberofdraftsubmissions', 'assign');
            $cell2content = $summary->submissiondraftscount;
            $this->add_table_row_tuple($t, $cell1content, $cell2content);
        }

        // Submitted for grading.
        if ($summary->submissionsenabled) {
            $cell1content = get_string('numberofsubmittedassignments', 'assign');
            $cell2content = $summary->submissionssubmittedcount;
            $this->add_table_row_tuple($t, $cell1content, $cell2content);

            if (!$summary->teamsubmission) {
                $cell1content = get_string('numberofsubmissionsneedgrading', 'assign');
                $cell2content = $summary->submissionsneedgradingcount;
                $this->add_table_row_tuple($t, $cell1content, $cell2content);
            }
        }

        $time = time();
        if ($summary->duedate) {
            // Time remaining.
            $duedate = $summary->duedate;
            $cell1content = get_string('timeremaining', 'assign');
            if ($summary->courserelativedatesmode) {
                $cell2content = get_string('relativedatessubmissiontimeleft', 'mod_assign');
            } else {
                if ($duedate - $time <= 0) {
                    $cell2content = get_string('assignmentisdue', 'assign');
                } else {
                    $cell2content = format_time($duedate - $time);
                }
            }

            $this->add_table_row_tuple($t, $cell1content, $cell2content);

            if ($duedate < $time) {
                $cell1content = get_string('latesubmissions', 'assign');
                $cutoffdate = $summary->cutoffdate;
                if ($cutoffdate) {
                    if ($cutoffdate > $time) {
                        $cell2content = get_string('latesubmissionsaccepted', 'assign', userdate($summary->cutoffdate));
                    } else {
                        $cell2content = get_string('nomoresubmissionsaccepted', 'assign');
                    }

                    $this->add_table_row_tuple($t, $cell1content, $cell2content);
                }
            }

        }

        // Add time limit info if there is one.
        $timelimitenabled = get_config('assign', 'enabletimelimit');
        if ($timelimitenabled && $summary->timelimit > 0) {
            $cell1content = get_string('timelimit', 'assign');
            $cell2content = format_time($summary->timelimit);
            $this->add_table_row_tuple($t, $cell1content, $cell2content, [], []);
        }

        // All done - write the table.
        $o .= \html_writer::table($t);
        $o .= $this->output->box_end();

        // Close the container and insert a spacer.
        $o .= $this->output->container_end();
        $o .= \html_writer::end_tag('center');

        return $o;
    }
    private function add_table_row_tuple(\html_table $table, $first, $second, $firstattributes = [],
                                                     $secondattributes = []) {
        $row = new \html_table_row();
        $cell1 = new \html_table_cell($first);
        $cell1->header = true;
        if (!empty($firstattributes)) {
            $cell1->attributes = $firstattributes;
        }
        $cell2 = new \html_table_cell($second);
        if (!empty($secondattributes)) {
            $cell2->attributes = $secondattributes;
        }
        $row->cells = array($cell1, $cell2);
        $table->data[] = $row;
    }
    public function render_footer() {
        return $this->output->footer();
    }
    protected function get_time_remaining(\mod_assignquiz\output\aiassign_submission_status $status): array {
        $time = time();
        $submission = $status->teamsubmission ? $status->teamsubmission : $status->submission;
        $submissionstarted = $submission && property_exists($submission, 'timestarted') && $submission->timestarted;
        $timelimitenabled = get_config('assign', 'enabletimelimit') && $status->timelimit > 0 && $submissionstarted;
        // Define $duedate as latest between due date and extension - which is a possibility...
        $extensionduedate = intval($status->extensionduedate);
        $duedate = !empty($extensionduedate) ? max($status->duedate, $extensionduedate) : $status->duedate;
        $duedatereached = $duedate > 0 && $duedate - $time <= 0;
        $timelimitenabledbeforeduedate = $timelimitenabled && !$duedatereached;

        // There is a submission, display the relevant early/late message.
        if ($submission && $submission->status == ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
            $latecalculation = $submission->timemodified - ($timelimitenabledbeforeduedate ? $submission->timestarted : 0);
            $latethreshold = $timelimitenabledbeforeduedate ? $status->timelimit : $duedate;
            $earlystring = $timelimitenabledbeforeduedate ? 'submittedundertime' : 'submittedearly';
            $latestring = $timelimitenabledbeforeduedate ? 'submittedovertime' : 'submittedlate';
            $ontime = $latecalculation <= $latethreshold;
            return [
                get_string(
                    $ontime ? $earlystring : $latestring,
                    'assign',
                    format_time($latecalculation - $latethreshold)
                ),
                $ontime ? 'earlysubmission' : 'latesubmission'
            ];
        }

        // There is no submission, due date has passed, show assignment is overdue.
        if ($duedatereached) {
            return [
                get_string(
                    $status->submissionsenabled ? 'overdue' : 'duedatereached',
                    'assign',
                    format_time($time - $duedate)
                ),
                'overdue'
            ];
        }

        // An attempt has started and there is a time limit, display the time limit.
        if ($timelimitenabled && !empty($submission->timestarted)) {
            return [
                (new \assign($status->context, null, null))->get_timelimit_panel($submission),
                'timeremaining'
            ];
        }

        // Assignment is not overdue, and no submission has been made. Just display the due date.
        return [get_string('paramtimeremaining', 'assign', format_time($duedate - $time)), 'timeremaining'];
    }


    public function render_aiassign_submission_status(aiassign_submission_status $status) {
        $o = '';
        $o .= $this->output->container_start('submissionstatustable');
        $o .= $this->output->heading(get_string('submissionstatusheading', 'assign'), 3);
        $time = time();

        $o .= $this->output->box_start('boxaligncenter submissionsummarytable');

        $t = new \html_table();
        $t->attributes['class'] = 'generaltable table-bordered';

        $warningmsg = '';
        if ($status->teamsubmissionenabled) {
            $cell1content = get_string('submissionteam', 'assign');
            $group = $status->submissiongroup;
            if ($group) {
                $cell2content = format_string($group->name, false, ['context' => $status->context]);
            } else if ($status->preventsubmissionnotingroup) {
                if (count($status->usergroups) == 0) {
                    $notification = new \core\output\notification(get_string('noteam', 'assign'), 'error');
                    $notification->set_show_closebutton(false);
                    $warningmsg = $this->output->notification(get_string('noteam_desc', 'assign'), 'error');
                } else if (count($status->usergroups) > 1) {
                    $notification = new \core\output\notification(get_string('multipleteams', 'assign'), 'error');
                    $notification->set_show_closebutton(false);
                    $warningmsg = $this->output->notification(get_string('multipleteams_desc', 'assign'), 'error');
                }
                $cell2content = $this->output->render($notification);
            } else {
                $cell2content = get_string('defaultteam', 'assign');
            }

            $this->add_table_row_tuple($t, $cell1content, $cell2content);
        }

        if ($status->attemptreopenmethod != ASSIGN_ATTEMPT_REOPEN_METHOD_NONE) {
            $currentattempt = 1;
            if (!$status->teamsubmissionenabled) {
                if ($status->submission) {
                    $currentattempt = $status->submission->attemptnumber + 1;
                }
            } else {
                if ($status->teamsubmission) {
                    $currentattempt = $status->teamsubmission->attemptnumber + 1;
                }
            }

            $cell1content = get_string('attemptnumber', 'assign');
            $maxattempts = $status->maxattempts;
            if ($maxattempts == ASSIGN_UNLIMITED_ATTEMPTS) {
                $cell2content = get_string('currentattempt', 'assign', $currentattempt);
            } else {
                $cell2content = get_string('currentattemptof', 'assign',
                    array('attemptnumber' => $currentattempt, 'maxattempts' => $maxattempts));
            }

            $this->add_table_row_tuple($t, $cell1content, $cell2content);
        }

        $cell1content = get_string('submissionstatus', 'assign');
        $cell2attributes = [];
        if (!$status->teamsubmissionenabled) {
            if ($status->submission && $status->submission->status != ASSIGN_SUBMISSION_STATUS_NEW) {
                $cell2content = get_string('submissionstatus_' . $status->submission->status, 'assign');
                $cell2attributes = array('class' => 'submissionstatus' . $status->submission->status);
            } else {
                if (!$status->submissionsenabled) {
                    $cell2content = get_string('noonlinesubmissions', 'assign');
                } else {
                    $cell2content = get_string('nosubmissionyet', 'assign');
                }
            }
        } else {
            $group = $status->submissiongroup;
            if (!$group && $status->preventsubmissionnotingroup) {
                $cell2content = get_string('nosubmission', 'assign');
            } else if ($status->teamsubmission && $status->teamsubmission->status != ASSIGN_SUBMISSION_STATUS_NEW) {
                $teamstatus = $status->teamsubmission->status;
                $cell2content = get_string('submissionstatus_' . $teamstatus, 'assign');

                $members = $status->submissiongroupmemberswhoneedtosubmit;
                $userslist = array();
                foreach ($members as $member) {
                    $urlparams = array('id' => $member->id, 'course'=>$status->courseid);
                    $url = new \moodle_url('/user/view.php', $urlparams);
                    if ($status->view == aiassign_submission_status::GRADER_VIEW && $status->blindmarking) {
                        $userslist[] = $member->alias;
                    } else {
                        $fullname = fullname($member, $status->canviewfullnames);
                        $userslist[] = $this->output->action_link($url, $fullname);
                    }
                }
                if (count($userslist) > 0) {
                    $userstr = join(', ', $userslist);
                    $formatteduserstr = get_string('userswhoneedtosubmit', 'assign', $userstr);
                    $cell2content .= $this->output->container($formatteduserstr);
                }

                $cell2attributes = array('class' => 'submissionstatus' . $status->teamsubmission->status);
            } else {
                if (!$status->submissionsenabled) {
                    $cell2content = get_string('noonlinesubmissions', 'assign');
                } else {
                    $cell2content = get_string('nosubmission', 'assign');
                }
            }
        }

        $this->add_table_row_tuple($t, $cell1content, $cell2content, [], $cell2attributes);

        // Is locked?
        if ($status->locked) {
            $cell1content = '';
            $cell2content = get_string('submissionslocked', 'assign');
            $cell2attributes = array('class' => 'submissionlocked');
            $this->add_table_row_tuple($t, $cell1content, $cell2content, [], $cell2attributes);
        }

        // Grading status.
        $cell1content = get_string('gradingstatus', 'assign');
        if ($status->gradingstatus == ASSIGN_GRADING_STATUS_GRADED ||
            $status->gradingstatus == ASSIGN_GRADING_STATUS_NOT_GRADED) {
            $cell2content = get_string($status->gradingstatus, 'assign');
        } else {
            $gradingstatus = 'markingworkflowstate' . $status->gradingstatus;
            $cell2content = get_string($gradingstatus, 'assign');
        }
        if ($status->gradingstatus == ASSIGN_GRADING_STATUS_GRADED ||
            $status->gradingstatus == ASSIGN_MARKING_WORKFLOW_STATE_RELEASED) {
            $cell2attributes = array('class' => 'submissiongraded');
        } else {
            $cell2attributes = array('class' => 'submissionnotgraded');
        }
        $this->add_table_row_tuple($t, $cell1content, $cell2content, [], $cell2attributes);

        $submission = $status->teamsubmission ? $status->teamsubmission : $status->submission;
        $duedate = $status->duedate;
        if ($duedate > 0) {
            if ($status->view == aiassign_submission_status::GRADER_VIEW) {
                if ($status->cutoffdate) {
                    // Cut off date.
                    $cell1content = get_string('cutoffdate', 'assign');
                    $cell2content = userdate($status->cutoffdate);
                    $this->add_table_row_tuple($t, $cell1content, $cell2content);
                }
            }

            if ($status->extensionduedate) {
                // Extension date.
                $cell1content = get_string('extensionduedate', 'assign');
                $cell2content = userdate($status->extensionduedate);
                $this->add_table_row_tuple($t, $cell1content, $cell2content);
                $duedate = $status->extensionduedate;
            }
        }

        // Time remaining.
        // Only add the row if there is a due date, or a countdown.
        if ($status->duedate > 0 || !empty($submission->timestarted)) {
            $cell1content = get_string('timeremaining', 'assign');
            [$cell2content, $cell2attributes] = $this->get_time_remaining($status);
            $this->add_table_row_tuple($t, $cell1content, $cell2content, [], ['class' => $cell2attributes]);
        }

        // Add time limit info if there is one.
        $timelimitenabled = get_config('assign', 'enabletimelimit') && $status->timelimit > 0;
        if ($timelimitenabled && $status->timelimit > 0) {
            $cell1content = get_string('timelimit', 'assign');
            $cell2content = format_time($status->timelimit);
            $this->add_table_row_tuple($t, $cell1content, $cell2content, [], []);
        }

        // Show graders whether this submission is editable by students.
        if ($status->view == aiassign_submission_status::GRADER_VIEW) {
            $cell1content = get_string('editingstatus', 'assign');
            if ($status->canedit) {
                $cell2content = get_string('submissioneditable', 'assign');
                $cell2attributes = array('class' => 'submissioneditable');
            } else {
                $cell2content = get_string('submissionnoteditable', 'assign');
                $cell2attributes = array('class' => 'submissionnoteditable');
            }
            $this->add_table_row_tuple($t, $cell1content, $cell2content, [], $cell2attributes);
        }

        // Last modified.
        if ($submission) {
            $cell1content = get_string('timemodified', 'assign');

            if ($submission->status != ASSIGN_SUBMISSION_STATUS_NEW) {
                $cell2content = userdate($submission->timemodified);
            } else {
                $cell2content = "-";
            }

            $this->add_table_row_tuple($t, $cell1content, $cell2content);

            if (!$status->teamsubmission || $status->submissiongroup != false || !$status->preventsubmissionnotingroup) {
                foreach ($status->submissionplugins as $plugin) {
                    $pluginshowsummary = !$plugin->is_empty($submission) || !$plugin->allow_submissions();
                    if ($plugin->is_enabled() &&
                        $plugin->is_visible() &&
                        $plugin->has_user_summary() &&
                        $pluginshowsummary
                    ) {

                        $cell1content = $plugin->get_name();
                        $displaymode = \assign_submission_plugin_submission::SUMMARY;
                        $pluginsubmission = new \assign_submission_plugin_submission($plugin,
                            $submission,
                            $displaymode,
                            $status->coursemoduleid,
                            $status->returnaction,
                            $status->returnparams);
                        $cell2content = $this->render($pluginsubmission);
                        $this->add_table_row_tuple($t, $cell1content, $cell2content);
                    }
                }
            }
        }

        $o .= $warningmsg;
        $o .= \html_writer::table($t);
        $o .= $this->output->box_end();

        // Grading criteria preview.
        if (!empty($status->gradingcontrollerpreview)) {
            $o .= $this->output->heading(get_string('gradingmethodpreview', 'assign'), 4);
            $o .= $status->gradingcontrollerpreview;
        }

        $o .= $this->output->container_end();
        return $o;
    }
    public function render_assign_submission_plugin_submission(\assign_submission_plugin_submission $submissionplugin) {
        $o = '';

        if ($submissionplugin->view == \assign_submission_plugin_submission::SUMMARY) {
            $showviewlink = false;
            $summary = $submissionplugin->plugin->view_summary($submissionplugin->submission,
                $showviewlink);

            $classsuffix = $submissionplugin->plugin->get_subtype() .
                '_' .
                $submissionplugin->plugin->get_type() .
                '_' .
                $submissionplugin->submission->id;

            $o .= $this->output->box_start('boxaligncenter plugincontentsummary summary_' . $classsuffix);

            $link = '';
            if ($showviewlink) {
                $previewstr = get_string('viewsubmission', 'assign');
                $icon = $this->output->pix_icon('t/preview', $previewstr);

                $expandstr = get_string('viewfull', 'assign');
                $expandicon = $this->output->pix_icon('t/switch_plus', $expandstr);
                $options = array(
                    'class' => 'expandsummaryicon expand_' . $classsuffix,
                    'aria-label' => $expandstr,
                    'role' => 'button',
                    'aria-expanded' => 'false'
                );
                $o .= \html_writer::link('', $expandicon, $options);

                $jsparams = array($submissionplugin->plugin->get_subtype(),
                    $submissionplugin->plugin->get_type(),
                    $submissionplugin->submission->id);

                $this->page->requires->js_init_call('M.mod_assign.init_plugin_summary', $jsparams);

                $action = 'viewplugin' . $submissionplugin->plugin->get_subtype();
                $returnparams = http_build_query($submissionplugin->returnparams);
                $link .= '<noscript>';
                $urlparams = array('id' => $submissionplugin->coursemoduleid,
                    'sid'=>$submissionplugin->submission->id,
                    'plugin'=>$submissionplugin->plugin->get_type(),
                    'action'=>$action,
                    'returnaction'=>$submissionplugin->returnaction,
                    'returnparams'=>$returnparams);
                $url = new \moodle_url('/mod/assign/view.php', $urlparams);
                $link .= $this->output->action_link($url, $icon);
                $link .= '</noscript>';

                $link .= $this->output->spacer(array('width'=>15));
            }

            $o .= $link . $summary;
            $o .= $this->output->box_end();
            if ($showviewlink) {
                $o .= $this->output->box_start('boxaligncenter hidefull full_' . $classsuffix);
                $collapsestr = get_string('viewsummary', 'assign');
                $options = array(
                    'class' => 'expandsummaryicon contract_' . $classsuffix,
                    'aria-label' => $collapsestr,
                    'role' => 'button',
                    'aria-expanded' => 'true'
                );
                $collapseicon = $this->output->pix_icon('t/switch_minus', $collapsestr);
                $o .= \html_writer::link('', $collapseicon, $options);

                $o .= $submissionplugin->plugin->view($submissionplugin->submission);
                $o .= $this->output->box_end();
            }
        } else if ($submissionplugin->view == \assign_submission_plugin_submission::FULL) {
            $o .= $this->output->box_start('boxaligncenter submissionfull');
            $o .= $submissionplugin->plugin->view($submissionplugin->submission);
            $o .= $this->output->box_end();
        }

        return $o;
    }

    public function render_assign_header(assign_header $header) {
        if ($header->subpage) {
            $this->page->navbar->add($header->subpage, $header->subpageurl);
            $args = ['contextname' => $header->context->get_context_name(false, true), 'subpage' => $header->subpage];
            $title = get_string('subpagetitle', 'assign', $args);
        } else {
            $title = $header->context->get_context_name(false, true);
        }
        $courseshortname = $header->context->get_course_context()->get_context_name(false, true);
        $title = $courseshortname . ': ' . $title;
        $heading = format_string($header->assign->name, false, array('context' => $header->context));

        $this->page->set_title($title);
        $this->page->set_heading($this->page->course->fullname);

        $description = $header->preface;
        if ($header->showintro || $header->activity) {
            $description = $this->output->box_start('generalbox boxaligncenter');
            if ($header->showintro) {
                $description .= format_module_intro('assign', $header->assign, $header->coursemoduleid);
            }
            if ($header->activity) {
                $description .= $this->format_activity_text($header->assign, $header->coursemoduleid);
            }
            $description .= $header->postfix;
            $description .= $this->output->box_end();
        }

        $activityheader = $this->page->activityheader;
        $activityheader->set_attrs([
            'title' => $activityheader->is_title_allowed() ? $heading : '',
            'description' => $description
        ]);

        return $this->output->header();
    }
}