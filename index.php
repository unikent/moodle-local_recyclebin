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

if (isset($itemid)) {
    require_sesskey();

    $item = $DB->get_record('local_recyclebin', array(
        'id' => $itemid
    ), '*', MUST_EXIST);

    // Restore it.
    $recyclebin->restore_item($item);
    redirect($PAGE->url);
}

$items = $recyclebin->get_items();
if (empty($items)) {
    redirect(new \moodle_url('/course/view.php', array(
        'id' => $courseid
    )));
}

// Output header.
echo $OUTPUT->header();
echo $OUTPUT->heading('Recycle Bin');

echo '<ul>';

// Cache a list of modules.
$modules = $DB->get_records('modules');

foreach ($items as $item) {
    $mod = $modules[$item->module];

    $icon = '<img src="' . $OUTPUT->pix_url('icon', $mod->name) . '" class="icon" alt="' . get_string('modulename', $mod->name) . '" /> ';

    $restore = new \moodle_url('/local/recyclebin/index.php', array(
        'course' => $courseid,
        'itemid' => $item->id,
        'sesskey' => sesskey()
    ));
    $restore = \html_writer::link($restore, '<i class="fa fa-history"></i>', array(
        'alt' => 'Restore',
        'title' => 'Restore'
    ));

    echo "<li>{$icon}{$item->name}  {$restore}</li>";
}

echo '</ul>';

// Output footer.
echo $OUTPUT->footer();
