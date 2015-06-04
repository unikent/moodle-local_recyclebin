<?php

namespace local_recyclebin\task;

class clean_recyclebin extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('cleanrecyclebin', 'local_recyclebin');
    }
    
    public function execute() {       
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');
        $deletefrom = time() - (86400 * get_config('local_recyclebin', 'expiry'));
        \local_recyclebin\RecycleBin::cron_empty_recycle_bin($deletefrom);
    }       
}