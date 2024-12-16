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
 * Implementation of the quizaccess_seb plugin.
 *
 * @package    quizaccess_seb
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use quizaccess_seb\access_manager;
use quizaccess_seb\quiz_settings;
use quizaccess_seb\settings_provider;
use \quizaccess_seb\event\access_prevented;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');

/**
 * Implementation of the quizaccess_seb plugin.
 *
 * @copyright  2020 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class aiquizaccess_seb extends quiz_access_rule_base {

    /** @var access_manager $accessmanager Instance to manage the access to the quiz for this plugin. */
    private $accessmanager;

    public static function save_settings($quiz) {
        $context = context_module::instance($quiz->coursemodule);

        if (!settings_provider::can_configure_seb($context)) {
            return;
        }

        if (settings_provider::is_seb_settings_locked($quiz->id)) {
            return;
        }

        if (settings_provider::is_conflicting_permissions($context)) {
            return;
        }

        $cm = get_coursemodule_from_instance('aiquiz', $quiz->id, $quiz->course, false, MUST_EXIST);
        error_log('***CM value: ' . print_r($cm, true));
        $settings = settings_provider::filter_plugin_settings($quiz);
        $settings->quizid = $quiz->id;
        $settings->cmid = $cm->id;

        // Get existing settings or create new settings if none exist.
        $quizsettings = quiz_settings::get_by_quiz_id($quiz->id);
        if (empty($quizsettings)) {
            $quizsettings = new quiz_settings(0, $settings);
        } else {
            $settings->id = $quizsettings->get('id');
            $quizsettings->from_record($settings);
        }

        // Process uploaded files if required.
        if ($quizsettings->get('requiresafeexambrowser') == settings_provider::USE_SEB_UPLOAD_CONFIG) {
            $draftitemid = file_get_submitted_draft_itemid('filemanager_sebconfigfile');
            settings_provider::save_filemanager_sebconfigfile_draftarea($draftitemid, $cm->id);
        } else {
            settings_provider::delete_uploaded_config_file($cm->id);
        }

        // Save or delete settings.
        if ($quizsettings->get('requiresafeexambrowser') != settings_provider::USE_SEB_NO) {
            $quizsettings->save();
        } else if ($quizsettings->get('id')) {
            $quizsettings->delete();
        }
    }

}
