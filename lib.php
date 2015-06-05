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
 * Local lib code
 *
 * @package    local_recyclebin
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function local_recyclebin_extend_settings_navigation(settings_navigation $nav, context $context) {
    global $PAGE;

    // Only add this settings item on non-site course pages.
    if (!$PAGE->course or $PAGE->course->id == 1) {
        return;
    }

    $course = new \local_recyclebin\RecycleBin($PAGE->course->id);
    $items = $course->get_items();

    if (!empty($items) && $settingnode = $nav->find('courseadmin', navigation_node::TYPE_COURSE)) {
        $url = new moodle_url('/local/recyclebin/index.php', array(
            'course' => $context->instanceid
        ));

        $node = navigation_node::create(
            'Recycle bin',
            $url,
            navigation_node::NODETYPE_LEAF,
            'local_recyclebin',
            'local_recyclebin',
            new pix_icon('e/cleanup_messy_code', 'Recycle bin')
        );

        if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
            $node->make_active();
        }

        $settingnode->add_node($node);

        return $node;
    }
}

/**
 * For pre-2.9.
 *
 * @param settings_navigation $nav
 * @param context $context
 */
function local_recyclebin_extends_settings_navigation(settings_navigation $nav, context $context) {
    local_recyclebin_extend_settings_navigation($nav, $context);
}
