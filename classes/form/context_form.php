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
 * Plugin version and other meta-data are defined here.
 *
 * @package     mod_aiquiz
 * @copyright   2024 Zakaria Lasry z.lsahraoui@alumnos.upm.es
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_assignquiz\form;
use context;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Class definition
 */
class assignquiz_context_form extends \context_module {
    /**
     * Define the form.
     */
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('textarea', 'description', get_string('yourmessage', 'local_greetings'));
        $mform->setType('description', PARAM_TEXT);
        $submitlabel = get_string('submit');
        $mform->addElement('submit', 'submitmessage', $submitlabel);
    }
    public static function instance($cmid, $strictness = MUST_EXIST) {
        global $DB;

        if ($context = context::cache_get(CONTEXT_MODULE, $cmid)) {
            return $context;
        }

        if (!$record = $DB->get_record('context', array('contextlevel' => CONTEXT_MODULE, 'instanceid' => $cmid))) {
            if ($cm = $DB->get_record('course_modules', array('id' => $cmid), 'id,course', $strictness)) {
                $parentcontext = context_course::instance($cm->course);
                $record = context::insert_context_record(CONTEXT_MODULE, $cm->id, $parentcontext->path);
            }
        }

        if ($record) {
            $context = new context_module($record);
            context::cache_add($context);
            return $context;
        }

        return false;
    }



}