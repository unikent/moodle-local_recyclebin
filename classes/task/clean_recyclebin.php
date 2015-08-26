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
 * Recycle bin cron task.
 *
 * @package    local_recyclebin
 * @copyright  2015 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_recyclebin\task;

/**
 * This task deleted expired recyclebin items.
 *
 * @package    local_recyclebin
 * @copyright  2015 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clean_recyclebin extends \core\task\scheduled_task {
    /**
     * Task name.
     */
    public function get_name() {
        return get_string('cleanrecyclebin', 'local_recyclebin');
    }

    /**
     * Delete all expired items.
     */
    public function execute() {
        global $DB;

        $lifetime = get_config('local_recyclebin', 'expiry');
        if ($lifetime == 0) {
            return true;
        }

        $deletefrom = time() - (86400 * $lifetime);
        $items = $DB->get_recordset_select('local_recyclebin', 'deleted < ?', array($deletefrom), '', 'id');
        foreach ($items as $item) {
            mtrace("[RecycleBin] Deleting item {$item->id}...");

            $bin = new \local_recyclebin\RecycleBin($item->course);
            $bin->delete_item($item);
        }
        $items->close();

        return true;
    }
}