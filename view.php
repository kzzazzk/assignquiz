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
 * Prints an instance of mod_aiquiz.
 *
 * @package     mod_aiquiz
 * @copyright   2024 Zakaria Lasry z.lsahraoui@alumnos.upm.es
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once(__DIR__.'/classes/event/course_module_viewed.php');
require_once(__DIR__.'/classes/form/context_form.php');

use mod_aiquiz\form\context_form;

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$a = optional_param('a', 0, PARAM_INT);


if ($id) {
    $cm = get_coursemodule_from_id('assignquiz', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('assignquiz', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('assignquiz', ['id' => $a], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('assignquiz', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

// Check login and get context.
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/aiquiz:view', $context);

// Cache some other capabilities we use several times.
$canattempt = has_capability('mod/aiquiz:attempt', $context);
$canreviewmine = has_capability('mod/aiquiz:reviewmyattempts', $context);
$canpreview = has_capability('mod/aiquiz:preview', $context);

$event = \mod_assignquiz\event\assignquiz_course_module_viewed::create([
    'objectid' => $moduleinstance->id,
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('assignquiz', $moduleinstance);
$event->add_record_snapshot('aiquiz', $moduleinstance);
$event->add_record_snapshot('aiassign', $moduleinstance);
$event->trigger();

$PAGE->set_url('/mod/assignquiz/view.php', ['id' => $cm->id, 'sesskey' => sesskey()]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// 1 Add the db part if needed$DB->insert_record('local_greetings_messages', $record);
// 2 require_capability('local/greetings:postmessages', $context);

$contextform = new \mod_assignquiz\form\assignquiz_context_form();
if ($data = $contextform->get_data()) {
    $description = required_param('description', PARAM_TEXT  );

    if (!empty($description)) {
        $record = new stdClass;
        $record->message = $description;
        $record->timecreated = time();
        $record->userid = $USER->id;
    }
}

echo $OUTPUT->header();

echo $OUTPUT->footer();