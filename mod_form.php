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
 * The main mod_aiquiz configuration form.
 *
 * @package     mod_aiquiz
 * @copyright   2024 Zakaria Lasry Sahraou zsahraoui20@gmail.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/quiz/mod_form.php');

require_once($CFG->dirroot.'/mod/assign/locallib.php');

/**
 * Module instance settings form.
 *
 * @package     mod_aiquiz
 * @copyright   2024 Zakaria Lasry Sahraou zsahraoui20@gmail.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_assignquiz_mod_form extends moodleform_mod {
    protected static $reviewfields = array(); // Initialised in the constructor.
    protected $quiz_form;
    /**
     * Defines forms elements
     */
    public function definition() {
        self::$reviewfields = array(
            'attempt'          => array('theattempt', 'quiz'),
            'correctness'      => array('whethercorrect', 'question'),
            'marks'            => array('marks', 'quiz'),
            'specificfeedback' => array('specificfeedback', 'question'),
            'generalfeedback'  => array('generalfeedback', 'question'),
            'rightanswer'      => array('rightanswer', 'question'),
            'overallfeedback'  => array('reviewoverallfeedback', 'quiz'),
        );
        $mform = $this->_form;
        $this->general_header_definition($mform);
        $this->assignment_form($mform);
        $this->quiz_form($mform);

    }
    public function general_header_definition($mform)
    {
        global $COURSE;
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Name.
        $mform->addElement('text', 'name', get_string('activityname', 'assignquiz'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Introduction.
        $mform->addElement('editor', 'introeditor', get_string('activitydescription','assignquiz'), array('rows' => 10), array('maxfiles' => EDITOR_UNLIMITED_FILES,
            'noclean' => true, 'context' => $this->context, 'subdirs' => true));
        $mform->setType('introeditor', PARAM_RAW); // no XSS prevention here, users must be trusted

        /*
        if ($required) {
            $mform->addRule('introeditor', get_string('required'), 'required', null, 'client');
        }
        */
        $mform->addHelpButton('introeditor', 'requiredknowledge', 'assignquiz');


        // If the 'show description' feature is enabled, this checkbox appears below the intro.
        // We want to hide that when using the singleactivity course format because it is confusing.
        if ($this->_features->showdescription  && $this->courseformat->has_view_page()) {
            $mform->addElement('advcheckbox', 'showdescription', get_string('showdescription'));
            $mform->addHelpButton('showdescription', 'showdescription');
        }
        // Activity.
        $mform->addElement('editor', 'activityeditor',
            get_string('activityeditor', 'assignquiz'), array('rows' => 10), array('maxfiles' => EDITOR_UNLIMITED_FILES,
                'noclean' => true, 'context' => $this->context, 'subdirs' => true));
        $mform->addHelpButton('activityeditor', 'activityeditor', 'assign');
        $mform->setType('activityeditor', PARAM_RAW);

        $mform->addElement('filemanager', 'introattachments',
            get_string('introattachments', 'assign'),
            null, array('subdirs' => 0, 'maxbytes' => $COURSE->maxbytes) );
        $mform->addHelpButton('introattachments', 'introattachments', 'assign');

        $mform->addElement('advcheckbox', 'submissionattachments', get_string('submissionattachments', 'assign'));
        $mform->addHelpButton('submissionattachments', 'submissionattachments', 'assign');
        // --------------------------------------------------------------------------------------
    }
    public function quiz_form($mform){
        global $COURSE, $CFG, $DB, $PAGE;
        //$quiz_form = new mod_quiz_mod_form($this->get_current(),$this->_customdata['current'],null , $this->get_course());

        $quizconfig = get_config('quiz');
        $mform->addElement('header', 'title', get_string('aiquizconfigtitle', 'assignquiz'));

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'quiztiming', get_string('quiztiming', 'assignquiz'));
        $mform->setExpanded('quiztiming', true);

        $mform->addRule('name', null, 'required', null, 'client');

        // Open and close dates.
        $mform->addElement('date_time_selector', 'timeopen', get_string('quizopen', 'quiz'),
            mod_quiz_mod_form::$datefieldoptions);
        $mform->addHelpButton('timeopen', 'quizopenclose', 'quiz');

        $mform->addElement('date_time_selector', 'timeclose', get_string('quizclose', 'quiz'),
            mod_quiz_mod_form::$datefieldoptions);

        // Time limit.
        $mform->addElement('duration', 'timelimit', get_string('timelimit', 'quiz'),
            array('optional' => true));
        $mform->setDefault('timeopen', time());
        $mform->addHelpButton('timelimit', 'timelimit', 'quiz');

        // What to do with overdue attempts.
        $mform->addElement('select', 'overduehandling', get_string('overduehandling', 'quiz'),
            quiz_get_overdue_handling_options());
        $mform->addHelpButton('overduehandling', 'overduehandling', 'quiz');
        // TODO Formslib does OR logic on disableif, and we need AND logic here.
         $mform->disabledIf('overduehandling', 'timelimit', 'eq', 0);
         $mform->disabledIf('overduehandling', 'timeclose', 'eq', 0);

        // Grace period time.
        $mform->addElement('duration', 'graceperiod', get_string('graceperiod', 'quiz'),
            array('optional' => true));
        $mform->addHelpButton('graceperiod', 'graceperiod', 'quiz');
        $mform->hideIf('graceperiod', 'overduehandling', 'neq', 'graceperiod');

        // -------------------------------------------------------------------------------
        // Grade settings.
        $this->standard_grading_coursemodule_elements();

        $mform->removeElement('grade');
        if (property_exists($this->current, 'grade')) {
            $currentgrade = $this->current->grade;
        } else {
            $currentgrade = $quizconfig->maximumgrade;
        }
        $mform->addElement('hidden', 'grade', $currentgrade);
        $mform->setType('grade', PARAM_FLOAT);

        // Number of attempts.
        $attemptoptions = array('0' => get_string('unlimited'));
        for ($i = 1; $i <= QUIZ_MAX_ATTEMPT_OPTION; $i++) {
            $attemptoptions[$i] = $i;
        }
        $mform->addElement('select', 'attempts', get_string('attemptsallowed', 'quiz'),
            $attemptoptions);

        // Grading method.
        $mform->addElement('select', 'grademethod', get_string('grademethod', 'quiz'),
            quiz_get_grading_options());
        $mform->addHelpButton('grademethod', 'grademethod', 'quiz');
//        if ($this->get_max_attempts_for_any_override() < 2) {
//            $mform->hideIf('grademethod', 'attempts', 'eq', 1);
//        }

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'layouthdr', get_string('layout', 'quiz'));

        $pagegroup = array();
        $pagegroup[] = $mform->createElement('select', 'questionsperpage',
            get_string('newpage', 'quiz'), quiz_questions_per_page_options(), array('id' => 'id_questionsperpage'));
        $mform->setDefault('questionsperpage', $quizconfig->questionsperpage);

        if (!empty($this->_cm) && !quiz_has_attempts($this->_cm->instance)) {
            $pagegroup[] = $mform->createElement('checkbox', 'repaginatenow', '',
                get_string('repaginatenow', 'quiz'), array('id' => 'id_repaginatenow'));
        }

        $mform->addGroup($pagegroup, 'questionsperpagegrp',
            get_string('newpage', 'quiz'), null, false);
        $mform->addHelpButton('questionsperpagegrp', 'newpage', 'quiz');
        $mform->setAdvanced('questionsperpagegrp', $quizconfig->questionsperpage_adv);

        // Navigation method.
        $mform->addElement('select', 'navmethod', get_string('navmethod', 'quiz'),
            quiz_get_navigation_options());
        $mform->addHelpButton('navmethod', 'navmethod', 'quiz');

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'interactionhdr', get_string('questionbehaviour', 'quiz'));

        // Shuffle within questions.
        $mform->addElement('selectyesno', 'shuffleanswers', get_string('shufflewithin', 'quiz'));
        $mform->addHelpButton('shuffleanswers', 'shufflewithin', 'quiz');

        // How questions behave (question behaviour).
        if (!empty($this->current->preferredbehaviour)) {
            $currentbehaviour = $this->current->preferredbehaviour;
        } else {
            $currentbehaviour = '';
        }
        $behaviours = question_engine::get_behaviour_options($currentbehaviour);
        $mform->addElement('select', 'preferredbehaviour',
            get_string('howquestionsbehave', 'question'), $behaviours);
        $mform->addHelpButton('preferredbehaviour', 'howquestionsbehave', 'question');

        // Can redo completed questions.
        $redochoices = array(0 => get_string('no'), 1 => get_string('canredoquestionsyes', 'quiz'));
        $mform->addElement('select', 'canredoquestions', get_string('canredoquestions', 'quiz'), $redochoices);
        $mform->addHelpButton('canredoquestions', 'canredoquestions', 'quiz');
        foreach ($behaviours as $behaviour => $notused) {
            if (!question_engine::can_questions_finish_during_the_attempt($behaviour)) {
                $mform->hideIf('canredoquestions', 'preferredbehaviour', 'eq', $behaviour);
            }
        }

        // Each attempt builds on last.
        $mform->addElement('selectyesno', 'attemptonlast',
            get_string('eachattemptbuildsonthelast', 'quiz'));
        $mform->addHelpButton('attemptonlast', 'eachattemptbuildsonthelast', 'quiz');
