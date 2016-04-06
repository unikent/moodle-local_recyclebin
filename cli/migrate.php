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
 * Migrate to the 3.1 recyclebin.
 */

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

if ($CFG->version < 2016033100) {
    die("You must be on Moodle 3.1+ to use this script!");
}

require_once($CFG->dirroot . '/admin/tool/recyclebin/classes/course_bin.php');
require_once($CFG->dirroot . '/admin/tool/recyclebin/classes/category_bin.php');

$count = $DB->count_records('local_recyclebin_course');
if ($count > 0) {
    cli_heading("Migrating {$count} activity backups...");
    $rs = $DB->get_recordset('local_recyclebin_course');
    foreach ($rs as $record) {
        $record->courseid = $record->course;
        $record->timecreated = $record->deleted;
        $binid = $DB->insert_record('tool_recyclebin_course', $record);

        // Create the location we want to copy this file to.
        $filerecord = array(
            'contextid' => \context_course::instance($record->courseid)->id,
            'component' => 'tool_recyclebin',
            'filearea' => TOOL_RECYCLEBIN_COURSE_BIN_FILEAREA,
            'itemid' => $binid,
            'filepath' => '/',
            'filename' => $binid,
            'timemodified' => time()
        );

        // Move the file over.
        $currentloc = $CFG->dataroot . '/recyclebin/' . $record->id;
        $fs = get_file_storage();
        if (!file_exists($currentloc) || !$fs->create_file_from_pathname($filerecord, $currentloc)) {
            $DB->delete_records('tool_recyclebin_course', array(
                'id' => $binid
            ));

            cli_println("Failed to copy {$record->id}'s file to the new area.'");

            continue;
        }

        // Delete our record.
        $DB->delete_records('local_recyclebin_course', array(
            'id' => $record->id
        ));
    }
    $rs->close();
}

$count = $DB->count_records('local_recyclebin_category');
if ($count > 0) {
    cli_heading("Migrating {$count} course backups...");
    $rs = $DB->get_recordset('local_recyclebin_category');
    foreach ($rs as $record) {
        $record->categoryid = $record->category;
        $record->timecreated = $record->deleted;
        $binid = $DB->insert_record('tool_recyclebin_category', $record);

        // Create the location we want to copy this file to.
        $filerecord = array(
            'contextid' => \context_coursecat::instance($record->categoryid)->id,
            'component' => 'tool_recyclebin',
            'filearea' => TOOL_RECYCLEBIN_COURSECAT_BIN_FILEAREA,
            'itemid' => $binid,
            'filepath' => '/',
            'filename' => $binid,
            'timemodified' => time()
        );

        // Move the file over.
        $currentloc = $CFG->dataroot . '/recyclebin/course-' . $record->id;
        $fs = get_file_storage();
        if (!file_exists($currentloc) || !$fs->create_file_from_pathname($filerecord, $currentloc)) {
            $DB->delete_records('tool_recyclebin_category', array(
                'id' => $binid
            ));

            cli_println("Failed to copy {$record->id}'s file to the new area.'");

            continue;
        }

        // Delete our record.
        $DB->delete_records('local_recyclebin_category', array(
            'id' => $record->id
        ));
    }
    $rs->close();
}

cli_writeln("Finished!");
