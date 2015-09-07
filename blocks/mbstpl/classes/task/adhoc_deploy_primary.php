<?php
/**
 * @package block_mbstpl
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstpl\task;


use block_mbstpl\backup;

class adhoc_deploy_primary extends \core\task\adhoc_task {
    public function execute() {
        $bkpdetails = $this->get_custom_data();
        $backup = new \block_mbstpl\dataobj\backup(array('id' => $bkpdetails->id), true, MUST_EXIST);
        try {
            backup::backup_primary($backup);
            $courseid = backup::restore_primary($backup);
            \block_mbstpl\notifications::email_deployed($backup, $courseid);
        } catch(\moodle_exception $e) {
            \block_mbstpl\notifications::notify_error('errordeploying', $e);
            print_r($e->getMessage());
            print_r($e->getTrace());
            print_r($backup);
        }
        return true;
    }
}