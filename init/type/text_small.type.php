<?
// Class text_small
// v 3.0.

class Ttext_small {
	
	function delete() {
		
	}		
	
	function update(&$values) {
		GLOBAL $data;
		return strip_tags($values[$data->element_name]);			
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
			'VALUE'	=> $value,
			'PARAMS'=> $form_params
		), 'form');
		return trim($parser->tpl->get());
	}
	
	function makeElementView($value) {
		return nl2br($value);	
	}	
	
	
	
	
}









?>