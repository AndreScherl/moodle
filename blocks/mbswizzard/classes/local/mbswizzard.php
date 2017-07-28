<?php
/**
 * wizzard class for block mbswizzard
 *
 * @package     block_mbswizzard
 * @author      Andre Scherl <andre.scherl@isb.bayern.de>
 * @copyright   2015, ISB Bayern
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mbswizzard\local;

class mbswizzard {

    /** get the list of sequences by names of files in block folder
     * 
     * @return array list of sequence names
     */
    public static function sequencefiles() {
        global $CFG;
        $sequences = [];
        
        // read filenames from sequence directory of block
        if ($handle = opendir($CFG->dirroot.'/blocks/mbswizzard/js/sequences/')) {
            while (false !== ($entry = readdir($handle))) {
                //if ($entry != "." && $entry != ".." && $entry != "") {
                if(false !== strpos($entry, 'wizzard_sequence_')) {
                    $name = explode('wizzard_sequence_', $entry)[1]; // Remove prefix.
                    $name = explode('.json', $name)[0]; // Remove file extension.
                    $sequences[] = $name;
                }
            }
            closedir($handle);
        }
        
        return $sequences;
    }
}
