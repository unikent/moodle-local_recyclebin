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
 * @package    local_recyclebin
 * @copyright  2015 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Observer
{
    /**
     * Main hook.
     * Note: This is not actually a typical observer.
     * There is no pre-cm event, see README.
     *
     * @param \stdClass $cm The course module record.
     */
    public static function pre_cm_delete($cm) {
        $recyclebin = new \local_recyclebin\RecycleBin($cm->course);
        $recyclebin->store_item($cm);
    }
}
