YUI.add('moodle-block_getting_started-assistant', function(Y) {
  // Your module code goes here.
 
  // Define a name space to call
  M.block_getting_started = M.block_getting_started || {};
  M.block_getting_started.assistant = {
    init: function() {
      Y.one('#link_assistant_course_create').on('click', function(e){
	      alert("Load the json file to get into the sequence");
      });
    }
  };
}, '@VERSION@', {
  requires: ['node', 'event']
});