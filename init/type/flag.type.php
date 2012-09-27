<?
// Class flag
// v 3.0.

class Tflag {
	
	function update($values) {
		GLOBAL $data;
		return $values[$data->element_name]==''?($values[$data->element_name.'_checked']?1:0):$values[$data->element_name];
	}	

	function delete() {
		
	}	
	
	function insert($values) {
		GLOBAL $data;
		return $values[$data->element_name]==''?($values[$data->element_name.'_checked']?1:0):$values[$data->element_name];
	}	
	
	function makeFormElement($value=null) {
		GLOBAL $parser;

		$form_params = $parser->element_params['form'].' '.$parser->fields_global_params;
		$parser->tpl->parseVariable(array(
			'NAME'		=> $parser->element_name,
			'CHECKED'	=> ($value==1)?' checked':'',
			'PARAMS'	=> $form_params
			),'form');
		return trim($parser->tpl->get());		
	}
	
	function makeElementView($value) {
		GLOBAL $parser;
		
		$tpl = new template(TPL_PATH);
		$tpl->loadTemplateFile($parser->getSystemTemplate('type/flag'));
		
		if ($value==1) {
			$tpl->touchBlock('view_yes');
		} else {
			$tpl->touchBlock('view_no');
		} 
		return trim($tpl->get());		
	}	

	
}



?>