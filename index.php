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

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');

$courseid = required_param('course', PARAM_INT);
$itemid = optional_param('itemid', null, PARAM_INT);
$coursecontext = \context_course::instance($courseid, MUST_EXIST);

require_login($courseid);
require_capability('moodle/course:update', $coursecontext);

$PAGE->set_url('/local/recyclebin/index.php', array(
    'course' => $courseid
));
$PAGE->set_title('Recycle Bin');

$recyclebin = new \local_recyclebin\RecycleBin($courseid);

// Do we have an itemid?
// If so, we might have something to do!
if (isset($itemid)) {
    require_sesskey();
    raise_memory_limit(MEMORY_EXTRA);
    $action = required_param('action', PARAM_ALPHA);

    $item = $DB->get_record('local_recyclebin', array(
        'id' => $itemid
    ), '*', MUST_EXIST);

    $message = '';

    // Work out what we want to do with this item.
    switch($action) {
        case 'restore':
            // Restore it.
            $recyclebin->restore_item($item);
            $message = $item->name . ' has been restored';
        break;

        case 'delete':
            // Delete it.
            \local_recyclebin\RecycleBin::delete_item($item);
            $message = $item->name . ' has been deleted';
        break;

        default:
            throw new \moodle_exception('Invalid action.');
    }

    redirect($PAGE->url, $message, 2);
} else {
    // We might want to empty the whole bin?
    $action = optional_param('action', null, PARAM_ALPHA);
    if ($action == 'empty') {
        require_sesskey();
        $recyclebin->empty_recycle_bin();
    }
}

// Grab our items, redirect back to the course if there aren't any.
$items = $recyclebin->get_items();
if (empty($items)) {
    redirect(new \moodle_url('/course/view.php', array(
        'id' => $courseid
    )));
}

// Output header.
echo $OUTPUT->header();
echo $OUTPUT->heading('Recycle Bin');

// Define a table.
$table = new flexible_table('recyclebin');
$table->define_columns(array('activity', 'date', 'restore', 'delete'));
$table->define_headers(array('Activity', 'Date Deleted', 'Restore',  'Delete'));
$table->define_baseurl($CFG->wwwroot.'/local/recyclebin/index.php');
$table->setup();

// Cache a list of modules.
$modules = $DB->get_records('modules');

// Add all the items to the table.
foreach ($items as $item) {
    $icon = '';
    if (isset($modules[$item->module])) {
        $mod = $modules[$item->module];
        $icon = '<img src="' . $OUTPUT->pix_url('icon', $mod->name) . '" class="icon" alt="' . get_string('modulename', $mod->name) . '" /> ';
    }

    // Build restore link.
    $restore = new \moodle_url('/local/recyclebin/index.php', array(
        'course' => $courseid,
        'itemid' => $item->id,
        'action' => 'restore',
        'sesskey' => sesskey()
    ));
    $restore = \html_writer::link($restore, '<i class="fa fa-history"></i>', array(
        'alt' => 'Restore',
        'title' => 'Restore'
    ));

    $datedeleted = userdate($item->deleted);

    // Build delete link.
    $delete = new \moodle_url('/local/recyclebin/index.php', array(
        'course' => $courseid,
        'itemid' => $item->id,
        'action' => 'delete',
        'sesskey' => sesskey()
    ));
    $delete = \html_writer::link($delete, '<i class="fa fa-trash"></i>', array(
        'alt' => 'Delete',
        'title' => 'Delete'
    ));

    if (isset($modules[$item->module])) {
        $table->add_data(array("{$icon} {$item->name}", $datedeleted, $restore, $delete));
    } else {
        $table->add_data(array($item->name, $datedeleted, 'missing module!', $delete));
    }
}

// Display the table now.
$table->print_html();

// Empty bin link.
$empty = new \moodle_url('/local/recyclebin/index.php', array(
    'course' => $courseid,
    'action' => 'empty',
    'sesskey' => sesskey()
));
echo \html_writer::link($empty, 'Empty Recycle Bin', array(
    'alt' => 'Empty Recycle Bin',
    'title' => 'Empty Recycle Bin'
));

// Output footer.
echo $OUTPUT->footer();
