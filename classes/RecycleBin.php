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
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * RecycleBin class.
 */
class RecycleBin
{
    private $_courseid;

    public function __construct($courseid) {
        $this->_courseid = $courseid;
    }

    /**
     * Returns a list of items in the recycle bin for this course.
     */
    public function get_items() {
        global $DB;

        return $DB->get_records('local_recyclebin', array(
            'course' => $this->_courseid
        ));
    }

    /**
     * Store a course module in the recycle bin.
     */
    public function store_item($cm) {
        global $CFG, $DB, $USER;

        // Get more information.
        $modinfo = get_fast_modinfo($cm->course);
        $cminfo = $modinfo->cms[$cm->id];

        // Backup the activity.
        $controller = new \backup_controller(\backup::TYPE_1ACTIVITY, $cm->id, \backup::FORMAT_MOODLE, \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $USER->id);
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

        // Record the activity, get an ID.
        $binid = $DB->insert_record('local_recyclebin', array(
            'course' => $cm->course,
            'section' => $cm->section,
            'module' => $cm->module,
            'name' => $cminfo->name,
            'deleted' => time()
        ));

        // Move the file to our own special little place.
        if (!$file->copy_content_to($bindir . '/' . $binid)) {
            // Failed, cleanup first.
            $DB->delete_record('local_recyclebin', array(
                'id' => $binid
            ));

            throw new \moodle_exception("Failed to copy backup file to recyclebin.");
        }
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
     */
    public function restore_item($item) {
        global $CFG, $DB, $USER;

        // Get the pathname.
        $source = $CFG->dataroot . '/recyclebin/' . $item->id;
        if (!file_exists($source)) {
            throw new \moodle_exception('Invalid recycle bin item!');
        }

        // Grab the course context.
        $context = \context_course::instance($this->_courseid);

        // Grab a tmpdir.
        $tmpdir = \restore_controller::get_tempdir_name($context->id, $USER->id);

        // Extract the backup to tmpdir.
        $fb = get_file_packer('application/vnd.moodle.backup');
        $fb->extract_to_pathname($source, $CFG->tempdir . '/backup/' . $tmpdir . '/');

        // Define the import.
        $controller = new \restore_controller($tmpdir, $this->_courseid, \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $USER->id, \backup::TARGET_EXISTING_ADDING);
        if (!$controller->execute_precheck()) {
            $results = $controller->get_precheck_results();

            if (isset($results['errors'])) {
                debugging(var_dump($results));
                throw new \moodle_exception("Restore failed.");
            }

            if (isset($results['warnings'])) {
                debugging(var_dump($results['warnings']));
            }
        }

        // Run the import.
        $controller->execute_plan();

        // Fire event.
        $event = \local_recyclebin\event\item_restored::create(array(
            'objectid' => $item->id,
            'context' => $context
        ));
        $event->add_record_snapshot('local_recyclebin', $item);
        $event->trigger();

        // Cleanup.
        $this->cleanup_item($item);
    }

    /**
     * Delete an item from the recycle bin.
     */
    public function delete_item($item) {
        // Do the cleanup.
        $this->cleanup_item($item);

        // Fire event.
        $event = \local_recyclebin\event\item_purged::create(array(
            'objectid' => $item->id,
            'context' => \context_course::instance($item->course)
        ));
        $event->add_record_snapshot('local_recyclebin', $item);
        $event->trigger();
    }

    /**
     * Empty the recycle bin.
     */
    public function empty_recycle_bin() {
        // Cleanup all items.
        $items = $this->get_items();
        foreach ($items as $item) {
            $this->delete_item($item);
        }
    }

    /**
     * Delete an item from the recycle bin.
     */
    private function cleanup_item($item) {
        global $CFG, $DB;

        // Delete the file.
        unlink($CFG->dataroot . '/recyclebin/' . $item->id);

        // Delete the record.
        $DB->delete_records('local_recyclebin', array(
            'id' => $item->id
        ));
    }
}
