/**
 * helper function to hide the block
 *
 * @package    block_mbsgettingstarted
 * @copyright  2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$(document).ready(function() {	
    M.block_mbsgettingstarted.visibility.init();
});

/*
 * Namespace block mbsgettingstarted
 */
M.block_mbsgettingstarted = M.block_mbsgettingstarted || {};

/*
 * Namespace visibility
 */
M.block_mbsgettingstarted.visibility = M.block_mbsgettingstarted.visibility || {}

/*
 * Intitialize
 */
M.block_mbsgettingstarted.visibility.init = function() {
    
    $('#mbsgettingstarted_closeforever').on('click', (function(e){
       M.util.set_user_preference('mbsgettingstartednotshow', 0);
       $('#block_mbsgettingstarted').remove();
    }));
    
    $('#mbsgettingstarted_closeforsession').on('click', (function(e){
       $.post(
            M.cfg['wwwroot']+'/blocks/mbsgettingstarted/hideblock.php', 
            {sesskey: M.cfg.sesskey}, 
            function(data, status){
                 $('#block_mbsgettingstarted').remove(); 
            }
        );
    }));
};

