<?php
/**
 * @package block_mbstpl
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstpl\task;


use block_mbstpl\backup;

class adhoc_deploy_secondary extends \core\task\adhoc_task {
    public function execute() {
        $details = $this->get_custom_data();
        $template = new \block_mbstpl\dataobj\template($details->tplid, true, MUST_EXIST);
        try {
            $filename = backup::backup_secondary($template, $details->settings);
            $courseid = backup::restore_secondary($template, $filename, $details->settings);
            \block_mbstpl\notifications::email_duplicated($details->requesterid, $courseid);
        } catch(\moodle_exception $e) {
            \block_mbstpl\notifications::notify_error('errordeploying', $e);
            print_r($e->getMessage());
            print_r($e->getTrace());
            print_r($template);
        }

        return true;
    }
}