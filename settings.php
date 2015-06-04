<?php

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
 * Kaltura video assignment grade preferences form
 *
 * @package    local
 * @subpackage kaltura
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

global $PAGE;

if ($hassiteconfig) { // Thanks to Brett Wilkins <brett@catalyst.net.nz>

    $settings = new admin_settingpage('local_recyclebin', get_string('pluginname', 'local_recyclebin'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_heading('recyclebin_heading', get_string('heading_title', 'local_recyclebin'),
                       get_string('heading_desc', 'local_recyclebin')));
    
    $adminsetting = new admin_setting_configtext('expiry', get_string('expiry', 'local_recyclebin'),
                       get_string('expiry_desc', 'local_recyclebin'), '', PARAM_TEXT);
    $adminsetting->plugin = 'local_recyclebin';
    $settings->add($adminsetting);
}