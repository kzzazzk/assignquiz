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
require_once($CFG->dirroot.'/mod/quiz/locallib.php');
require_once($CFG->dirroot.'/mod/assignquiz/locallib.php');
require_once($CFG->dirroot.'/mod/assignquiz/aiassignmentplugin.php');
require_once($CFG->dirroot.'/mod/assignquiz/aisubmissionplugin.php');

/**
 * Module instance settings form.
 *
 * @package     mod_aiquiz
 * @copyright   2024 Zakaria Lasry Sahraou zsahraoui20@gmail.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define("weekdelay",  604800);

class mod_assignquiz_mod_form extends mod_assign_mod_form {
    protected static $reviewfields = array(); // Initialised in the constructor.
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

    protected function standard_intro_elements($customlabel=null,$elementname=null,$displaydescriptionoption=false) {
        global $CFG;

        $required = $CFG->requiremodintro;

        $mform = $this->_form;

        $mform->addElement('editor', $elementname, $customlabel, array('rows' => 10), array('maxfiles' => EDITOR_UNLIMITED_FILES,
            'noclean' => true, 'context' => $this->context, 'subdirs' => true));
        $mform->setType($elementname, PARAM_TEXT); // no XSS prevention here, users must be trusted
        if ($required) {
            $mform->addRule($elementname, get_string('required'), 'required', null, 'client');
        }

        // If the 'show description' feature is enabled, this checkbox appears below the intro.
        // We want to hide that when using the singleactivity course format because it is confusing.
        if($displaydescriptionoption) {
            $mform->addElement('advcheckbox', 'showdescription', get_string('showdescription'));
            $mform->addHelpButton('showdescription', 'showdescription');
        }
    }
    public function general_header_definition($mform)
    {
        global $COURSE, $PAGE;
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
        $mform->addElement('textarea', 'requiredknowledge', get_string('activitydescription', 'assignquiz'), 'wrap="virtual" rows="3" cols="50"');
        $mform->setType('requiredknowledge', PARAM_RAW); // no XSS prevention here, users must be trusted

        /*
        if ($required) {
            $mform->addRule('introeditor', get_string('required'), 'required', null, 'client');
        }
        */
        $this->standard_intro_elements(get_string('submissionphasedescription', 'assignquiz'), 'assignintro');
        $this->standard_intro_elements(get_string('quizphasedescription', 'assignquiz'), 'quizintro', true);
    }
    private function assignment_form($mform){
        global $COURSE, $CFG, $DB, $PAGE;

        $mform->addElement('header', 'aiassignconfigtitle', get_string('aiassignconfigtitle', 'assignquiz'));
        $mform->addElement('header', 'basicsettings', get_string('basicsettings', 'assignquiz'));
        $mform->setExpanded('basicsettings', true);

        // Activity.
        $mform->addElement('editor', 'activityeditor',
            get_string('assigninstructions', 'assignquiz'), array('rows' => 10), array('maxfiles' => EDITOR_UNLIMITED_FILES,
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
        $mform->addElement('header', 'availability', get_string('assignmenttiming', 'assignquiz'));
        $mform->setExpanded('availability', true);

        $name = get_string('allowsubmissionsfromdate', 'assign');
        $options = array('optional'=>true);
        $mform->addElement('date_time_selector', 'allowsubmissionsfromdate', $name, $options);
        $mform->setDefault('allowsubmissionsfromdate', time());
        $mform->addHelpButton('allowsubmissionsfromdate', 'allowsubmissionsfromdate', 'assign');

        $name = get_string('duedate', 'assign');
        $mform->addElement('date_time_selector', 'duedate', $name, array('optional'=>true));
        $mform->setDefault('duedate', time() + constant("weekdelay")); //give a week delay
        $mform->addHelpButton('duedate', 'duedate', 'assign');

        $name = get_string('cutoffdate', 'assign');
        $mform->addElement('date_time_selector', 'cutoffdate', $name, array('optional'=>true));
        $mform->addHelpButton('cutoffdate', 'cutoffdate', 'assign');

        $name = get_string('gradingduedate', 'assign');
        $mform->addElement('date_time_selector', 'gradingduedate', $name, array('optional' => true));
        $mform->setDefault('gradingduedate', time() +  2*constant("weekdelay")); //give a two week delay
        $mform->addHelpButton('gradingduedate', 'gradingduedate', 'assign');

        $timelimitenabled = get_config('assign', 'enabletimelimit');
        // Time limit.
        if ($timelimitenabled) {
            $mform->addElement('duration', 'timelimit', get_string('timelimit', 'assign'),
                array('optional' => true));
            $mform->addHelpButton('timelimit', 'timelimit', 'assign');
        }

        $name = get_string('alwaysshowdescription', 'assign');
        $mform->addElement('advcheckbox', 'alwaysshowdescription', $name);
        $mform->setDefault('alwaysshowdescription', 1);
        $mform->addHelpButton('alwaysshowdescription', 'alwaysshowdescription', 'assign');
        $mform->disabledIf('alwaysshowdescription', 'allowsubmissionsfromdate[enabled]', 'notchecked');
        $ctx = null;
        if ($this->current && $this->current->coursemodule) {
            $cm = get_coursemodule_from_instance('assignquiz', $this->current->id, 0, false, MUST_EXIST);
            $ctx = context_module::instance($cm->id);
        }
        $assignment = new aiassign($this->context, null, null);

//        $mform->addElement('select', 'assignsubmission_file_maxfiles', $name, $options);
//        $mform->addHelpButton('assignsubmission_file_maxfiles',
//            'maxfilessubmission',
//            'assignquizsubmission_file');
//        $mform->setDefault('assignsubmission_file_maxfiles', $defaultmaxfilesubmissions);
//        $mform->hideIf('assignsubmission_file_maxfiles', 'assignsubmission_file_enabled', 'notchecked');

        $assignment->add_all_plugin_settings($mform);

        $mform->addElement('header', 'submissionsettings', get_string('submissionsettings', 'assign'));
        $name = get_string('submissiondrafts', 'assign');
        $mform->addElement('selectyesno', 'submissiondrafts', $name);
        $mform->addHelpButton('submissiondrafts', 'submissiondrafts', 'assign');

        if ($assignment->has_submissions_or_grades()) {
            $mform->freeze('submissiondrafts');
        }

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
        if ($assignment->has_submissions_or_grades()) {
            $mform->freeze('teamsubmission');
        }
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
        if ($assignment->has_submissions_or_grades()) {
            $mform->freeze('teamsubmissiongroupingid');
        }
        $mform->addElement('header', 'notifications', get_string('notifications', 'assign'));

        $name = get_string('sendnotifications', 'assign');
        $mform->addElement('selectyesno', 'sendnotifications', $name);
        $mform->addHelpButton('sendnotifications', 'sendnotifications', 'assign');

        $name = get_string('sendlatenotifications', 'assign');
        $mform->addElement('selectyesno', 'sendlatenotifications', $name);
        $mform->addHelpButton('sendlatenotifications', 'sendlatenotifications', 'assign');
        $mform->disabledIf('sendlatenotifications', 'sendnotifications', 'eq', 1);

        $name = get_string('sendstudentnotificationsdefault', 'assign');
        $mform->addElement('selectyesno', 'sendstudentnotifications', $name);
        $mform->addHelpButton('sendstudentnotifications', 'sendstudentnotificationsdefault','assign');
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
        $mform->setDefault('gradepass', 5);
        $mform->addElement('text', 'mingrade', get_string('mingrade', 'assignquiz'));$mform->setType('mingrade', PARAM_INT);  // Setting the type for the 'mingrade' field
        $mform->setType('mingrade', PARAM_FLOAT);  // Setting the type for the 'mingrade' field
        $mform->setDefault('mingrade', 0);
        $mform->addElement('text', 'maxgrade', get_string('maxgrade', 'assignquiz'));
        $mform->setType('maxgrade', PARAM_FLOAT);  // Setting the type for the 'mingrade' field
        $mform->setDefault('maxgrade', 10);
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
        if ($this->get_max_attempts_for_any_override() < 2) {
            $mform->hideIf('grademethod', 'attempts', 'eq', 1);
        }

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
        $mform->setDefault('preferredbehaviour', 'deferredfeedback');
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
        if ($this->get_max_attempts_for_any_override() < 2) {
            $mform->hideIf('attemptonlast', 'attempts', 'eq', 1);
        }
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
        if ($this->get_max_attempts_for_any_override() < 2) {
           $mform->hideIf('delay1', 'attempts', 'eq', 1);
        }

        $mform->addElement('duration', 'delay2', get_string('delaylater', 'quiz'),
            array('optional' => true));
        $mform->addHelpButton('delay2', 'delaylater', 'quiz');
        if ($this->get_max_attempts_for_any_override() < 3) {
            $mform->hideIf('delay2', 'attempts', 'eq', 1);
            $mform->hideIf('delay2', 'attempts', 'eq', 2);
        }

        // Browser security choices.
        $mform->addElement('select', 'browsersecurity', get_string('browsersecurity', 'quiz'),
            quiz_access_manager::get_browser_security_choices());
        $mform->addHelpButton('browsersecurity', 'browsersecurity', 'quiz');

        $modquizform = new mod_quiz_mod_form($this->get_current(),$this->_customdata['current'],null , $this->get_course());
        // Any other rule plugins.
        quiz_access_manager::add_settings_form_fields($modquizform, $mform);

        // -------------------------------------------------------------------------------

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

    /**
     * Defines form behaviour after being defined
     */
    public function definition_after_data() {
        parent::definition_after_data();
    }
    public function get_max_attempts_for_any_override() {
        global $DB;

        if (empty($this->_instance)) {
            // Quiz not created yet, so no overrides.
            return 1;
        }

        if ($this->maxattemptsanyoverride === null) {
            $this->maxattemptsanyoverride = $DB->get_field_sql("
                    SELECT MAX(CASE WHEN attempts = 0 THEN 1000 ELSE attempts END)
                      FROM {quiz_overrides}
                     WHERE quiz = ?",
                array($this->_instance));
            if ($this->maxattemptsanyoverride < 1) {
                // This happens when no override alters the number of attempts.
                $this->maxattemptsanyoverride = 1;
            }
        }

        return $this->maxattemptsanyoverride;
    }

    public function data_preprocessing(&$defaultvalues) {
        global $DB;

        $cmid = $this->_instance;
        $assignexists = $DB->get_record('aiassign', array('assignquizid' => $cmid));
        $quizexists = $DB->get_record('aiquiz', array('assignquizid' => $cmid));
        $assignquizexists = $DB->get_record('assignquiz', array('id' => $cmid));

        if($cmid && $quizexists && $assignexists){
            $elementdraftitemid = file_get_submitted_draft_itemid('intro');
            $defaultvalues['intro'] = array(
                'text' => $assignquizexists->intro,
                'format' => $assignquizexists->introformat,
                'itemid' => $elementdraftitemid
            );
            $defaultvalues['showdescription'] = $DB->get_field('course_modules','showdescription',['instance'=>$cmid],MUST_EXIST);
            $this->assign_preprocessing($assignexists, $defaultvalues);
            $this->quiz_preprocessing($quizexists, $assignquizexists->id,$defaultvalues);
        }
    }

    public function assign_preprocessing($assigndata, &$defaultvalues) {
        $elementdraftitemid = file_get_submitted_draft_itemid('activityeditor');
        $defaultvalues['activityeditor'] = array(
            'text' => $assigndata->activity,
            'format' => $assigndata->activityformat,
            'itemid' => $elementdraftitemid
        );
        $elementdraftitemid = file_get_submitted_draft_itemid('assignintro');

        $defaultvalues['assignintro'] = array(
            'text' => $assigndata->assignintro,
            'format' => $assigndata->assignintroformat,
            'itemid' => $elementdraftitemid
        );
        $defaultvalues['duedate'] = $assigndata->duedate;
        $defaultvalues['cutoffdate'] = $assigndata->cutoffdate;
        $defaultvalues['allowsubmissionsfromdate'] = $assigndata->allowsubmissionsfromdate;
        $defaultvalues['gradeduedate'] = $assigndata->gradeduedate;
        $defaultvalues['alwaysshowdescription'] = $assigndata->alwaysshowdescription;
        $assignment = new aiassign($this->context, null, null);
        $assignment->plugin_data_preprocessing($defaultvalues);

        $defaultvalues['submissiondrafts'] = $assigndata->submissiondrafts;
        $defaultvalues['requiresubmissionstatement'] = $assigndata->requiresubmissionstatement;
        $defaultvalues['attemptreopenmethod'] = $assigndata->attemptreopenmethod;
        $defaultvalues['maxattempts'] = $assigndata->maxattempts;

        $defaultvalues['teamsubmission'] = $assigndata->teamsubmission;
        $defaultvalues['preventsubmissionnotingroup'] = $assigndata->preventsubmissionnotingroup;
        $defaultvalues['requireallteammemberssubmit'] = $assigndata->requireallteammemberssubmit;
        $defaultvalues['teamsubmissiongroupingid'] = $assigndata->teamsubmissiongroupingid;

        $defaultvalues['sendnotifications'] = $assigndata->sendnotifications;
        $defaultvalues['sendlatenotifications'] = $assigndata->sendlatenotifications;
        $defaultvalues['sendstudentnotifications'] = $assigndata->sendstudentnotifications;

    }

    protected function preprocessing_review_settings(&$toform, $whenname, $when) {
        foreach (self::$reviewfields as $field => $notused) {
            $fieldname = 'review' . $field;
            if (array_key_exists($fieldname, $toform)) {
                $toform[$field . $whenname] = $toform[$fieldname] & $when;
            }
        }
    }

    public function quiz_preprocessing($assigndata,$assignquizid, &$defaultvalues) {
        global $DB;
        $elementdraftitemid = file_get_submitted_draft_itemid('quizintro');
        $defaultvalues['quizintro'] = array(
            'text' => $assigndata->quizintro,
            'format' => $assigndata->quizintroformat,
            'itemid' => $elementdraftitemid
        );

        $defaultvalues['timeopen'] = $assigndata->timeopen;
        $defaultvalues['timeclose'] = $assigndata->timeclose;
        $defaultvalues['timelimit'] = $assigndata->timelimit;
        $defaultvalues['overduehandling'] = $assigndata->overduehandling;
        $defaultvalues['graceperiod'] = $assigndata->graceperiod;

        $defaultvalues['gradecat'] = $assigndata->gradecat;

        $inserted_into_grade_items = $DB->record_exists('grade_items', ['iteminstance' => $assignquizid]);
        $defaultvalues['gradepass'] = !$inserted_into_grade_items
            ? 5
            : $DB->get_field('grade_items', 'gradepass', ['iteminstance' => $assignquizid,'itemmodule' => 'assignquiz'], MUST_EXIST);

        $defaultvalues['maxgrade'] = !$inserted_into_grade_items
            ? 10
            : $DB->get_field('grade_items', 'grademax', ['iteminstance' => $assignquizid,'itemmodule' => 'assignquiz'], MUST_EXIST);

        $defaultvalues['mingrade'] = !$inserted_into_grade_items
            ? 0
            : $DB->get_field('grade_items', 'grademin', ['iteminstance' => $assignquizid,'itemmodule' => 'assignquiz'], MUST_EXIST);
        $defaultvalues['attempts'] = $assigndata->attempts;
        $defaultvalues['grademethod'] = $assigndata->grademethod;

        $defaultvalues['questionsperpage'] = $assigndata->questionsperpage;
        $defaultvalues['repaginatenow'] = $assigndata->repaginatenow;
        $defaultvalues['navmethod'] = $assigndata->navmethod;

        $defaultvalues['shuffleanswers'] = $assigndata->shuffleanswers;
        $defaultvalues['preferredbehaviour'] = $assigndata->preferredbehaviour;
        $defaultvalues['canredoquestions'] = $assigndata->canredoquestions;
        $defaultvalues['attemptonlast'] = $assigndata->attemptonlast;

        $this->preprocessing_review_settings($defaultvalues, 'during',
            mod_quiz_display_options::DURING);
        $this->preprocessing_review_settings($defaultvalues, 'immediately',
            mod_quiz_display_options::IMMEDIATELY_AFTER);
        $this->preprocessing_review_settings($defaultvalues, 'open',
            mod_quiz_display_options::LATER_WHILE_OPEN);
        $this->preprocessing_review_settings($defaultvalues, 'closed',
            mod_quiz_display_options::AFTER_CLOSE);

        $defaultvalues['showuserpicture'] = $assigndata->showuserpicture;
        $defaultvalues['decimalpoints'] = $assigndata->decimalpoints;
        $defaultvalues['questiondecimalpoints'] = $assigndata->questiondecimalpoints;
        $defaultvalues['showblocks'] = $assigndata->showblocks;

        $defaultvalues['quizpassword'] = $assigndata->password;
        $defaultvalues['subnet'] = $assigndata->subnet;
        $defaultvalues['delay1'] = $assigndata->delay1;
        $defaultvalues['delay2'] = $assigndata->delay2;
        $defaultvalues['browsersecurity'] = $assigndata->browsersecurity;
    }
}
