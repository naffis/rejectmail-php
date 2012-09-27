<?
// Class input
// v 3.0.

class Tinput {
	
	function update(&$values) {
		GLOBAL $data;
		return strip_tags($values[$data->element_name]);		
	}	
	
	function delete() {
		
	}		
	
	function insert(&$values) {
		GLOBAL $data;
		return strip_tags($values[$data->element_name]);		
	}		
	
	function makeFormElement($value=null) {
		GLOBAL $parser;
		$form_params = $parser->element_params['form'].' '.$parser->fields_global_params;
		
		$parser->tpl->parseVariable(array(
			'NAME'	=> $parser->element_name,
			'VALUE'	=> str_replace('"',"'",$value),
			'PARAMS'=> $form_params
			),'form');
		return trim($parser->tpl->get());
	}
	
	function makeElementView($value) {
		return $value;		
	}	
	
	
	
	
}


?>