<?
// Class text
// v 3.0.

class Ttext {
	
	function update(&$values) {
		GLOBAL $data;
		$name = $data->element_name;
		return preg_replace('/<(\s*)script(.*)>(.*?)<\/script>/i', '', $values[$name]);	
	}	

	function delete() {
		
	}		

	function insert(&$values) {
		GLOBAL $data;
		$name = $data->element_name;
		return preg_replace('/<(\s*)script(.*)>(.*)<\/script>/i', '', $values[$name]);	
	}

	function makeFormElement($value=null, $element_params=null) {
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
		if ($value == strip_tags($value)) {
			return nl2br($value);
		} else {
			return $value;
		}		
	}	
	
	
	
	
}









?>