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

namespace local_recyclebin\task;

class clean_recyclebin extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('cleanrecyclebin', 'local_recyclebin');
    }

    public function execute() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/lib.php');

        $lifetime = get_config('local_recyclebin', 'expiry');
        if ($lifetime == 0) {
            // Effectively disabled.
            return true;
        }

        $deletefrom = time() - (86400 * $lifetime);

        $items = $DB->get_recordset_select('local_recyclebin', 'deleted < ?', array($deletefrom), '', 'id');
        foreach ($items as $item) {
            echo "[RecycleBin] Deleting item {$item->id}...\n";

            \local_recyclebin\RecycleBin::delete_item($item);
        }
        $items->close();

        return true;
    }
}