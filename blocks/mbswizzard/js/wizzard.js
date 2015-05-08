/**
 * helper functions to handle events triggered by user interface
 *
 * @package   block_mbswizzard
 * @copyright Andre Scherl <andre.scherl@isb.bayern.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$(document).ready(function() {
    M.block_mbswizzard.wizzard.init();
});

/*
 * Block mbswizzard Namespace
 */
M.block_mbswizzard = M.block_mbswizzard || {};

/*
 * Wizzard namespace of block mbswizzard
 */
M.block_mbswizzard.wizzard = M.block_mbswizzard.wizzard || {
    sequence: {
        name: null,
        current_step: 0,
        steps: []
    }
};

/*
 * Intitialize the wizzard
 */
M.block_mbswizzard.wizzard.init = function() {
    // On click of an assistant link, load sequence data from json file and set sequence data into browers local storage
    // Note! The id of the link and the json file name should meet each other, e.g. link_assistant_course_create and sequence_course_create.json
    $('#block_mbsgettingstarted .link_wizzard').on('click', $.proxy(function(event){
        this.event = event;
	var seqname = "mbswizzard_sequence_"+$(event.target).attr("data-wizzard");
  	this.copy_sequence_from_json(seqname, $.proxy(function(success){
            if (success) {
		this.sequence = this.get_sequence(seqname);
		this.sequence.current_step = parseInt(this.sequence.current_step);
	    }
	    // store the name of the current sequence
	    localStorage.setItem("mbswizzard_current_sequence", seqname);
	}, this));
    }, this));
	    	
    // Load current sequence from localStorage, if the current sequence ist null 
    if (!this.sequence.name && localStorage.getItem("mbswizzard_current_sequence")) {
  	this.sequence = this.get_sequence(localStorage.getItem("mbswizzard_current_sequence"));
  	this.sequence.current_step = parseInt(this.sequence.current_step);
    }
    
    if (this.sequence.name) {
	// Show tooltip
	this.show_tip(this.sequence.current_step);
	// Prepare next step
	this.prepare_next_step(this.sequence.current_step);	
    }
};

/*
 * Copy sequence data from json file to localStorage
 * @param string sname - name of the sequence e.g. course_create
 * @callback bool success
 */
M.block_mbswizzard.wizzard.copy_sequence_from_json = function(sname, callback) {
    $.get(M.cfg['wwwroot']+"/blocks/mbswizzard/js/"+sname+".json", $.proxy(function(jsonobject){
	localStorage.setItem(sname, JSON.stringify(jsonobject));
	callback(true);
    }, this))
    .fail(function(){
	alert("Sequence loading from json failed.");
	callback(false);
    });
};

/*
 * Get sequence into an js object
 * @param string sname - name of the sequence e.g. course_create
 * @return object sequence
 */
M.block_mbswizzard.wizzard.get_sequence = function(sname) {
    var seq = localStorage.getItem(sname);
    if (seq) {
	return JSON.parse(seq);
    }
	return null;	
};
	
/*
 * Write sequence into localStorage
 * @param object sequence
 */
M.block_mbswizzard.wizzard.store_sequence = function(seq) {
    localStorage.setItem(seq.name, JSON.stringify(seq));
};
	
/*
 * Show tip and set focus to target element
 * @param number step
 */
M.block_mbswizzard.wizzard.show_tip = function(step) {
    var cs = this.sequence.steps[step];
    $(cs.sel).tooltip({
	html: true,
	title: cs.tip,
	trigger: 'manual'
    });
    $(cs.sel).tooltip('show');
    $(cs.sel).focus();
};
	
/*
 * Prepare the next step
 * @param int cs - current step
 */
M.block_mbswizzard.wizzard.prepare_next_step = function(cs) {
    if (this.sequence.steps.length == cs+1) {
	// the end, there is no next step
	return;
    }
	
    if (window.location.pathname.search(this.sequence.steps[cs+1].url) != -1) {
	// next link of tip
	$("#wizzard_next_step_"+cs).on('click', $.proxy(function(e) {
            this.sequence.current_step = cs+1;
            this.store_sequence(this.sequence);
            this.show_tip(cs+1);
            this.prepare_next_step(cs+1);
	}, this));
    } else {
	$(this.sequence.steps[cs].sel).on('click', $.proxy(function(e) {
            this.sequence.current_step = cs+1;
            this.store_sequence(this.sequence);
	}, this)); 
    }
};

