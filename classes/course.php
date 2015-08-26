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
 * The main interface for recycle bin methods.
 *
 * @package    local_recyclebin
 * @copyright  2015 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_recyclebin;

defined('MOODLE_INTERNAL') || die();

/**
 * Represents a course's recyclebin.
 *
 * @package    local_recyclebin
 * @copyright  2015 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course extends recyclebin
{
    private $_courseid;

    /**
     * Constructor.
     *
     * @param int $courseid Course ID.
     */
    public function __construct($courseid) {
        $this->_courseid = $courseid;
    }

    /**
     * Returns a list of items in the recycle bin for this course.
     */
    public function get_items() {
        global $DB;

        return $DB->get_records('local_recyclebin_course', array(
            'course' => $this->_courseid
        ));
    }

    /**
     * Store a course module in the recycle bin.
     *
     * @param $cm stdClass Course module
     * @throws \coding_exception
     * @throws \invalid_dataroot_permissions
     * @throws \moodle_exception
     */
    public function store_item($cm) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        // Get more information.
        $modinfo = get_fast_modinfo($cm->course);
        $cminfo = $modinfo->cms[$cm->id];

        // Check backup/restore support.
        if (!plugin_supports('mod', $cminfo->modname , FEATURE_BACKUP_MOODLE2)) {
            return;
        }

        // Backup the activity.
        $user = get_admin();
        $controller = new \backup_controller(
            \backup::TYPE_1ACTIVITY,
            $cm->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $user->id
        );
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
        $this->ensure_backup_dir_exists();

        // Record the activity, get an ID.
        $binid = $DB->insert_record('local_recyclebin_course', array(
            'course' => $cm->course,
            'section' => $cm->section,
            'module' => $cm->module,
            'name' => $cminfo->name,
            'deleted' => time()
        ));

        // Move the file to our own special little place.
        if (!$file->copy_content_to($bindir . '/' . $binid)) {
            // Failed, cleanup first.
            $DB->delete_record('local_recyclebin_course', array(
                'id' => $binid
            ));

            throw new \moodle_exception("Failed to copy backup file to recyclebin.");
        }

        // Delete the old file.
        $file->delete();

        // Fire event.
        $event = \local_recyclebin\event\item_stored::create(array(
            'objectid' => $binid,
            'context' => \context_course::instance($cm->course)
        ));
        $event->trigger();
    }

    /**
     * Restore an item from the recycle bin.
     *
     * @param stdClass $item The item database record
     * @throws \Exception
     * @throws \coding_exception
     * @throws \moodle_exception
     * @throws \restore_controller_exception
     */
    public function restore_item($item) {
        global $CFG;

        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $user = get_admin();

        // Get the pathname.
        $source = $CFG->dataroot . '/recyclebin/' . $item->id;
        if (!file_exists($source)) {
            throw new \moodle_exception('Invalid recycle bin item!');
        }

        // Grab the course context.
        $context = \context_course::instance($this->_courseid);

        // Grab a tmpdir.
        $tmpdir = \restore_controller::get_tempdir_name($context->id, $user->id);

        // Extract the backup to tmpdir.
        $fb = get_file_packer('application/vnd.moodle.backup');
        $fb->extract_to_pathname($source, $CFG->tempdir . '/backup/' . $tmpdir . '/');

        // Define the import.
        $controller = new \restore_controller(
            $tmpdir,
            $this->_courseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $user->id,
            \backup::TARGET_EXISTING_ADDING
        );

        // Prechecks.
        if (!$controller->execute_precheck()) {
            $results = $controller->get_precheck_results();

            if (isset($results['errors'])) {
                debugging(var_export($results, true));
                throw new \moodle_exception("Restore failed.");
            }

            if (isset($results['warnings'])) {
                debugging(var_export($results['warnings'], true));
            }
        }

        // Run the import.
        $controller->execute_plan();

        // Fire event.
        $event = \local_recyclebin\event\item_restored::create(array(
            'objectid' => $item->id,
            'context' => $context
        ));
        $event->add_record_snapshot('local_recyclebin_course', $item);
        $event->trigger();

        // Cleanup.
        $this->delete_item($item, true);
    }

    /**
     * Delete an item from the recycle bin.
     *
     * @param stdClass $item The item database record
     * @param boolean $noevent Whether or not to fire a purged event.
     * @throws \coding_exception
     */
    public function delete_item($item, $noevent = false) {
        global $CFG, $DB;

        // Delete the file.
        unlink($CFG->dataroot . '/recyclebin/' . $item->id);

        // Delete the record.
        $DB->delete_records('local_recyclebin_course', array(
            'id' => $item->id
        ));

        if ($noevent) {
            return;
        }

        // Fire event.
        $event = \local_recyclebin\event\item_purged::create(array(
            'objectid' => $item->id,
            'context' => \context_course::instance($item->course)
        ));
        $event->add_record_snapshot('local_recyclebin_course', $item);
        $event->trigger();
    }
}
