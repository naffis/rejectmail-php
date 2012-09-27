<?
// Class Password
// v 3.0.

class Tpassword {
	
	function update(&$values) {
		GLOBAL $data;
		GLOBAL $db;
		GLOBAL $app;
		$name=$data->element_name;
		$oldpassword = $db->get($data->buffer, $name);
		if ($values[$name] != '') {		
//			if ($oldpassword != $values['old_'.$name]) {
//				$return = false;
//				$app->setError(ERROR_PASSWORD_UPDATE);
//			} else {
				$return = $values[$name];	
//			}
		} else {
			$return = false;
		}
		return $return;
	}	
	
	function delete() {
		
	}		

	function insert(&$values) {
		GLOBAL $data;
		return $values[$data->element_name];		
	}		
	
	function makeFormElement($value=null) {
		GLOBAL $parser;
		
		$form_params = $parser->element_params['form'].' '.$parser->fields_global_params;
		
		$parser->tpl->parseVariable(array(
			'NAME'	=> $parser->element_name,
			'PARAMS'=> $form_params
		), 'form');
		
		return trim($parser->tpl->get());
	}
	
	function makeElementView($value) {
		GLOBAL $parser;
		
		if (!isset($parser->element_params['view'])){
			return $value;
		} else {
			return 	$parser->element_params['view'];
		}
	}	
	
	
	
	
}



?>