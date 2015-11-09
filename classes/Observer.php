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
 * Recycle bin observers.
 *
 * @package    local_recyclebin
 * @copyright  2015 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_recyclebin;

defined('MOODLE_INTERNAL') || die();

/**
 * Main class for the recycle bin.
 *
 * @deprecated 2.2 Please use "observer" class instead (note lower case).
 * @package    local_recyclebin
 * @copyright  2015 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Observer
{
    /**
     * Course hook.
     * Note: This is not actually a typical observer.
     * There is no pre-course delete event, see README.
     *
     * @deprecated 2.2 Please use observer::pre_course_delete instead (note lower case).
     * @param \stdClass $course The course record.
     */
    public static function pre_course_delete($course) {
        debugging("Observer::pre_course_delete is deprecated and will be removed in a future update.");
        observer::pre_course_delete($course);
    }

    /**
     * Course module hook.
     * Note: This is not actually a typical observer.
     * There is no pre-cm event, see README.
     *
     * @deprecated 2.2 Please use observer::pre_cm_delete instead (note lower case).
     * @param \stdClass $cm The course module record.
     */
    public static function pre_cm_delete($cm) {
        debugging("Observer::pre_cm_delete is deprecated and will be removed in a future update.");
        observer::pre_cm_delete($cm);
    }
}
