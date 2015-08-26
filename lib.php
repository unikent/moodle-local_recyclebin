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

/**
 * Adds a recycle bin link to the course admin menu.
 *
 * @param  settings_navigation $nav     Nav menu
 * @param  context             $context Context of the menu
 * @return navigation_node              A new navigation mode to insert.
 */
function local_recyclebin_extend_settings_navigation(settings_navigation $nav, context $context) {
    global $PAGE;

    // Only add this settings item on non-site course pages.
    if (!$PAGE->course or $PAGE->course->id == SITEID) {
        return null;
    }

    // Check we can view the recycle bin.
    $context = \context_course::instance($PAGE->course->id);
    if (!has_capability('local/recyclebin:view', $context)) {
        return null;
    }

    // If we are set to auto-hide, check the number of items.
    $autohide = get_config('local_recyclebin', 'autohide');
    if ($autohide) {
        $course = new \local_recyclebin\RecycleBin($PAGE->course->id);
        $items = $course->get_items();
        if (empty($items)) {
            return null;
        }
    }

    // Add the recyclebin link.
    if ($settingnode = $nav->find('courseadmin', navigation_node::TYPE_COURSE)) {
        $url = new moodle_url('/local/recyclebin/index.php', array(
            'course' => $context->instanceid
        ));

        $pluginname = get_string('pluginname', 'local_recyclebin');

        $node = navigation_node::create(
            $pluginname,
            $url,
            navigation_node::NODETYPE_LEAF,
            'local_recyclebin',
            'local_recyclebin',
            new pix_icon('e/cleanup_messy_code', $pluginname)
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
