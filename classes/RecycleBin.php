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
     * Restore an item from the recycle bin.
     */
    public function restore_item($item) {
    	global $CFG, $DB;

    	// Get the pathname.
    	$source = $CFG->dataroot . '/recyclebin/' . $item->id;
    	if (!file_exists($source)) {
    		throw new \moodle_exception('Invalid recycle bin item!');
    	}

    	// Use the admin user here too.
    	$user = get_admin();

    	// Context please!
    	$ctx = \context_course::instance($this->_courseid);

    	// Grab a tmpdir.
    	$tmpdir = \restore_controller::get_tempdir_name($ctx->id, $user->id);

    	// Extract the backup to tmpdir.
    	$fb = get_file_packer('application/vnd.moodle.backup');
    	$fb->extract_to_pathname($source, $CFG->tempdir . '/backup/' . $tmpdir . '/');

    	// Run the import.
        $controller = new \restore_controller($tmpdir, $this->_courseid, \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $user->id, \backup::TARGET_EXISTING_ADDING);
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

        $controller->execute_plan();

        // Cleanup.
        $this->delete_item($item);
    }

    /**
     * Delete an item from the recycle bin.
     */
    public function delete_item($item) {
    	global $CFG, $DB;

    	// Delete the file.
    	unlink($CFG->dataroot . '/recyclebin/' . $item->id);

    	// Delete the record.
    	$DB->delete_records('local_recyclebin', array(
			'id' => $item->id
		));
    }

    /**
     * Empty the recycle bin.
     */
    public function empty_recycle_bin() {
        $items = $this->get_items();
        foreach ($items as $item) {
            $this->delete_item($item);
        }
    }
}
