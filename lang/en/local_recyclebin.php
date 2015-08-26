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
 * English strings for local_recyclebin.
 *
 * @package    local_recyclebin
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Recycle bin';
$string['cleanrecyclebin'] = 'Clean recycle bin';

$string['expiry'] = 'Item lifetime';
$string['expiry_desc'] = 'How long should a deleted activity remain in the recycle bin?';

$string['autohide'] = 'Auto hide?';
$string['autohide_desc'] = 'Automatically hides the recycle bin link when the bin is empty.';

$string['neverdelete'] = 'Never delete recycled items';
$string['deleted'] = 'Date deleted';
$string['empty'] = 'Empty recycle bin';

$string['recyclebin:view'] = 'View recycle bin items';
$string['recyclebin:restore'] = 'Restore recycle bin items';
$string['recyclebin:delete'] = 'Delete recycle bin items';
$string['recyclebin:empty'] = 'Empty recycle bins';

$string['alertrestored'] = '{$a->name} has been restored';
$string['alertdeleted'] = '{$a->name} has been deleted';
$string['alertemptied'] = 'Recycle bin has been emptied';

$string['event_stored_name'] = 'Item stored';
$string['event_restored_name'] = 'Item restored';
$string['event_purged_name'] = 'Item purged';

$string['event_stored_description'] = 'Item stored with ID {$a->objectid}.';
$string['event_restored_description'] = 'Item with ID {$a->objectid} restored.';
$string['event_purged_description'] = 'Item with ID {$a->objectid} purged.';

$string['description'] = 'Items that have been deleted from a course can be restored and will appear at the bottom of the section from which they were deleted.';
$string['descriptionexpiry'] = 'Contents will be permanently deleted after {$a} days.';

$string['emptybin'] = 'There are no items in the recycle bin.';
$string['emptyconfirm'] = 'Are you sure you want to delete all items in the recycle bin?';
$string['deleteconfirm'] = 'Are you sure you want to delete the selected item in the recycle bin?';
