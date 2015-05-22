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

namespace local_recyclebin;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

/**
 * Main class for the recycle bin.
 */
class Observer
{
    /**
     * Main hook.
     *
     * @param \stdClass $cm The course module record.
     */
    public static function pre_cm_delete($cm) {
        global $CFG, $DB;

        // Get more information.
        $modinfo = get_fast_modinfo($cm->course);
        $cminfo = $modinfo->cms[$cm->id];

        // Record the activity.
        $binid = $DB->insert_record('local_recyclebin', array(
            'course' => $cm->course,
            'section' => $cm->section,
            'module' => $cm->module,
            'name' => $cminfo->name
        ));

        // Backup user.
        $user = get_admin();

        // Backup the activity.
        $controller = new \backup_controller(\backup::TYPE_1ACTIVITY, $cm->id, \backup::FORMAT_MOODLE, \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $user->id);
        $controller->execute_plan();

        // Grab the result.
        $result = $controller->get_results();
        if (!isset($result['backup_destination'])) {
            throw new \moodle_exception('Failed to backup activity prior to deletion.');
        }

        // Grab the filename.
        $file = $result['backup_destination'];
        if (!$file->get_contenthash()) {
            throw new \moodle_exception('Failed to backup activity prior to deletion (invalid file).');
        }

        // Make sure our backup dir exists.
        $bindir = $CFG->dataroot . '/recyclebin';
        if (!file_exists($bindir)) {
            make_writable_directory($bindir);
        }

        // Move the file to our own special little place.
        $file->copy_content_to($bindir . '/' . $binid);
        $file->delete();
    }
}
