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
 * Recycle bin tests.
 *
 * @package    local_recyclebin
 * @copyright  2015 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Recycle bin category tests.
 *
 * @package    local_recyclebin
 * @copyright  2015 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_recyclebin_category_tests extends \advanced_testcase
{
    /**
     * Run a bunch of tests to make sure we capture mods.
     */
    public function test_observer() {
        global $DB, $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $before = $DB->count_records('course');

        $course = $this->getDataGenerator()->create_course();

        $this->assertEquals($before + 1, $DB->count_records('course'));
        delete_course($course, false);
        $this->assertEquals($before, $DB->count_records('course'));

        // Try with the API.
        $recyclebin = new \local_recyclebin\category($course->category);
        $this->assertEquals(1, count($recyclebin->get_items()));
    }

    /**
     * Run a bunch of tests to make sure we can restore mods.
     */
    public function test_restore() {
        global $DB, $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $before = $DB->count_records('course');

        $course = $this->getDataGenerator()->create_course();
        delete_course($course, false);
        $this->assertEquals($before, $DB->count_records('course'));

        $recyclebin = new \local_recyclebin\category($course->category);
        foreach ($recyclebin->get_items() as $item) {
            $recyclebin->restore_item($item);
        }

        $this->assertEquals($before + 1, $DB->count_records('course'));
        $this->assertEquals(0, count($recyclebin->get_items()));
    }

    /**
     * Run a bunch of tests to make sure we can purge mods.
     */
    public function test_purge() {
        global $DB, $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $before = $DB->count_records('course');

        $course = $this->getDataGenerator()->create_course();
        delete_course($course, false);
        $this->assertEquals($before, $DB->count_records('course'));

        $recyclebin = new \local_recyclebin\category($course->category);
        foreach ($recyclebin->get_items() as $item) {
            $recyclebin->delete_item($item);
        }

        $this->assertEquals($before, $DB->count_records('course'));
        $this->assertEquals(0, count($recyclebin->get_items()));
    }
}