//        if ($this->get_max_attempts_for_any_override() < 2) {
//            $mform->hideIf('attemptonlast', 'attempts', 'eq', 1);
//        }

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'reviewoptionshdr',
            get_string('reviewoptionsheading', 'quiz'));
        $mform->addHelpButton('reviewoptionshdr', 'reviewoptionsheading', 'quiz');

        // Review options.
        $this->add_review_options_group($mform, $quizconfig, 'during',
            mod_quiz_display_options::DURING, true);
        $this->add_review_options_group($mform, $quizconfig, 'immediately',
            mod_quiz_display_options::IMMEDIATELY_AFTER);
        $this->add_review_options_group($mform, $quizconfig, 'open',
            mod_quiz_display_options::LATER_WHILE_OPEN);
        $this->add_review_options_group($mform, $quizconfig, 'closed',
            mod_quiz_display_options::AFTER_CLOSE);

        foreach ($behaviours as $behaviour => $notused) {
            $unusedoptions = question_engine::get_behaviour_unused_display_options($behaviour);
            foreach ($unusedoptions as $unusedoption) {
                $mform->disabledIf($unusedoption . 'during', 'preferredbehaviour',
                    'eq', $behaviour);
            }
        }
        $mform->disabledIf('attemptduring', 'preferredbehaviour',
            'neq', 'wontmatch');
        $mform->disabledIf('overallfeedbackduring', 'preferredbehaviour',
            'neq', 'wontmatch');

        foreach (self::$reviewfields as $field => $notused) {
            $mform->disabledIf($field . 'closed', 'timeclose[enabled]');
        }

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'display', get_string('appearance'));

        // Show user picture.
        $mform->addElement('select', 'showuserpicture', get_string('showuserpicture', 'quiz'),
            quiz_get_user_image_options());
        $mform->addHelpButton('showuserpicture', 'showuserpicture', 'quiz');

        // Overall decimal points.
        $options = array();
        for ($i = 0; $i <= QUIZ_MAX_DECIMAL_OPTION; $i++) {
            $options[$i] = $i;
        }
        $mform->addElement('select', 'decimalpoints', get_string('decimalplaces', 'quiz'),
            $options);
        $mform->addHelpButton('decimalpoints', 'decimalplaces', 'quiz');

        // Question decimal points.
        $options = array(-1 => get_string('sameasoverall', 'quiz'));
        for ($i = 0; $i <= QUIZ_MAX_Q_DECIMAL_OPTION; $i++) {
            $options[$i] = $i;
        }
        $mform->addElement('select', 'questiondecimalpoints',
            get_string('decimalplacesquestion', 'quiz'), $options);
        $mform->addHelpButton('questiondecimalpoints', 'decimalplacesquestion', 'quiz');

        // Show blocks during quiz attempt.
        $mform->addElement('selectyesno', 'showblocks', get_string('showblocks', 'quiz'));
        $mform->addHelpButton('showblocks', 'showblocks', 'quiz');

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'security', get_string('extraattemptrestrictions', 'quiz'));

        // Require password to begin quiz attempt.
        $mform->addElement('passwordunmask', 'quizpassword', get_string('requirepassword', 'quiz'));
        $mform->setType('quizpassword', PARAM_TEXT);
        $mform->addHelpButton('quizpassword', 'requirepassword', 'quiz');

        // IP address.
        $mform->addElement('text', 'subnet', get_string('requiresubnet', 'quiz'));
        $mform->setType('subnet', PARAM_TEXT);
        $mform->addHelpButton('subnet', 'requiresubnet', 'quiz');

        // Enforced time delay between quiz attempts.
        $mform->addElement('duration', 'delay1', get_string('delay1st2nd', 'quiz'),
            array('optional' => true));
        $mform->addHelpButton('delay1', 'delay1st2nd', 'quiz');
