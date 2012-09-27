<?
// Class list
// v 3.0.

class Tlist {
	
	function delete() {
		
	}		
	
	function update(&$values) {
		GLOBAL $data;
		return $values[$data->element_name];			
	}	
	
	function insert(&$values) {
		GLOBAL $data;
		return $values[$data->element_name];		
	}

	function __makeListFormElement($arr,$value,$element_params=null) {
		GLOBAL $parser;
		
		$form_params = $parser->element_params['form'].' '.$parser->fields_global_params;
		
		$parser->tpl->setCurrentBlock('form_list_row');
		foreach ($arr as $key=>$name) {
			if (is_array($name)) {
				$key=$name['id'];
				$name=$name['name'];
			}
			$parser->tpl->setVariable(array(
				'CAPTION'	=> $name,
				'VALUE'		=> $key,
				'SELECTED'	=> (isset($value) && $key==$value)?' selected':null
			));
			$parser->tpl->parseCurrentBlock();
		}
		
		$parser->tpl->setcurrentBlock('form');
		$parser->tpl->setVariable(array(
			'NAME'		=> $parser->element_name,
			'PARAMS'	=> $form_params
		));
		$parser->tpl->parseCurrentBlock();				
	}	
	
	function makeFormElement($value=null) {
		GLOBAL $parser;
		GLOBAL $db;
		GLOBAL $app;

		$element_params = $parser->element_params;
		
		// get type info details
		if ($parser->element_type_info=='arr') {
			$matches[1]=$parser->element_type_info;
		} else { 
			preg_match('/^([a-z]+)\_([a-z_0-9]+)$/', $parser->element_type_info, $matches);
		}
		switch ($matches[1]) {			
			case 'def':
				$arr=$GLOBALS['LIST_'.strtoupper($matches[2])];
				if (!arrayIsOk($arr)) {
					$app->raiseError('Array <b>LIST_'.strtoupper($matches[2]).'</b> not found. In parser->makeFormElements()');
				}
				break;	
				
			case 'table':
				// get cross field
				$cross_field = (isset($element_params['cross_field']))?$element_params['cross_field']:'name';	
			
				$arr=$db->select($element_params['cond'],'','id,'.$cross_field.' as name',$matches[2]);
				break;	
				
			case 'data':
				$data=$GLOBALS['DATA_'.strtoupper($matches[2])];
				$list_table=$data['table'];
				
				// get cross field
				$cross_field = (isset($element_params['cross_field']))?$element_params['cross_field']:'name';				
				
 				$arr=$db->select($element_params['cond'],$data['order'],'id,'.$cross_field.' as name',$list_table);
 				break;
					
			case 'arr':
				$arr=$parser->form_values;
				break;	
					
			default:
				GLOBAL $app;
				$app->raiseError('Unknown list type <b>'.$parser->element_type_info.'</b> (list.type :: makeFormElements)');
				break;																
		}

		if (arrayIsOk($arr)) { 
			// add additional values
			if (arrayIsOk($element_params['add_values'])) {
				$arr=array_merge($element_params['add_values'],$arr);	
			}
			$this->__makeListFormElement($arr,$value);
		}	
		
		return trim($parser->tpl->get());
	}
	
	function makeElementView($value) {
		GLOBAL $parser;
		GLOBAL $db;
		GLOBAL $app;
		
		// get type info details
		if ($parser->element_type_info=='arr') {
			$matches[1]=$parser->element_type_info;
		} else { 
			preg_match('/^([a-z]+)\_([a-z_0-9]+)$/', $parser->element_type_info, $matches);
		}
				
		switch ($matches[1]) {			
			case 'def':
				$arr=$GLOBALS['LIST_'.strtoupper($matches[2])];
				if (!arrayIsOk($arr)) {
					$app->raiseError('Array <b>LIST_'.strtoupper($matches[2]).'</b> not found. In parser->makeFormElements()');
				}
			break;	
				
			case 'table':
				$cross_field = 'name';
				if (isset($parser->element_params['cross_field'])) {
					$cross_field = $parser->element_params['cross_field'];
				}
							
				return $db->get("id=$value",$cross_field,$matches[2]);
			break;	
				
			case 'data':
				$data=$GLOBALS['DATA_'.strtoupper($matches[2])];
				$list_table=$data['table'];
				
				// get field that will be shown
				$cross_field = 'name';
				if (isset($parser->element_params['cross_field'])) {
					$cross_field = $parser->element_params['cross_field'];	
				}
				
 				if ($value!='') {
 					$rez=$db->get("id=$value",$cross_field,$list_table);
 				}
 				return $rez;
 			break;
					
			case 'arr':
				$arr=$parser->form_values;
			break;	
					
			default:
				GLOBAL $app;
				$app->raiseError('Unknown list type <b>'.$parser->element_type_info.'</b> (list.type :: makeElementView)');
			break;																
		}		
		return $arr[$value];		
	}	

}



?>