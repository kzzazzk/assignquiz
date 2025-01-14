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

namespace mod_assignquiz\task;
use core\task\scheduled_task;
global $CFG;

require_once($CFG->dirroot . '/mod/assignquiz/lib.php');

class phase_switch_task extends scheduled_task {
    public function get_name() {
        return get_string('phase_switch_task', 'mod_assignquiz');
    }

    public function execute() {
        global $DB;
        $now = time();
        $records = $DB->get_records_sql(
            "SELECT * FROM {assignquiz} 
                 WHERE phase = :phase 
                 AND duedate <= :now",
            array(
                'phase' => PHASE_SUBMISSION,
                'now' => $now
            )
        );
        foreach ($records as $record) {
            $record->phase = PHASE_QUIZ;
            $DB->update_record('assignquiz', $record);
            rebuild_course_cache($record->course, true);
        }
    }
}
