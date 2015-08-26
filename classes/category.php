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
 * Represents a category's recyclebin.
 *
 * @package    local_recyclebin
 * @copyright  2015 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category
{
    private $_categoryid;

    /**
     * Constructor.
     *
     * @param int $categoryid Category ID.
     */
    public function __construct($categoryid) {
        $this->_categoryid = $categoryid;
    }

    /**
     * Returns a list of items in the recycle bin for this course.
     */
    public function get_items() {
        global $DB;

        return $DB->get_records('local_recyclebin_category', array(
            'course' => $this->_courseid
        ));
    }

    /**
     * Store a course in the recycle bin.
     *
     * @param $course stdClass Course
     * @throws \coding_exception
     * @throws \invalid_dataroot_permissions
     * @throws \moodle_exception
     */
    public function store_item($course) {
        // TODO.
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
        // TODO.
    }

    /**
     * Delete an item from the recycle bin.
     *
     * @param stdClass $item The item database record
     * @throws \coding_exception
     */
    public function delete_item($item) {
        // TODO.
    }
}
