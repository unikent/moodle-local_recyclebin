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
 * This page shows the contents of a recyclebin for a given course.
 *
 * @package    local_recyclebin
 * @copyright  2015 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');

$courseid = required_param('course', PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);
$context = \context_course::instance($courseid, MUST_EXIST);

require_login($courseid);
require_capability('local/recyclebin:view_item', $context);

$PAGE->set_url('/local/recyclebin/index.php', array(
    'course' => $courseid
));
$PAGE->set_title(get_string('pluginname', 'local_recyclebin'));

$recyclebin = new \local_recyclebin\course($courseid);

// If we are doing anything, we need a sesskey!
if (!empty($action)) {
    raise_memory_limit(MEMORY_EXTRA);
    require_sesskey();

    $item = null;
    if ($action == 'restore' || $action == 'delete') {
        $itemid = required_param('itemid', PARAM_INT);
        $item = $DB->get_record('local_recyclebin_course', array(
            'id' => $itemid
        ), '*', MUST_EXIST);
    }

    switch ($action) {
        // Restore it.
        case 'restore':
            require_capability('local/recyclebin:restore_item', $context);
            $recyclebin->restore_item($item);
            redirect($PAGE->url, get_string('alertrestored', 'local_recyclebin', $item), 2);
        break;

        // Delete it.
        case 'delete':
            require_capability('local/recyclebin:delete_item', $context);
            $recyclebin->delete_item($item);
            redirect($PAGE->url, get_string('alertdeleted', 'local_recyclebin', $item), 2);
        break;

        // Empty it.
        case 'empty':
            require_capability('local/recyclebin:delete_item', $context);
            $recyclebin->delete_all_items();
            redirect($PAGE->url, get_string('alertemptied', 'local_recyclebin'), 2);
        break;
    }
}

// Add a "Go Back" button.
$goback = html_writer::start_tag('div', array('class' => 'backlink'));
$goback .= html_writer::link($context->get_url(), get_string('backto', '', $context->get_context_name()));
$goback .= html_writer::end_tag('div');

// Output header.
echo $OUTPUT->header();
echo $OUTPUT->heading($PAGE->title);

// Grab our items, check there is actually something to display.
$items = $recyclebin->get_items();

// Nothing to show? Bail out early.
if (empty($items)) {
    echo $OUTPUT->box(get_string('emptybin', 'local_recyclebin'));
    echo $goback;
    echo $OUTPUT->footer();
    die;
}

// Start with a description.
$description = get_string('description', 'local_recyclebin');
$expiry = get_config('local_recyclebin', 'expiry');
if ($expiry > 0) {
    $description .= ' ' . get_string('descriptionexpiry', 'local_recyclebin', $expiry);
}
echo $OUTPUT->box($description, 'generalbox descriptionbox');

// Check permissions.
$canrestore = has_capability('local/recyclebin:restore_item', $context);
$candelete = has_capability('local/recyclebin:delete_item', $context);

// Strings.
$restorestr = get_string('restore');
$deletestr = get_string('delete');

// Define columns and headers.
$columns = array('activity', 'date');
$headers = array(
    get_string('activity'),
    get_string('deleted', 'local_recyclebin')
);

if ($candelete) {
    $columns[] = 'delete';
    $headers[] = $deletestr;
}

if ($canrestore) {
    $columns[] = 'restore';
    $headers[] = $restorestr;
}

// Define a table.
$table = new flexible_table('recyclebin');
$table->define_columns($columns);
$table->define_headers($headers);
$table->define_baseurl($PAGE->url);
$table->set_attribute('id', 'recycle-bin-table');
$table->setup();

// Cache a list of modules.
$modules = $DB->get_records('modules');

// Add all the items to the table.
foreach ($items as $item) {
    $row = array();

    // Build item name.
    $icon = '';
    if (isset($modules[$item->module])) {
        $mod = $modules[$item->module];
        $modname = get_string('modulename', $mod->name);
        $icon = '<img src="' . $OUTPUT->pix_url('icon', $mod->name) . '" class="icon" alt="' . $modname . '" /> ';
    }

    $row[] = "{$icon}{$item->name}";
    $row[] = userdate($item->deleted);

    // Build delete link.
    if ($candelete) {
        $delete = new \moodle_url('/local/recyclebin/index.php', array(
            'course' => $courseid,
            'itemid' => $item->id,
            'action' => 'delete',
            'sesskey' => sesskey()
        ));
        $delete = $OUTPUT->action_icon($delete, new pix_icon('t/delete',
                get_string('delete'), '', array('class' => 'iconsmall')), null,
                array('class' => 'action-icon recycle-bin-delete'));

        $row[] = $delete;
    }

    // Build restore link.
    if ($canrestore) {
        $restore = '';
        if (isset($modules[$item->module])) {
            $restore = new \moodle_url('/local/recyclebin/index.php', array(
                'course' => $courseid,
                'itemid' => $item->id,
                'action' => 'restore',
                'sesskey' => sesskey()
            ));
            $restore = $OUTPUT->action_icon($restore, new pix_icon('t/restore', get_string('restore'), '', array('class' => 'iconsmall')));
        }

        $row[] = $restore;
    }

    $table->add_data($row);
}

// Display the table now.
$table->print_html();

// Empty recyclebin link.
if ($candelete) {
    $empty = new \moodle_url('/local/recyclebin/index.php', array(
        'course' => $courseid,
        'action' => 'empty',
        'sesskey' => sesskey()
    ));

    echo $OUTPUT->single_button($empty, get_string('empty', 'local_recyclebin'), 'post', array(
        'class' => 'singlebutton recycle-bin-delete-all'
    ));
}

echo $goback;

// Confirmation JS.
$PAGE->requires->strings_for_js(array('emptyconfirm', 'deleteconfirm'), 'local_recyclebin');
$PAGE->requires->js_init_call('M.local_recyclebin.init');

// Output footer.
echo $OUTPUT->footer();
