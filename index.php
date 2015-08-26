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

$contextid = required_param('contextid', PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);

$context = context::instance_by_id($contextid, MUST_EXIST);

$capabilities = array(
    'view' => '',
    'restore' => '',
    'delete' => ''
);

$description = '';

// We could be a course or a category.
switch ($context->contextlevel) {
    case \CONTEXT_COURSE:
        require_login($context->instanceid);

        $capabilities = array(
            'view' => 'local/recyclebin:view_item',
            'restore' => 'local/recyclebin:restore_item',
            'delete' => 'local/recyclebin:delete_item'
        );

        $recyclebin = new \local_recyclebin\course($context->instanceid);
        $description = get_string('description_course', 'local_recyclebin');
    break;

    case \CONTEXT_COURSECAT:
        require_login();
        $PAGE->set_context($context);

        $capabilities = array(
            'view' => 'local/recyclebin:view_course',
            'restore' => 'local/recyclebin:restore_course',
            'delete' => 'local/recyclebin:delete_course'
        );

        $recyclebin = new \local_recyclebin\category($context->instanceid);
        $description = get_string('description_coursecat', 'local_recyclebin');
    break;

    default:
        print_error('invalidcontext', 'local_recyclebin');
    break;
}

require_capability($capabilities['view'], $context);

$PAGE->set_url('/local/recyclebin/index.php', array(
    'contextid' => $contextid
));
$PAGE->set_title(get_string('pluginname', 'local_recyclebin'));

// If we are doing anything, we need a sesskey!
if (!empty($action)) {
    raise_memory_limit(MEMORY_EXTRA);
    require_sesskey();

    $item = null;
    if ($action == 'restore' || $action == 'delete') {
        $itemid = required_param('itemid', PARAM_INT);
        $item = $recyclebin->get_item($itemid);
    }

    switch ($action) {
        // Restore it.
        case 'restore':
            require_capability($capabilities['restore'], $context);
            $recyclebin->restore_item($item);
            redirect($PAGE->url, get_string('alertrestored', 'local_recyclebin', $item), 2);
        break;

        // Delete it.
        case 'delete':
            require_capability($capabilities['delete'], $context);
            $recyclebin->delete_item($item);
            redirect($PAGE->url, get_string('alertdeleted', 'local_recyclebin', $item), 2);
        break;

        // Empty it.
        case 'empty':
            require_capability($capabilities['delete'], $context);
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
$expiry = get_config('local_recyclebin', 'expiry');
if ($expiry > 0) {
    $description .= ' ' . get_string('descriptionexpiry', 'local_recyclebin', $expiry);
}
echo $OUTPUT->box($description, 'generalbox descriptionbox');

// Check permissions.
$canrestore = has_capability($capabilities['restore'], $context);
$candelete = has_capability($capabilities['delete'], $context);

// Strings.
$restorestr = get_string('restore');
$deletestr = get_string('delete');

// Define columns and headers.
$firstcolstr = $context->contextlevel == \CONTEXT_COURSE ? 'activity' : 'course';
$columns = array($firstcolstr, 'date');
$headers = array(
    get_string($firstcolstr),
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
$modules = null;
if ($context->contextlevel == \CONTEXT_COURSE) {
    $modules = $DB->get_records('modules');
}

// Add all the items to the table.
foreach ($items as $item) {
    $row = array();

    // Build item name.
    $name = $item->name;
    if ($context->contextlevel == \CONTEXT_COURSE) {
        if (isset($modules[$item->module])) {
            $mod = $modules[$item->module];
            $modname = get_string('modulename', $mod->name);
            $name = '<img src="' . $OUTPUT->pix_url('icon', $mod->name) . '" class="icon" alt="' . $modname . '" /> ' . $name;
        }
    }

    $row[] = $name;
    $row[] = userdate($item->deleted);

    // Build delete link.
    if ($candelete) {
        $delete = new \moodle_url('/local/recyclebin/index.php', array(
            'contextid' => $contextid,
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
        if ($context->contextlevel == \CONTEXT_COURSECAT || isset($modules[$item->module])) {
            $restore = new \moodle_url('/local/recyclebin/index.php', array(
                'contextid' => $contextid,
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
        'contextid' => $contextid,
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
