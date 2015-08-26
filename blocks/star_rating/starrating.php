// <?php
class block_star_rating extends block_base {
	public function init() {
		$this->title = get_string ( 'simplehtml', 'block_simplehtml' );
	}
	
	public function get_content() {
		$this->content = new stdClass ();
		$this->content->text = 'The content of our SimpleHTML block!';
		$this->content->footer = 'Footer here...';
		
		return $this->content;
	}
}