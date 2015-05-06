/**
 * helper function to hide the block
 *
 * @package    block_mbsgettingstarted
 * @copyright  2015 Franziska Hübler <franziska.huebler@isb.bayern.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$(document).ready(function() {
	var hider = new Hide();
	hider.init();
});

/*
 *
 * Hide Class
 *
 */
function Hide() {

}

/*
 * Intitialize
 */
Hide.prototype.init = function() {
    $('#mbsgettingstarted_closeforever').on('click', (function(e){
       M.util.set_user_preference('mbsgettingstartednotshow', 0);
    }));
};
