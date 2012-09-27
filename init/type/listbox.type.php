<?
// Class content
// v 3.0.

class Tlistbox {
	
	function update(&$values) {
		GLOBAL $data;

		$name = $data->element_name;
		$count_name = strlen($name);
		foreach ($values as $key => $val) {
			if (substr($key, 0, $count_name) == $name) {
				$listbox_id = substr($key, $count_name + 1);
				if (!empty($listbox_id))
					$rez .= '<'.$listbox_id.'>';
			}
		}
		return $rez;	
	}	

	function insert(&$values) {
		GLOBAL $data;

		$name = $data->element_name;
		$count_name = strlen($name);
		foreach ($values as $key => $val) {
			if (substr($key, 0, $count_name) == $name) {
				$listbox_id = substr($key, $count_name + 1);
				if (!empty($listbox_id))
					$rez .= '<'.$listbox_id.'>';
			}
		}
		return $rez;	
	}	

	function delete() {
		
	}		
	
	function __makeListboxFormElement($arr, $value, $element_params = null) {
		GLOBAL $parser;
		if (isset($arr) and is_array($arr)) {
			$parser->tpl->setCurrentBlock('form_row');
			foreach($arr as $key => $name) {
				if (is_array($name)) {
					$key = $name['id'];
					$name = $name['name'];
				}
				$parser->tpl->setVariable(array(
											'VALUE'		=> $key,
											'NAME'		=> $parser->element_name.'_'.$key,
											'CAPTION'	=> $name,
											'SELECTED'	=> (substr_count($value, '<'.$key.'>')>0)?' selected':null
				));
				$parser->tpl->parseCurrentBlock();
			}
			$params = $element_params.' '.$parser->fields_global_params;
			$parser->tpl->setCurrentBlock('form');
			$parser->tpl->setVariable(array(
				'NAME'		=> $parser->element_name,
				'PARAMS'	=> $params
			));
			$parser->tpl->parseCurrentBlock();				
		}
	}
	
	function __makeListboxViewElement($list,$value) {
		GLOBAL $parser;
		
		$tpl = new template(TPL_PATH);
		$tpl->loadTemplateFile($parser->getSystemTemplate('type/listbox'));
		
		if (isset($list) and is_array($list)) {
				$values = preg_split("/[<>]/", $value, -1, PREG_SPLIT_NO_EMPTY);
				foreach ($values as $key => $value) {
					$values[$key] = $list[$value];
				}
				if (empty($values)) {
					$tpl->touchBlock('view_empty');
				}
				else {
					$cnt = 0;
					foreach ($list as $name) {
						$cnt++;
						if (in_array($name, $values)) {
							$tpl->setCurrentBlock('yes');
							$tpl->setVariable(array(
								'YES'	=> ''
							));
						} else {
							$tpl->setCurrentBlock('no');
							$tpl->setVariable('NO', '');
							
						}
						$tpl->setVariable(array(
							'NAME'	=> $name
						));
						$tpl->parseCurrentBlock();
							
						if ($cnt != count($list)) $tpl->touchBlock('view_separator');

						$tpl->setCurrentBlock('view_row');
						$tpl->setVariable(array(
							'ROW'	=> $name
						));
						$tpl->parseCurrentBlock();
					}
					$tpl->setCurrentBlock('view');
					$tpl->parseCurrentBlock();
				}
			}
		return trim($tpl->get());
	}
	
	function makeFormElement($value = null, $element_params = null) {
		GLOBAL $parser;
		GLOBAL $db;
		GLOBAL $app;
		
		// get type info details
		if ($parser->element_type_info == 'arr') {
			$matches[1] = $parser->element_type_info;
		} else { 
			preg_match('/^([a-z]+)\_([a-z_]+)$/', $parser->element_type_info, $matches);
		}
		switch ($matches[1]) {			
			case 'def':
				$arr = $GLOBALS['LIST_'.strtoupper($matches[2])];
				if (!arrayIsOk($arr)) {
					$app->raiseError('Array <b>LIST_'.strtoupper($matches[2]).'</b> not found. In parser->makeFormElements()');
				}
				break;	
				
			case 'table':
				$cross_field = 'name';
				if (isset($parser->element_params['cross_field'])) {
					$cross_field = $parser->element_params['cross_field'];	
				}			
			
				$arr = $db->select($element_params['cond'], '', 'id,'.$cross_field.' as name', $matches[2]);
				break;	
				
			case 'data':
				$data = $GLOBALS['DATA_'.strtoupper($matches[2])];
				$listbox_table = $data['table'];
				
				$cross_field = 'name';
				if (isset($parser->element_params['cross_field'])) {
					$cross_field = $parser->element_params['cross_field'];	
				}				
				
 				$arr = $db->select($element_params['cond'], $data['order'], 'id,'.$cross_field.' as name', $listbox_table);
 				break;
					
			case 'arr':
				$arr = $parser->form_values;
				break;	
					
			default:
				GLOBAL $app;
				$app->raiseError('Unknown listbox type <b>'.$parser->element_type_info.'</b> (listbox.type :: makeFormElements)');
				break;																
		}

		if (arrayIsOk($arr)) { 
			// add additional values
			if (arrayIsOk($element_params['add_values'])) {
				$arr = array_merge($element_params['add_values'], $arr);	
			}
			$this->__makeListboxFormElement($arr, $value, $element_params['form']);
		}	
		
		return trim($parser->tpl->get());
	}

	function makeElementView($value) {
		GLOBAL $parser;
		GLOBAL $db;

		// get type info details
		if ($parser->element_type_info == 'arr') {
			$matches[1] = $parser->element_type_info;
		} else { 
			preg_match('/^([a-z]+)\_([a-z_]+)$/', $parser->element_type_info, $matches);
		}

		switch ($matches[1]) {			
		case 'def':
			//DEF
			$list = $GLOBALS['LIST_'.strtoupper($matches[2])];
			$result = $this->__makeListboxViewElement($list,$value);
			break;
		case 'arr':
			//ARRAY
			$list = $parser->form_values;
			$result = $this->__makeListboxViewElement($list,$value);
			break;
		case 'table':
			//TABLE
			$cross_field = 'name';
			if (isset($parser->element_params['cross_field'])) {
				$cross_field = $parser->element_params['cross_field'];	
			}			
			
			$list_temp = $db->select('', '', 'id,'.$cross_field.' as name', $matches[2]);
			if ($list_temp) {
				foreach($list_temp as $key => $row) {
					$list[$row['id']] = $row['name'];
				}
			}
			$result = $this->__makeListboxViewElement($list,$value);
			break;
		case 'data':
			//DATA 
			$data = $GLOBALS['DATA_'.strtoupper($matches[2])];
			$list_table = $data['table'];
			
			$cross_field = 'name';
			if (isset($parser->element_params['cross_field'])) {
				$cross_field = $parser->element_params['cross_field'];	
			}			
			
			$list_temp = $db->select('', '', 'id,'.$cross_field.' as name', $list_table);
			if ($list_temp) {
				foreach($list_temp as $key => $row) {
					$list[$row['id']] = $row['name'];
				}
			}
			$result = $this->__makeListboxViewElement($list,$value);
			break;
		} 
		// END VIEW LISTBOX
		
		return $result;
	}
}

?>