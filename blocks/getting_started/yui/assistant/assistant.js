/**
 * helper functions to handle events triggered by user interface
 *
 * @package	DLB - block: getting_started
 * @author	Andre Scherl
 *
 * Copyright (C) 2014, Andre Scherl
 * You should have received a copy of the GNU General Public License
 * along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 */
 
YUI.add('moodle-block_getting_started-assistant', function(Y) {
	// Define a name space to call
	M.block_getting_started = {};
	
	M.block_getting_started.assistant = {  
    	sequence: {
	    	name: null,
	    	current_step: 0,
	    	steps: []	
	    }
	};
	
	M.block_getting_started.assistant.init = function() {
		// On click of an assistant link, load sequence data from json file and set sequence data into browers local storage
		// Note! The id of the link and the json file name should meet each other, e.g. link_assistant_course_create and sequence_course_create.json
		Y.all('.block_getting_started .link_assistant').on('click', Y.bind(function(e){
			var seqname = e.target.get("id").split("link_assistant_")[1];
	    	this.copy_sequence_from_json(seqname, Y.bind(function(success){
		    	if(success) {
			    	this.sequence = this.get_sequence(seqname);
			    	this.sequence.current_step = 0;
		    	}
		    	// store the name of the current sequence
		    	localStorage.setItem("current_sequence", seqname);
	    	}, this));
    	}, this));
    	
    	// Load current sequence from localStorage, if the current sequence ist null 
    	if(!this.sequence.name && localStorage.getItem("current_sequence")) {
	    	this.sequence = localStorage.getItem(localStorage.getItem("current_sequence"));
    	}
	};
	
	/*
	 * Get sequence into an js object
	 * @param string sname - name of the sequence e.g. course_create
	 * @return object sequence
	 */
	M.block_getting_started.assistant.get_sequence = function(sname) {
		var seq = localStorage.getItem(sname);
		if(seq) {
			return JSON.parse(seq);	
		}
		return null;	
	};
	
	/*
	 * Copy sequence data from json file to localStorage
	 * @param string sname - name of the sequence e.g. course_create
	 * @callback bool success
	 */
	M.block_getting_started.assistant.copy_sequence_from_json = function(sname, callback) {
		Y.io(M.cfg['wwwroot']+"/blocks/getting_started/yui/assistant/sequence_"+sname+".json", {
		    	on: {
			    	success: Y.bind(function(id, o){
				    	localStorage.setItem(sname, o.responseText);
				    	callback(true);
			    	}, this),
			    	failure: function(id, o) {
				    	alert("Sequence loading from json failed.");
				    	callback(false);
			    	}
		    	}
	    	});	
	};
}, '@VERSION@', {
	requires: ['node', 'event', 'io']
});