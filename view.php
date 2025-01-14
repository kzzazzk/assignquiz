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
global $PAGE, $USER, $OUTPUT, $DB;

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
require_once(__DIR__.'/locallib.php');

use mod_aiquiz\form\context_form;

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$a = optional_param('a', 0, PARAM_INT);

//
////if ($id) {
//$cm = get_coursemodule_from_id('assignquiz', $id, 0, false, MUST_EXIST);
//$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
//$moduleinstance = $DB->get_record('assignquiz', ['id' => $cm->instance], '*', MUST_EXIST);
//$moduleinstance->intro .= 'mic mic';
//
////} else {
////    $moduleinstance = $DB->get_record('assignquiz', ['id' => $a], '*', MUST_EXIST);
////    $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
////    $cm = get_coursemodule_from_instance('assignquiz', $moduleinstance->id, $course->id, false, MUST_EXIST);
////}
//
//// Check login and get context.
//require_login($course, true, $cm);
//$context = context_module::instance($cm->id);
////$context = \mod_assignquiz\form\assignquiz_context_form::instance($cm->id);
//
//$PAGE->set_context($context);
//
//
//echo $OUTPUT->header();
//
//echo $OUTPUT->footer();

require_once(__DIR__ . '/../../config.php'); // Include Moodle config file.
require_once($CFG->libdir . '/completionlib.php'); // For activity completion tracking.

$id = required_param('id', PARAM_INT); // Course module ID.

$cm = get_coursemodule_from_id('assignquiz', $id, 0, false, MUST_EXIST); // Get course module info.
$course = get_course($cm->course); // Get course info.
$context = context_module::instance($cm->id); // Get context for this module.
$aiassign = new aiassign($context, $cm, $course); // Create a new instance of the module class.
require_login($course,true,$cm); // Authenticate and enforce access control.

$PAGE->set_context($context); // Set the context for the page.

echo $aiassign->view(optional_param('action', '', PARAM_ALPHA));

