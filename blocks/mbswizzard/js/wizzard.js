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
        // no event handling on static preview links. Remove this if new wizzard is available!
        if ($(event.target).attr("data-wizzard") === "first_learningsequence") {
            return;
        }
	var seqname = "mbswizzard_sequence_"+$(event.target).attr("data-wizzard");
  	this.copy_sequence_from_json(seqname, $.proxy(function(success){
            if (success) {
		this.sequence = this.get_sequence(seqname);
		this.sequence.current_step = parseInt(this.sequence.current_step);
                this.set_wizzard_state('start', seqname);
	    }
	    // store the name of the current sequence
	    localStorage.setItem("mbswizzard_current_sequence", seqname);
            // redirect force rendering of block to get the progress bar
            window.location.replace(M.cfg['wwwroot']+'/my');
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
	//this.prepare_next_step(this.sequence.current_step);
        
        //Listen to cancel button
        $('div[data-block=block_mbswizzard] .cancel').on('click', $.proxy(function() {
            this.finish_sequence('cancel')
        }, this));
    }
    
    // Set static tooltip for upcoming wizzards. Remove this, if new wizzard is available.
    $('a[data-wizzard=first_learningsequence]').tooltip({
        // trigger: 'click',
        animation: true,
        title: 'Folgt in Kürze.'
    });
    
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
    }, this), 'json')
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
    // Destroy the previous tooltip.
    if(step > 0) {
        $(this.sequence.steps[step-1].sel).tooltip('destroy');
    }
        
    var cs = this.sequence.steps[step];
    
    // Set visibility if element is hidden
    if($(cs.sel).is(':hidden')) {
        $(cs.sel).css("visibility", "visible");
    }
    
    // Show the current tooltip.
    var placement = "auto";
    if (cs.placement != null) {
        placement = cs.placement;
    }
    var viewport = { selector: 'body', padding: 0 };
    if(cs.viewport != null) {
        viewport = cs.viewport;
    }
    
    $(cs.sel).tooltip({
	html: true,
	title: cs.tip,
	trigger: 'manual',
        placement: placement,
        animation: true,
        viewport: viewport
    });
    $(cs.sel).first().tooltip('show');
    this.prepare_next_step(step);
    
    var offsettop = $('#topbar').height() + $('header.me-page-header.full').height() + 150;
    $.scrollTo(cs.sel, 1000, {
        offset: {top:-offsettop}
    });
    $(cs.sel).focus();
    this.update_progressbar();
};
	
/*
 * Prepare the next step
 * @param int cs - current step
 */
M.block_mbswizzard.wizzard.prepare_next_step = function(cstep) { 
    if (this.sequence.steps.length == cstep+1) {
	// the end, there is no next step
        this.prepare_finish_sequence('finish');
	return;
    }
    
    // If there is an action selector to get to the next step, take it. Otherwise listen to the origin target.
    // Only needed, if you stay on the same page
    if (this.sequence.steps[cstep].actionsel != null) {
	$(this.sequence.steps[cstep].actionsel).on('click', $.proxy(function(e) {
            this.sequence.current_step = cstep+1;
            this.store_sequence(this.sequence);
            // if next element isn't on the page the right way immediately, wait a bit
            if($(this.sequence.steps[cstep+1].sel).length == 0 || $(this.sequence.steps[cstep+1].sel).is(':hidden')) {
                setTimeout($.proxy(function(){
                    this.show_tip(cstep+1);
                }, this), 500);
            } else {
                this.show_tip(cstep+1);
            }
            this.prepare_next_step(cstep+1);
	}, this));
    } else {
	$(this.sequence.steps[cstep].sel).on('click', $.proxy(function(e) {
            this.sequence.current_step = cstep+1;
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
        async: false,
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
M.block_mbswizzard.wizzard.prepare_finish_sequence = function(state) {
    $(this.sequence.steps[this.sequence.current_step].sel).on('click', $.proxy(function() {
        this.finish_sequence(state);
    }, this));
};

/**
 * Finish sequence
 * 
 * @param string $state - finish or cancel
 */
M.block_mbswizzard.wizzard.finish_sequence = function(state) {
    this.set_wizzard_state(state, this.sequence.name);
    localStorage.removeItem(this.sequence.name);
    localStorage.removeItem('mbswizzard_current_sequence');
    $('.tooltip').tooltip('destroy');
    if(state === 'finish'){
        alert('Herzlichen Glückwunsch!\nSie haben den Wizzard erfolgreich abgeschlossen.');
    }
};