//        if ($this->get_max_attempts_for_any_override() < 2) {
//           $mform->hideIf('delay1', 'attempts', 'eq', 1);
//        }

        $mform->addElement('duration', 'delay2', get_string('delaylater', 'quiz'),
            array('optional' => true));
        $mform->addHelpButton('delay2', 'delaylater', 'quiz');
//        if ($this->get_max_attempts_for_any_override() < 3) {
//            $mform->hideIf('delay2', 'attempts', 'eq', 1);
//            $mform->hideIf('delay2', 'attempts', 'eq', 2);
//        }

        // Browser security choices.
        $mform->addElement('select', 'browsersecurity', get_string('browsersecurity', 'quiz'),
            quiz_access_manager::get_browser_security_choices());
        $mform->addHelpButton('browsersecurity', 'browsersecurity', 'quiz');

        // Any other rule plugins.
//        quiz_access_manager::add_settings_form_fields($this, $mform);

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'overallfeedbackhdr', get_string('overallfeedback', 'quiz'));
        $mform->addHelpButton('overallfeedbackhdr', 'overallfeedback', 'quiz');

        if (isset($this->current->grade)) {
            $needwarning = $this->current->grade === 0;
        } else {
            $needwarning = $quizconfig->maximumgrade == 0;
        }
        if ($needwarning) {
            $mform->addElement('static', 'nogradewarning', '',
                get_string('nogradewarning', 'quiz'));
        }

        $mform->addElement('static', 'gradeboundarystatic1',
            get_string('gradeboundary', 'quiz'), '100%');

        $repeatarray = array();
        $repeatedoptions = array();
        $repeatarray[] = $mform->createElement('editor', 'feedbacktext',
            get_string('feedback', 'quiz'), array('rows' => 3), array('maxfiles' => EDITOR_UNLIMITED_FILES,
                'noclean' => true, 'context' => $this->context));
        $repeatarray[] = $mform->createElement('text', 'feedbackboundaries',
            get_string('gradeboundary', 'quiz'), array('size' => 10));
        $repeatedoptions['feedbacktext']['type'] = PARAM_RAW;
        $repeatedoptions['feedbackboundaries']['type'] = PARAM_RAW;

        if (!empty($this->_instance)) {
            $this->_feedbacks = $DB->get_records('quiz_feedback',
                array('quizid' => $this->_instance), 'mingrade DESC');
            $numfeedbacks = count($this->_feedbacks);
        } else {
            $this->_feedbacks = array();
            $numfeedbacks = $quizconfig->initialnumfeedbacks;
        }
        $numfeedbacks = max($numfeedbacks, 1);

        $nextel = $this->repeat_elements($repeatarray, $numfeedbacks - 1,
            $repeatedoptions, 'boundary_repeats', 'boundary_add_fields', 3,
            get_string('addmoreoverallfeedbacks', 'quiz'), true);

        // Put some extra elements in before the button.
        $mform->insertElementBefore($mform->createElement('editor',
            "feedbacktext[$nextel]", get_string('feedback', 'quiz'), array('rows' => 3),
            array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true,
                'context' => $this->context)),
            'boundary_add_fields');
        $mform->insertElementBefore($mform->createElement('static',
            'gradeboundarystatic2', get_string('gradeboundary', 'quiz'), '0%'),
            'boundary_add_fields');

        // Add the disabledif rules. We cannot do this using the $repeatoptions parameter to
        // repeat_elements because we don't want to dissable the first feedbacktext.
        for ($i = 0; $i < $nextel; $i++) {
            $mform->disabledIf('feedbackboundaries[' . $i . ']', 'grade', 'eq', 0);
            $mform->disabledIf('feedbacktext[' . ($i + 1) . ']', 'grade', 'eq', 0);
        }

        $mform->addElement('header', 'coursemoduleelements', get_string('coursemoduleconfigtitle', 'assignquiz'));
        // -------------------------------------------------------------------------------
        $this->standard_coursemodule_elements();
        // Check and act on whether setting outcomes is considered an advanced setting.
        $mform->setAdvanced('modoutcomes', !empty($quizconfig->outcomes_adv));

        // The standard_coursemodule_elements method sets this to 100, but the
        // quiz has its own setting, so use that.
        $mform->setDefault('grade', $quizconfig->maximumgrade);

        // -------------------------------------------------------------------------------
        $this->apply_admin_defaults();
        $this->add_action_buttons();

        $PAGE->requires->yui_module('moodle-mod_quiz-modform', 'M.mod_quiz.modform.init');
    }

    private function assignment_form($mform){
        global $COURSE, $CFG, $DB, $PAGE;
        $mform->addElement('header', 'assignformtitle', get_string('aiassignconfigtitle', 'assignquiz'));
        $mform->addElement('header', 'availability', get_string('assignmenttiming', 'assignquiz'));
        $mform->setExpanded('availability', true);

        $name = get_string('allowsubmissionsfromdate', 'assign');
        $options = array('optional'=>true);
        $mform->addElement('date_time_selector', 'allowsubmissionsfromdate', $name, $options);
        $mform->setDefault('allowsubmissionsfromdate', time());
        $mform->addHelpButton('allowsubmissionsfromdate', 'allowsubmissionsfromdate', 'assign');

        $name = get_string('duedate', 'assign');
        $mform->addElement('date_time_selector', 'duedate', $name, array('optional'=>true));
        $mform->setDefault('duedate', time());
        $mform->addHelpButton('duedate', 'duedate', 'assign');

        $name = get_string('cutoffdate', 'assign');
        $mform->addElement('date_time_selector', 'cutoffdate', $name, array('optional'=>true));
        $mform->addHelpButton('cutoffdate', 'cutoffdate', 'assign');

        $name = get_string('gradingduedate', 'assign');
        $mform->addElement('date_time_selector', 'gradingduedate', $name, array('optional' => true));
        $mform->setDefault('gradingduedate', time());
        $mform->addHelpButton('gradingduedate', 'gradingduedate', 'assign');

        $timelimitenabled = get_config('assign', 'enabletimelimit');
        // Time limit.
        if ($timelimitenabled) {
            $mform->addElement('duration', 'timelimit', get_string('timelimit', 'assign'),
                array('optional' => true));
            $mform->addHelpButton('timelimit', 'timelimit', 'assign');
        }

        $name = get_string('alwaysshowdescription', 'assign');
        $mform->addElement('checkbox', 'alwaysshowdescription', $name);
        $mform->addHelpButton('alwaysshowdescription', 'alwaysshowdescription', 'assign');
        $mform->disabledIf('alwaysshowdescription', 'allowsubmissionsfromdate[enabled]', 'notchecked');

        $mform->addElement('header', 'submissiontypes', get_string('submissiontypes', 'assign'));

        $submissionpluginsenabled = array();
        $group = $mform->addGroup(array(), 'submissionplugins', get_string('submissiontypes', 'assign'), array(' '), false);
        foreach ($this->submissionplugins as $plugin) {
            $this->add_plugin_settings($plugin, $mform, $submissionpluginsenabled);
        }
        $group->setElements($submissionpluginsenabled);

        $mform->addElement('header', 'feedbacktypes', get_string('feedbacktypes', 'assign'));
        $feedbackpluginsenabled = array();
        $group = $mform->addGroup(array(), 'feedbackplugins', get_string('feedbacktypes', 'assign'), array(' '), false);
        foreach ($this->feedbackplugins as $plugin) {
            $this->add_plugin_settings($plugin, $mform, $feedbackpluginsenabled);
        }
        $group->setElements($feedbackpluginsenabled);
        $mform->setExpanded('submissiontypes');

        $mform->addElement('header', 'submissionsettings', get_string('submissionsettings', 'assign'));

        $name = get_string('submissiondrafts', 'assign');
        $mform->addElement('selectyesno', 'submissiondrafts', $name);
        $mform->addHelpButton('submissiondrafts', 'submissiondrafts', 'assign');

//        if ($assignment->has_submissions_or_grades()) {
//            $mform->freeze('submissiondrafts');
//        }

        $name = get_string('requiresubmissionstatement', 'assign');
        $mform->addElement('selectyesno', 'requiresubmissionstatement', $name);
        $mform->addHelpButton('requiresubmissionstatement',
            'requiresubmissionstatement',
            'assign');
        $mform->setType('requiresubmissionstatement', PARAM_BOOL);

        $options = array(
            ASSIGN_ATTEMPT_REOPEN_METHOD_NONE => get_string('attemptreopenmethod_none', 'mod_assign'),
            ASSIGN_ATTEMPT_REOPEN_METHOD_MANUAL => get_string('attemptreopenmethod_manual', 'mod_assign'),
            ASSIGN_ATTEMPT_REOPEN_METHOD_UNTILPASS => get_string('attemptreopenmethod_untilpass', 'mod_assign')
        );
        $mform->addElement('select', 'attemptreopenmethod', get_string('attemptreopenmethod', 'mod_assign'), $options);
        $mform->addHelpButton('attemptreopenmethod', 'attemptreopenmethod', 'mod_assign');

        $options = array(ASSIGN_UNLIMITED_ATTEMPTS => get_string('unlimitedattempts', 'mod_assign'));
        $options += array_combine(range(1, 30), range(1, 30));
        $mform->addElement('select', 'maxattempts', get_string('maxattempts', 'mod_assign'), $options);
        $mform->addHelpButton('maxattempts', 'maxattempts', 'assign');
        $mform->hideIf('maxattempts', 'attemptreopenmethod', 'eq', ASSIGN_ATTEMPT_REOPEN_METHOD_NONE);

        $mform->addElement('header', 'groupsubmissionsettings', get_string('groupsubmissionsettings', 'assign'));

        $name = get_string('teamsubmission', 'assign');
        $mform->addElement('selectyesno', 'teamsubmission', $name);
        $mform->addHelpButton('teamsubmission', 'teamsubmission', 'assign');
//        if ($assignment->has_submissions_or_grades()) {
//            $mform->freeze('teamsubmission');
//        }

        $name = get_string('preventsubmissionnotingroup', 'assign');
        $mform->addElement('selectyesno', 'preventsubmissionnotingroup', $name);
        $mform->addHelpButton('preventsubmissionnotingroup',
            'preventsubmissionnotingroup',
            'assign');
        $mform->setType('preventsubmissionnotingroup', PARAM_BOOL);
        $mform->hideIf('preventsubmissionnotingroup', 'teamsubmission', 'eq', 0);

        $name = get_string('requireallteammemberssubmit', 'assign');
        $mform->addElement('selectyesno', 'requireallteammemberssubmit', $name);
        $mform->addHelpButton('requireallteammemberssubmit', 'requireallteammemberssubmit', 'assign');
        $mform->hideIf('requireallteammemberssubmit', 'teamsubmission', 'eq', 0);
        $mform->disabledIf('requireallteammemberssubmit', 'submissiondrafts', 'eq', 0);

//        $groupings = groups_get_all_groupings($assignment->get_course()->id);
//        $options = array();
//        $options[0] = get_string('none');
//        foreach ($groupings as $grouping) {
//            $options[$grouping->id] = $grouping->name;
//        }

        $name = get_string('teamsubmissiongroupingid', 'assign');
        $mform->addElement('select', 'teamsubmissiongroupingid', $name, $options);
        $mform->addHelpButton('teamsubmissiongroupingid', 'teamsubmissiongroupingid', 'assign');
        $mform->hideIf('teamsubmissiongroupingid', 'teamsubmission', 'eq', 0);
//        if ($assignment->has_submissions_or_grades()) {
//            $mform->freeze('teamsubmissiongroupingid');
//        }

        $mform->addElement('header', 'notifications', get_string('notifications', 'assign'));

        $name = get_string('sendnotifications', 'assign');
        $mform->addElement('selectyesno', 'sendnotifications', $name);
        $mform->addHelpButton('sendnotifications', 'sendnotifications', 'assign');

        $name = get_string('sendlatenotifications', 'assign');
        $mform->addElement('selectyesno', 'sendlatenotifications', $name);
        $mform->addHelpButton('sendlatenotifications', 'sendlatenotifications', 'assign');
        $mform->disabledIf('sendlatenotifications', 'sendnotifications', 'eq', 1);

        $name = get_string('sendstudentnotificationsdefault', 'assignquiz');
        $mform->addElement('selectyesno', 'sendstudentnotifications', $name);
        $mform->addHelpButton('sendstudentnotifications', 'sendstudentnotificationsdefault');
    }


    protected function add_review_options_group($mform, $quizconfig, $whenname,
                                                $when, $withhelp = false) {
        global $OUTPUT;

        $group = array();
        foreach (self::$reviewfields as $field => $string) {
            list($identifier, $component) = $string;

            $label = get_string($identifier, $component);
            $group[] = $mform->createElement('html', html_writer::start_div('review_option_item'));
            $el = $mform->createElement('checkbox', $field . $whenname, '', $label);
            if ($withhelp) {
                $el->_helpbutton = $OUTPUT->render(new help_icon($identifier, $component));
            }
            $group[] = $el;
            $group[] = $mform->createElement('html', html_writer::end_div());
        }
        $mform->addGroup($group, $whenname . 'optionsgrp',
            get_string('review' . $whenname, 'quiz'), null, false);

        foreach (self::$reviewfields as $field => $notused) {
            $cfgfield = 'review' . $field;
            if ($quizconfig->$cfgfield & $when) {
                $mform->setDefault($field . $whenname, 1);
            } else {
                $mform->setDefault($field . $whenname, 0);
            }
        }

        if ($whenname != 'during') {
            $mform->disabledIf('correctness' . $whenname, 'attempt' . $whenname);
            $mform->disabledIf('specificfeedback' . $whenname, 'attempt' . $whenname);
            $mform->disabledIf('generalfeedback' . $whenname, 'attempt' . $whenname);
            $mform->disabledIf('rightanswer' . $whenname, 'attempt' . $whenname);
        }
    }

    protected function add_plugin_settings(assign_plugin $plugin, MoodleQuickForm $mform, & $pluginsenabled) {
        global $CFG;
        if ($plugin->is_visible() && !$plugin->is_configurable() && $plugin->is_enabled()) {
            $name = $plugin->get_subtype() . '_' . $plugin->get_type() . '_enabled';
            $pluginsenabled[] = $mform->createElement('hidden', $name, 1);
            $mform->setType($name, PARAM_BOOL);
            $plugin->get_settings($mform);
        } else if ($plugin->is_visible() && $plugin->is_configurable()) {
            $name = $plugin->get_subtype() . '_' . $plugin->get_type() . '_enabled';
            $label = $plugin->get_name();
            $pluginsenabled[] = $mform->createElement('checkbox', $name, '', $label);
            $helpicon = $this->get_renderer()->help_icon('enabled', $plugin->get_subtype() . '_' . $plugin->get_type());
            $pluginsenabled[] = $mform->createElement('static', '', '', $helpicon);

            $default = get_config($plugin->get_subtype() . '_' . $plugin->get_type(), 'default');
            if ($plugin->get_config('enabled') !== false) {
                $default = $plugin->is_enabled();
            }
            $mform->setDefault($plugin->get_subtype() . '_' . $plugin->get_type() . '_enabled', $default);

            $plugin->get_settings($mform);

        }
    }
    /**
     * Defines form behaviour after being defined
     */
    public function definition_after_data() {
        parent::definition_after_data();

        $mform = $this->_form;
        //$this->assignment_availability_modif($mform);
//        $this->submission_settings_modif($mform);
//        $this->group_submissions_modif($mform);
//        $this->notifications_modif($mform);

    }

    private function assignment_availability_modif($mform){
        $availability = $mform->getElement('availability');
        $allowsubmissionsfromdate = $mform->getElement('allowsubmissionsfromdate');
        $duedate = $mform->getElement('duedate');
        $cutoffdate = $mform->getElement('cutoffdate');
        $gradingduedate = $mform->getElement('gradingduedate');
        $alwaysshowdescription = $mform->getElement('alwaysshowdescription');

        $mform->removeElement('availability');
        $mform->removeElement('allowsubmissionsfromdate');
        $mform->removeElement('duedate');
        $mform->removeElement('cutoffdate');
        $mform->removeElement('gradingduedate');
        $mform->removeElement('alwaysshowdescription');

        $mform->insertElementBefore($allowsubmissionsfromdate, 'assignformtitle');
        $mform->insertElementBefore($duedate, 'assignformtitle');
        $mform->insertElementBefore($cutoffdate, 'assignformtitle');
        $mform->insertElementBefore($gradingduedate, 'assignformtitle');
        $mform->insertElementBefore($alwaysshowdescription, 'assignformtitle');
    }

}
