/**
 * helper functions to handle events triggered by user interface
 *
 * @package     block_mbswizzard
 * @author      Andre Scherl <andre.scherl@isb.bayern.de>
 * @copyright   2015, ISB Bayern
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
    // On click of an assistant link, load sequence data from json file and set sequence data into browers local storage.
    // Note! The attribute data-wizzard of the link and the json file name should meet each other,
    // e.g. course_create and mbswizzard_sequence_course_create.json
    $('.link_wizzard').on('click', $.proxy(function(event){
        this.event = event;
	var seqname = "mbswizzard_sequence_"+$(event.target).attr("data-wizzard");
  	this.copy_sequence_from_json(seqname, $.proxy(function(success){
            if (success) {
		this.sequence = this.get_sequence(seqname);
		this.sequence.current_step = parseInt(this.sequence.current_step);
                this.set_wizzard_state('start', seqname);
	    }
	    // store the name of the current sequence
	    localStorage.setItem("mbswizzard_current_sequence", seqname);
	}, this));
    }, this));
	    	
    // Load current sequence from localStorage, if the current sequence is null 
    if (!this.sequence.name && localStorage.getItem("mbswizzard_current_sequence")) {
  	this.sequence = this.get_sequence(localStorage.getItem("mbswizzard_current_sequence"));
  	this.sequence.current_step = parseInt(this.sequence.current_step);
    }
    
    if (this.sequence.name) {
	// Show tooltip
	this.show_tip(this.sequence.current_step);
        	
        // Prepare next step
	this.prepare_next_step(this.sequence.current_step);
        
        //Listen to cancel button
        $('div[data-block=block_mbswizzard] .cancel').on('click', $.proxy(function() {
            this.finish_sequence('cancel')
        }, this));
    }
};

/*
 * Copy sequence data from json file to localStorage
 * @param string sname - name of the sequence e.g. course_create
 * @callback bool success
 */
M.block_mbswizzard.wizzard.copy_sequence_from_json = function(sname, callback) {
    $.get(M.cfg['wwwroot']+"/blocks/mbswizzard/js/sequences/"+sname+".json", $.proxy(function(jsonobject){
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
    //$.scrollTo(cs.sel);
    this.update_progressbar();
};
	
/*
 * Prepare the next step
 * @param int cs - current step
 */
M.block_mbswizzard.wizzard.prepare_next_step = function(cs) {
    if (this.sequence.steps.length == cs+1) {
	// the end, there is no next step
        this.prepare_finish_sequence('finish');
	return;
    }
	
    if (window.location.pathname.search(this.sequence.steps[cs+1].url) != -1) {
	// next link of tip
	$('.wizzard_next_step').on('click', $.proxy(function(e) {
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

/*
 * Set state of wizzard in user session object
 * @param string state - "start" or "finish"
 */
M.block_mbswizzard.wizzard.set_wizzard_state = function(state, sequence) {
    $.ajax({
        url: M.cfg['wwwroot']+'/blocks/mbswizzard/ajax.php',
        method: "POST",
        data: {
            action: state+"wizzard",
            sequence: sequence,
            sesskey: M.cfg['sesskey']
        }
    });
};

/**
 * Update the progress bar for current sequence step
 */
M.block_mbswizzard.wizzard.update_progressbar = function() {
    $('div[data-block=block_mbswizzard] .progress-bar').attr('aria-valuenow', this.sequence.current_step);
    $('div[data-block=block_mbswizzard] .progress-bar').attr('aria-valuemax', (this.sequence.steps.length-1));
    var percent = this.sequence.current_step/(this.sequence.steps.length-1)*100;
    $('div[data-block=block_mbswizzard] .progress-bar').attr('style', 'width: '+percent+'%');
    $('div[data-block=block_mbswizzard] .sr-only').text(percent+'% Complete');
    $('div[data-block=block_mbswizzard] .currentstepnumber').text(this.sequence.current_step+1);
    $('div[data-block=block_mbswizzard] .maxstepnumber').text(this.sequence.steps.length);
};

/**
 * Prepare the finish of the sequence by the action the last step
 * 
 * @param string $state - finish or cancel
 */
M.block_mbswizzard.wizzard.prepare_finish_sequence = function($state) {
    $(this.sequence.steps[this.sequence.current_step].sel).on('click', $.proxy(function() {
        this.finish_sequence($state);
    }, this));
};

/**
 * Finish sequence
 * 
 * @param string $state - finish or cancel
 */
M.block_mbswizzard.wizzard.finish_sequence = function($state) {
    this.set_wizzard_state($state, this.sequence.name);
    localStorage.removeItem(this.sequence.name);
    localStorage.removeItem('mbswizzard_current_sequence');
};
