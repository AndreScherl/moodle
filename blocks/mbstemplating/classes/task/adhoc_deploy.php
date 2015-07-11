<?php
/**
 * @package block_mbstemplating
 * @copyright 2015 Yair Spielmann, Synergy Learning for ALP
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbstemplating\task;


class adhoc_deploy extends \core\task\adhoc_task {
    public function execute() {
        $backup = $this->get_custom_data();
        try {
            \block_mbstemplating\course::backup_template($backup);
            $courseid = \block_mbstemplating\course::restore_template($backup);
            \block_mbstemplating\notifications::email_deployed($backup, $courseid);
        } catch(\moodle_exception $e) {
            \block_mbstemplating\notifications::notify_error('errordeploying', $e);
            print_r($e->getMessage());
            print_r($backup);
        }
        return true;
    }
}