<?
// Class hidden
// v 3.0.

class Thidden {
	
	function update(&$values) {
		GLOBAL $data;
		return $values[$data->element_name];		
	}
	
	function delete() {
		
	}	
	
	function insert(&$values) {
		GLOBAL $data;
		return $values[$data->element_name];		
	}
	
	function makeFormElement($value=null) {
		GLOBAL $parser;

		$parser->tpl->parseVariable(array(
			'NAME'	=> $parser->element_name,
			'VALUE'	=> $value
		),'form');
		return trim($parser->tpl->get());
	}
	
	function makeElementView($value) {
		return $value;		
	}

	
}



?>