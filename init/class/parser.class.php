<?php
/*  
	$Header: /cvs_repository/lisk/engine/init/class/parser.class.php,v 1.3 2005/02/14 11:00:06 andrew Exp $
	
	Class parser
    version 3.0
    
    Wed Nov 17 13:55:09 EET 2004 - syntax fix
*/ 

class parser {
	var $cur_tpl_name,		// current template file name

	//	$date_format,		// date format expretion
	//	$time_format,		// time format expretion

		$fields,			// array of field name => type defenitions
		$fields_name,		// array of fields name for form processing
		$fields_params,		// array of fields params
		
		$list_decor,		//true or false
		$list_decoration1 = '',
		$list_decoration2 = '',
		
		$fields_global_params,	// general param for field form elements (f.e. - css style class)

		$element_name,		// element name
		$element_type,		// element type
		$element_type_info,	// type information (list_table_cities) = table_cities
		$element_params,	// element params array of defenitions
		$value,

		$list_columns,		// array with captions for list f.e. dynamic list v CMS (list.php)
		$list_columns_add_variables, // array with add variables for caption line in list (sort in cms list)

		$template_loaded=false,
		//$template_cur,
		
		$buffer,			// buffer - save any data

		$form_values, // list array element array of list :)
		$styles, // form elements styles


		$elements_view,

		$caption_variables,	// Caption variables for list, table, etc...
		$add_variables,		// addional variables for list_row, table_row, etc...

		$file_path,

		$tpl;

	/**
	* @return void
	* @desc Constructor. Create $parser->tpl object, setup date & time format
	*/
	function parser() {

		// cretae new TPL object for make functions
		$this->tpl = new template(TPL_PATH);

		// setting up date & time format
		//$this->date_format = '';
		//$this->time_format = '';
	}

	/**
	* @return void
	* @param string $tpl_name - template file name
	* @param bool $param1
	* @param bool $param2
	* @desc Load template file to $parser->tpl object
	*/
	function loadTemplate($tpl_name,$param1=true,$param2=true) {
		if ($this->cur_tpl_name!=$tpl_name) {
			$this->tpl->loadTemplatefile($tpl_name,$param1,$param2);
			$this->cur_tpl_name=$tpl_name;
		} else {
			$this->tpl->blockdata = '';
			$this->tpl->free();
		}
	}
	
	/**
	* @return string
	* @param string $name
	* @desc get system template
	*/
	function getSystemTemplate($name) {
		if (file_exists(TPL_PATH.'def/'.$name.'.'.TPL_EXT)) {
			$tpl_name='def/'.$name;
		} else {
			$tpl_name=ROOT_PATH.'../'.TPL_PATH.'global/'.$name;
		}

		return $tpl_name;
	}
	
	/**
	* @return string
	* @param string $name
	* @desc Function return path anf file name of the Type Tpl file
	*/
	/*function getTypeTemplate($name) {
		if (file_exists(TPL_PATH.'type/'.$name.'.htm')) {
			$tpl_name='type/'.$name;
		} else {
			$tpl_name=GLOBAL_TPL_PATH.'type/'.$name;
		}
		return $tpl_name;
	}	
	*/
	/**
	* @return string
	* @param string $name
	* @desc get editor path
	*/
	function __getEditorPath($name) {
		if (file_exists('editors/'.$name.'/')) {
			$path= 'editors/'.$name.'/';
		} else {
			$path = ROOT_PATH.'editors/'.$name.'/';
		}
		return $path;
	}

	/**
	* @return void
	* @param string $name Element name
	* @param string $type Element type (image, file, list, prop, etc)
	* @param array $values
	* @desc Set current element, load element-related type.
	*/
	function setElement($name,$type,$values=null) {
		GLOBAL $app;
		
		$this->element_params=array();

		if (arrayIsOk($type)) {
			// advanced field description

			// check is type isset
			if ($type['type']=='') $app->raiseError("Field type is undefined for data <b>$name</b> In data->set()");

			// get field name
			foreach ($type as $param_key=>$param_value) {
				if ($param_key!='type' && $param_key!='name') {
					$this->element_params[$param_key] = $param_value;
				}
			}
			$type=$type['type'];
		}

		if (isset($this->fields_params[$name]) && arrayIsOk($this->fields_params[$name])) {
			$this->element_params = array_merge($this->element_params,$this->fields_params[$name]);
		}

		if (substr($type,0,6) == 'image_') {
			$this->element_type_info=substr($type,6);
			$type='image';
		} else
		if (substr($type,0,5) == 'file_') {
			$this->element_type_info=substr($type,5);
			$type='file';
		} else
		if (substr($type,0,5) == 'list_') {
			$this->element_type_info=substr($type,5);
			$type='list';
		} else
		if (substr($type,0,5) == 'prop_') {
			$this->element_type_info=substr($type,5);
			$type='prop';
		} else
		if (substr($type,0,8) == 'listbox_') {
			$this->element_type_info=substr($type,8);
			$type='listbox';
		} else
		if (substr($type,0,6) == 'radio_') {
			$this->element_type_info=substr($type,6);
			$type='radio';
		} else if (substr($type,0,9) == 'category_') {
			$this->element_type_info = substr($type, 9);
			$type='category';
		}

		
		$app->load($type,'type');
		
		$this->element_name=$name;
		$this->element_type=$type;
		
		if (isset($values)) $this->form_values=$values;
	}

	/**
	* @return void
	* @param array $fields
	* @param array $fields_name
	* @param array $fields_params
	* @desc set fields, names, params
	*/
	function setFields($fields, $fields_name, $fields_params) {
		$this->fields = $fields;
		$this->fields_name = $fields_name;
		$this->fields_params = $fields_params;
	}

	function setFieldsGlobalParams($name) {
		$this->fields_global_params = $name;
	}

	/**
	* @return void
	* @param array $caption_variables Array of caption variables
	* @desc Set caption variables.
	*/
	function setCaption($caption_variables) {
		if (is_array($caption_variables)) {
			$this->caption_variables = $caption_variables;
		} else {
			$app->raiseError('Caption variables is not an array. In $parser->setCaption');
		}
	}
	//razobrat'sya
	function setListColumns($columns) {
		if (arrayIsOk($columns)) {
			foreach ($columns as $key=>$name) {
				if (arrayIsOk($name)) {
					$columns[$key]['element']=ucwords(str_replace('_', '&nbsp;', $name['element']));
				} else {
					$columns[$key]=ucwords(str_replace('_', '&nbsp;', $name));
				}
			}
			$this->list_columns = $columns;
		}
	}

	/**
	* @return void
	* @param array $add_variables Array of additional variables
	* @desc Set additionals variables.
	*/
	function setAddVariables($add_variables) {
		GLOBAL $app;
		if (is_array($add_variables)) {
			$this->add_variables = array_merge($this->add_variables,$add_variables);
		} else {
			$app->raiseError('Add variables is not an array. In $parser->setAddVariables');
		}
	}

	/**
	* @return array Array of additional varibles
	* @desc Get additional varibels array
	*/
	function __getAddVariables() {
		$add=array();
		if (isset($this->add_variables)) {
			$add = $this->add_variables;
		}
		return $add;
	}
	
	function __getCaptionVariables() {
		$caption=array();
		if (isset($this->caption_variables)) {
			$caption = $this->caption_variables;
		}
		return $caption;	
	}

	/**
	* @return void
	* @desc Clear all additional varibles
	*/
	function __clearAddVariables() {
		unset($this->caption_variables);
		unset($this->add_variables);
	}
	
	function __clearFieldsInfo() {
		unset($this->fields_name);
	}

	function getHtml($tpl_name,$block_name) {
		$this->loadTemplate($tpl_name);
		$this->tpl->touchBlock($block_name);
		return $this->tpl->get();
	}

	// ========================= FORM METHODS ================================
	/*
	* @return string HTML code for form element
	* @param unknown $value
	* @param array $element_params
	* @desc Make form element code. Load corresponig object and delegate creation to object's method.
	*/
	function makeFormElement($value=null) {
		$tpl_name=$this->getSystemTemplate('type/'.$this->element_type);
		$this->loadTemplate($tpl_name);
		eval('$object = new T'.$this->element_type.'();');
		return $object->makeFormElement($value);
	}

	/**
	* @return void
	* @param unknown $value
	* @param array $element_params
	* @param unknown $id ???
	* @desc Parse form element
	*/
	function parseFormElement($value=null, $element_params=null, $id=null) {
		GLOBAL $tpl;
		$tpl->setVariable(array(
			strtoupper($this->element_name)	=> $this->makeFormElement($value,$element_params,$id)
		));
	}

	/**
	* @return array Array of 2 arrays: $form_elements -- visible elements, $$hidden_elements -- hidden elements
	* @param array $fields
	* @param array $values
	* @desc Make all form elements and return them in 2 array.
	*/
	function __makeFormElements($fields,$values) {
		
		$form_elements=array();
		$hidden_elements = array();

		foreach ($fields as $name=>$type) {
			$this->setElement($name,$type);

			switch ($this->element_type) {
				case 'hidden':
					// hidden elements goes to $hidden_elements array
					$hidden_elements[] = $this->makeFormElement($values[$name]);
					break;
				case 'password':
					if (isset($values[$name])) {
						// Update password
						$this->setElement($name."_old","password");
						$form_elements[$name."_old"]=$this->makeFormElement();

						$this->setElement($name,$type);
						$form_elements[$name."_new"]=$this->makeFormElement($values[$name]);

						$this->setElement($name."_confirmation","password");
						$form_elements[$name."_confirmation"]=$this->makeFormElement();
					} else {
						// Insert password
						$form_elements[$name]=$this->makeFormElement($values[$name]);

						$this->setElement($name."_confirmation","password");
						$form_elements[$name."_confirmation"]=$this->makeFormElement();
					}
					break;
				case 'image':
					$form_elements[$name]=$this->makeFormElement($values['id']);
					break;
				case 'file':
					$form_elements[$name]=$this->makeFormElement($values['id'].'_'.$values[$name]);
					break;
				default:
					$form_elements[$name]=$this->makeFormElement($values[$name]);
					break;
			}
		}

		return array($form_elements, $hidden_elements);
	}
	
	/**
	* @return string
	* @param array $fields
	* @param array $values
	* @param string $tpl_name
	* @param string $block_name
	* @desc make html form
	*/
	function makeForm($fields, $values=null, $tpl_name='system', $block_name='form') {
	    

		// get fe & he html
		list($fields, $hidden_fields) = $this->__makeFormElements($fields,$values);

		// get system template name if $tpl_name isn't set
		if ($tpl_name=='system') {
			$tpl_name=$this->getSystemTemplate('form');
			
		}
		
		$this->loadTemplate($tpl_name);
		$this->__formHandler($fields, $hidden_fields, $block_name, $this->tpl);
		
		return $this->tpl->get();
	}
	
	/**
	* @return void
	* @param array $fields
	* @param array $values
	* @param string $block_name
	* @desc parse html form
	*/
	function parseForm($fields, $values=null, $block_name='form') {
		// get fe & he html
		list($fields, $hidden_fields)=$this->__makeFormElements($fields,$values);
		GLOBAL $tpl;
		$this->__formHandler($fields, $hidden_fields, $block_name, $tpl);
	}

	/**
	* @return string
	* @param array $fields
	* @param array $values
	* @param string $tpl_name
	* @param string $block_name
	* @desc make custom html form
	*/
	function makeCustomForm($fields, $values, $tpl_name, $block_name) {
		// set tpl_name to buffer it will be loaded after elements form views will be created
		$this->buffer=$tpl_name;

		$this->__customFormHandler($fields, $values, $block_name, $this->tpl);
		return $this->tpl->get();
	}
	
	/**
	* @return void
	* @param array $fields
	* @param array $values
	* @param string $tpl_name
	* @param string $block_name
	* @desc parse custom html form
	*/
	function parseCustomForm($fields, $values, $block_name) {
		GLOBAL $tpl;
		$this->__customFormHandler($fields, $values, $block_name, $tpl);
	}


	/**
	* @return void
	* @param array $fields
	* @param array $hidden_fields
	* @param string $block_name
	* @param object &$tpl
	* @desc parse form data
	*/
	function __formHandler($fields, $hidden_fields, $block_name='form', &$tpl) {
		$hidden_row_name = $block_name.'_hidden';
		$row_name = $block_name.'_row';

		// parse visible fields
		$tpl->setCurrentBlock($row_name);
		foreach($fields as $name=>$value) {
			// get name (some fields have no name in $this->fields_name so as was added (f.e. password field)
			$caption = (isset($this->fields_name[$name]))?$this->fields_name[$name]:$name;
			$caption = ucwords(str_replace('_', ' ', $caption));
			// FORM CHECK
			// ========================================
			if (!is_null($this->fields_params[$name]['check'])) {
				$chk = $this->fields_params[$name]['check'];
				preg_match('/[^\\\\](\*|\+|\{([0-9]+)\,([0-9]*)\})/', ' ' . $chk, $matches);
				if ($matches[1] == '+') $min = 1;
				else list($tmp, $tmp, $min, $max) = $matches;
				$min = (int)$min;
				$max = (int)$max;
				$type = preg_replace('/^([a-z]+)_.*/', '\1', $this->fields[$name]);
				$msg = $this->fields_params[$name]['check_msg'];
				$check[] = "['$name', '$caption', '$type', '$chk', $min, $max, '$msg']";
			}
			// =======================================		

			$tpl->setVariable(array(
				'CAPTION'	=> $caption,
				'FIELD'		=> $value
			));
			$tpl->parseCurrentBlock();
		}

		// parse hidden fields
		if (arrayIsOk($hidden_fields)) {
			$tpl->setCurrentBlock($hidden_row_name);
			foreach($hidden_fields as $value) {
				$tpl->setVariable(array(
					'HIDDEN'		=> $value
				));
				$tpl->parseCurrentBlock();
			}
		}
		
		// FORM CHECK
		// ========================================
		// parse form
		if (arrayIsOk($check)) {
			$check = '[' . implode(',',$check) . ']';
		} else {
			$check='[]';	
		}
		// ========================================		

		// parse form
		$tpl->setCurrentBlock($block_name);
		$tpl->setVariable(array(
			'JS_CHECK' => $check
		));
		$tpl->parseCurrentBlock();

		// clear data settings
		$this->__clearFieldsInfo();

	}
	
	/**
	* @return void
	* @param array $fields
	* @param array $values
	* @param string $block_name
	* @param object &$tpl
	* @desc parse custom form data
	*/
	function __customFormHandler($fields, $values, $block_name, &$tpl) {
		$parse_variables = array();
		
		foreach($fields as $name=>$type) {
			$this->setElement($name, $type);
			switch ($this->element_type) {
				case 'image':
					$value=$values['id'];	
					break;
				default:
					$value=$values[$name];
					break;	
			}
			
			$caption = formatCaption($this->fields_name[$name]);

			$parse_fields['FIELD_'.strtoupper($name)] = $this->makeFormElement($value);
			$parse_fields[strtoupper($name)] = $this->makeElementView($value);
			$parse_fields[strtoupper($name).'_CAPTION'] = $caption;			
			

			// FORM CHECK
			// ========================================
			if (!is_null($this->fields_params[$name]['check'])) {
				$chk = $this->fields_params[$name]['check'];
				preg_match('/[^\\\\](\*|\+|\{([0-9]+)\,([0-9]*)\})/', ' ' . $chk, $matches);
				if ($matches[1] == '+') $min = 1;
				else list($tmp, $tmp, $min, $max) = $matches;
				$min = (int)$min;
				$max = (int)$max;
				$type = preg_replace('/^([a-z]+)_.*/', '\1', $this->fields[$name]);
				$msg = $this->fields_params[$name]['check_msg'];
				$check[] = "['$name', '$caption', '$type', '$chk', $min, $max, '$msg']";
			}
			// =======================================	
			
			
			// add confirmation fields for password
			if ($type=='password') {
				$this->setElement($name.'_confirmation', $type);
				$parse_fields['FIELD_'.strtoupper($name).'_CONFIRMATION'] = $this->makeFormElement($values[$name]);
				$parse_fields[strtoupper($name).'_CONFIRMATION'] = $this->makeElementView($values[$name]);
				$parse_fields[strtoupper($name).'_CONFIRMATION_CAPTION'] = formatCaption($this->fields_name[$name].' confirmation');
							
			}
		}

		if ($this->buffer!='') {
			$this->loadTemplate($this->buffer);
			unset($this->buffer);
		}

		if (arrayIsOk($this->add_variables)) {
			$parse_fields+=$this->add_variables;
		}

		if (arrayIsOk($this->caption_variables)) {
			$parse_fields+=$this->caption_variables;
		}

		$this->__clearAddVariables();

		// FORM CHECK
		// ========================================
		// parse form
		if (arrayIsOk($check)) {
			$check = '[' . implode(',',$check) . ']';
		} else {
			$check='[]';	
		}	
		$parse_fields['JS_CHECK'] = $check;
		// ========================================		
		
		$tpl->parseVariable($parse_fields,$block_name);

	}
	
	// View Methods

	/**
	* @return void
	* @param array $arr
	* @param string $block_name
	* @desc parse $arr view
	*/
	function parseView($arr,$block_name='view') {
		GLOBAL $tpl;
		$this->__viewHandler($arr, $block_name, $tpl);
	}
	
	/**
	* @return string
	* @param array $arr
	* @param string $tpl_name
	* @param string $block_name
	* @desc make $arr view
	*/
	function makeView($arr,$tpl_name='system', $block_name='view') {

		// get system template name if $tpl_name isn't set
		if ($tpl_name=='system') {
			$tpl_name=$this->getSystemTemplate('view');
		}

		$this->loadTemplate($tpl_name);
		$this->__viewHandler($arr,$block_name, $this->tpl);
		return $this->tpl->get();
	}
	
	/**
	* @return void
	* @param array $arr
	* @param string $block_name
	* @desc parse dynamic view
	*/
	function parseDynamicView($arr, $block_name='dynamic_view') {
		GLOBAL $tpl;
		$this->__dynamicViewHandler($arr, $block_name, $tpl);
	}
	
	/**
	* @return string
	* @param array $arr
	* @param string $tpl_name
	* @param string $block_name
	* @desc make dynamic view
	*/
	function makeDynamicView($arr, $tpl_name='system', $block_name='dynamic_view') {
		// get system template name if $tpl_name isn't set
		if ($tpl_name == 'system') {
			$tpl_name = $this->getSystemTemplate('dynamic_view');
		}

		$this->loadTemplate($tpl_name);
		$this->__dynamicViewHandler($arr, $block_name, $this->tpl);
		return $this->tpl->get();
	}
	
	/**
	* @return void
	* @param array $arr
	* @param string $block_name
	* @param object &$tpl
	* @desc parse dynamic view data
	*/
	function __dynamicViewHandler($arr, $block_name, &$tpl) {
		$this->buffer = 'dynamic_view';

		if (!arrayIsOk($arr)) {
			return '';
		}

		$row_name = $block_name.'_row';

		// add images fields if needs
		if (arrayIsOk($this->fields)) {
			foreach ($this->fields as $name=>$type) {
				if (substr($type,0,5)=='image' && $arr['id']!='') {
					$arr[$name]=$arr['id'];
				}
				if (substr($type,0,4)=='file' && $arr['id']!='') {
					$arr[$name]=$arr['id'].'_'.$arr[$name];
				}
			}
		}

		// parse view row
		$tpl->setCurrentBlock($row_name);
		foreach ($arr as $key => $value) {
			if (isset($this->fields[$key])) {
				// do not show hidden fields
				if ($this->fields[$key]=='hidden') {
					continue;
				}
				$caption = $this->fields_name[$key];
				$this->setElement($key, $this->fields[$key]);
				$value = $this->makeElementView($value);
//				if ($this->element_type=='image') {
//					$value=$value['IMAGE_'.strtoupper($this->element_type_info).'_SYSTEM'];
//				}
			} else {
				// formatirovat' nado
				$caption = $key;
			}

			$tpl->setVariable(array(
				'CAPTION'       => $caption,
				'FIELD'         => $value
			));
			$tpl->parseCurrentBlock();
		}

		// parse view
		$tpl->setCurrentBlock($block_name);
		$tpl->parseCurrentBlock();

		// remove fields info
		$this->__clearFieldsInfo();
		$this->buffer = '';
	}
	
	/**
	* @return void
	* @param array $arr
	* @param string $block_name
	* @param object &$tpl
	* @desc parse view data
	*/
	function __viewHandler($arr, $block_name, &$tpl) {
		// get add variables
		$add = $this->__getAddVariables();
		$arr = array_merge($arr, $add);
		
		$caption = $this->__getCaptionVariables();
		$arr = array_merge($arr, $caption);
		
		$tpl->setCurrentBlock($block_name);

		foreach ($arr as $key => $value) {
			if (isset($this->fields[$key])) {
				if (substr($this->fields[$key],0,5)=='file_') {
					$this->setElement($key, $this->fields[$key]);
					$value = $this->makeElementView($arr['id'].'_'.$arr[$key]);
				} else {				
					$this->setElement($key, $this->fields[$key]);
					$value = $this->makeElementView($value);
				}
			}

			if (is_array($value)) {
				$tpl->setVariable($value);
			} else {
				$tpl->setVariable(array(
					strtoupper($key)	=> $value
				));
			}
		}

		// So es Image field has no record in DB we have to add it manually
		if (arrayIsOk($this->fields)) {
			foreach ($this->fields as $key=>$type) {
				if (substr($type,0,6) == 'image_') {
					$this->setElement($key, $type);
					$value = $this->makeElementView($arr['id']);
					$tpl->setVariable($value);
				}
				if (substr($type,0,5)=='file_') {
					$this->setElement($key, $type);
					$value = $this->makeElementView($arr['id'].'_'.$arr[$key]);
					$tpl->setVariable($value);
				}
			}
		}

		$tpl->parseCurrentBlock();

		// remove caption & add variables
		$this->__clearAddVariables();

		// remove fields info
		$this->__clearFieldsInfo();

	}
	
	/**
	* @return string
	* @param mixed $value
	* @desc make element view
	*/
	function makeElementView($value=null) {
	
		eval('$object = new T'.$this->element_type.'();');

		return $object->makeElementView($value);

	}

	//========================= LIST METHODS ==============================
	
	function setListDecoration($list_decoration1, $list_decoration2){
	
		$this->list_decoration1 = $list_decoration1;
		$this->list_decoration2 = $list_decoration2;		
	}
	
	function __clearListDecoration(){
		
		$this->list_decoration1 = '';
		$this->list_decoration2 = '';		
	}
	
	
	/**
	* @return string
	* @param array $arr
	* @param string $tpl_name
	* @param string $block_name
	* @desc make list
	*/
	function makeList($arr, $tpl_name='system', $block_name='list') {

		// get system template name if $tpl_name isn't set
		if ($tpl_name=='system') {
			$tpl_name=$this->getSystemTemplate('list');
		}
	
		$this->loadTemplate($tpl_name);
		$this->__listHandler($arr,$block_name, $this->tpl);
		return $this->tpl->get();
	}
	
	/**
	* @return void
	* @param array $arr
	* @param string $block_name
	* @desc parse list
	*/
	function parseList($arr, $block_name='list') {
		GLOBAL $tpl;
		$this->__listHandler($arr, $block_name, $tpl);
	}
	
	/**
	* @return string
	* @param array $arr
	* @param string $tpl_name
	* @param string $block_name
	* @desc make dynamic list
	*/
	function makeDynamicList($arr, $tpl_name='system', $block_name='dynamic_list') {

		// get system template name if $tpl_name isn't set
		if ($tpl_name=='system') {
			$tpl_name=$this->getSystemTemplate('dynamic_list');
		}
		$this->loadTemplate($tpl_name);
		$this->__dynamicListHandler($arr,$block_name, $this->tpl);
		return $this->tpl->get();
	}
	
	/**
	* @return void
	* @param array $arr
	* @param string $block_name
	* @desc parse dynamic list
	*/
	function parseDynamicList($arr, $block_name='dynamic_list') {
		GLOBAL $tpl;
		$this->__dynamicListHandler($arr,$block_name, $tpl);
	}


	/**
	* @return void
	* @param array  $arr - hash^2 of values
	* @param string $block_name - block name
	* @param Template $tpl - link to TPL object ($app->tpl OR $parser->tpl) that will be used for parsing
	* @desc List handler
	*/
	function __listHandler($arr, $block_name, &$tpl) {
		GLOBAL $app;

		// setup template defenitions
		$list_name = $block_name;
		$row_name = $block_name.'_row';
		$empty_name = $block_name.'_empty';
		$separator_name = $block_name.'_separator';

		$add = $this->__getAddVariables();

		if (isset($arr) && is_array($arr) && sizeof($arr)>0) {
			$list_size=sizeof($arr);
			$i=1;
			foreach ($arr as $row) {
				// make separator
				if ($i<$list_size) {
					$tpl->touchBlock($separator_name);
				}

				$tpl->setCurrentBlock($row_name);
				// add $this->add_variables
				$row = array_merge($row, $add);

				// add images fields if needs
				if (arrayIsOk($this->fields)) {
					foreach ($this->fields as $name=>$type) {
						if (substr($type,0,5)=='image' && $row['id']!='') {
							$row[$name]=$row['id'];
						}
						if (substr($type,0,4)=='file' && $row['id']!='') {
							$row[$name]=$row['id'].'_'.$row[$name];
						}
					}
				}

				foreach ($row as $key=>$value) {
					// make decoration
					if($this->list_decoration1!=''&&$this->list_decoration2!='') {
						if($i%2 == 1){
							$tpl->setVariable('decoration',$this->list_decoration1);
						} else {
							$tpl->setVariable('decoration',$this->list_decoration2);	
						}	
					}
					// preobrazovanie
					if (isset($this->fields[$key]) && $this->fields[$key] != '') {
						$this->setElement($key, $this->fields[$key]);
						$value = $this->makeElementView($value, $row['id']);
					}
					if (!arrayIsOk($value)) {
						$tpl->setVariable(array(
							strtoupper($key)	=> $value
						));
					} else {
						$tpl->setVariable($value);
					}
				}
				$tpl->parseCurrentBlock();
				$i++;
			}
		} else {
			$tpl->touchBlock($empty_name);
		}

		$tpl->setCurrentBlock($list_name);
		if (isset($this->caption_variables)) {
			$tpl->setVariable($this->caption_variables);
		}

		if ($app->paging && $app->paging['pages']>1) {			
			$tpl->setVariable(array(
				'PAGING'	=> $this->makePaging()
			));
		}

		$tpl->parseCurrentBlock();
		
		// remove caption & add variables
		$this->__clearAddVariables();
		$this->__clearListDecoration();
	}

	/**
	* @return void
	* @param array  $arr - hash^2 of values
	* @param string $block_name - block name
	* @param Template $tpl - link to TPL object ($app->tpl OR $parser->tpl) that will be used for parsing
	* @desc parse dynamic list data
	*/
	function __dynamicListHandler($arr, $block_name, &$tpl) {

		GLOBAL $app;
		
		$this->buffer = 'dynamic_view';

		$list_name = $block_name;
		$row_name = $block_name.'_row';
		$element_name = $block_name.'_element';

		$caption_row_name = $block_name.'_caption_row';
		$caption_element_name = $block_name.'_caption_element';

		$add = $this->__getAddVariables();

		// make captions line
		if (arrayIsOk($this->list_columns)) {
			foreach ($this->list_columns as $key=>$value) {
				if (arrayIsOk($value)) {
					foreach ($value as $key2=>$value2) {
						$cap_arr[$key2]=$value2;
					}
					$cap_arr['ELEMENT_NAME'] = $key;
				} else {
					$cap_arr = array(
						'ELEMENT'		=> $value,
						'ELEMENT_NAME'	=> $key
					);
				}
				$tpl->parseVariable($cap_arr,$caption_element_name);
			}

			$tpl->setCurrentBlock($caption_row_name);

			if (arrayIsOk($this->list_columns_add_variables)) {
				$tpl->setVariable($this->list_columns_add_variables);
				unset($this->list_columns_add_variables);
			}

			$tpl->parseCurrentBlock();
		}

		if (arrayIsOk($this->fields)) {
			$i = 1;
			foreach ($arr as $row) {
				foreach ($row as $key=>$value) {
					// make decoration
					if($this->list_decoration1!=''&&$this->list_decoration2!='') {
						if($i%2 == 1){
							$tpl->setVariable('decoration',$this->list_decoration1);
						} else {
							$tpl->setVariable('decoration',$this->list_decoration2);	
						}	
					}
					if ($this->fields[$key] != '') {
						// DATA field
						if ($this->fields[$key] != 'hidden' && $key!='id') {
							$this->setElement($key, $this->fields[$key]);
							$value = $this->makeElementView($value, $row['id']);

							$insert_array = array(
								'ELEMENT'	=> $value,
								'ID'		=> $row['id']
							);
							$tpl->parseVariable($insert_array,$element_name);
						}
					} else {
						// not DATA field

						if ($key!='id') {
							$tpl->parseVariable(array('ELEMENT'	=> $value),$element_name);
						}
					}
					
				}
				$row = array_merge($row, $add);
				$tpl->parseVariable($row,$row_name);
				$i++;
			}
		} else {
			$i=1;
			foreach ($arr as $row) {
				foreach ($row as $key=>$value) {
					// make decoration
					if($this->list_decoration1!=''&&$this->list_decoration2!='') {
						if($i%2 == 1){
							$tpl->setVariable('decoration',$this->list_decoration1);
						} else {
							$tpl->setVariable('decoration',$this->list_decoration2);	
						}	
					}	
					$tpl->parseVariable(array('ELEMENT'	=> $value),$element_name);
				}
				$row = array_merge($row, $add);
				$tpl->parseVariable($row,$row_name);
			$i++;
			}
		}


		$tpl->setCurrentBlock($list_name);
		if (isset($this->caption_variables)) {
			$tpl->setVariable($this->caption_variables);
		}

		// Paging
		if ($app->paging) {
			$tpl->setVariable(array(
				'PAGING'	=> $this->makePaging()
			));
		}

		$tpl->parseCurrentBlock();

		$this->__clearAddVariables();
		$this->__clearListDecoration();
		
		$this->buffer = '';
	}

	// ======================== TABLE METHODS =================================
	
	/**
	* @return string
	* @param array  $arr - hash^2 of values
	* @param int  $cols - number of columns
	* @param string $tpl_name
	* @param string $block_name
	* @desc make table
	*/
	function makeTable($arr, $cols, $tpl_name='system', $block_name='table') {

		// get system template name if $tpl_name isn't set
		if ($tpl_name=='system') {
			$tpl_name=$this->getSystemTemplate('table');
		}

		$this->loadTemplate($tpl_name);
		$this->__tableHandler($arr,$cols,$block_name, $this->tpl);
		return $this->tpl->get();
	}
	
	/**
	* @return void
	* @param array  $arr - hash^2 of values
	* @param int  $cols - number of columns
	* @param string $block_name
	* @desc parse table
	*/
	function parseTable($arr, $cols, $block_name='table') {
		GLOBAL $tpl;
		$this->__tableHandler($arr,$cols,$block_name, $tpl);
	}
	
	/**
	* @return void
	* @param array  $arr - hash^2 of values
	* @param int  $cols - number of columns
	* @param string $block_name
	* @param template &$tpl
	* @desc parse table data
	*/
	function __tableHandler($arr, $cols, $block_name='table', &$tpl) {
		$table_name=$block_name;
		$column_separator_left_name=$block_name.'_column_separator_left';
		$column_separator_right_name=$block_name.'_column_separator_right';
		$column_separator_name = $block_name.'_column_separator';
		$column_name = $block_name.'_column';
		$row_separator_name = $block_name.'_row_separator';
		$row_name = $block_name.'_row';
		$empty_name = $block_name.'_empty';
		$empty_separator_name = $block_name.'_empty_separator';


		$size = sizeof($arr); // size of array
		$rows = round($size/$cols); // number of rows
		if ($rows*$cols < $size) $rows++;

		$i = 0; // current element
		$c = 0; // current column
		$r = 0; // current row

		$add = $this->__getAddVariables(); // get add variables

		if (is_array($arr)) foreach ($arr as $row) {

			// new row begining
			if ($c == 0) $tpl->touchBlock($column_separator_left_name);

			// make column separator
			if ($c != 0) $tpl->touchBlock($column_separator_name);

			// parse current column variables
			$tpl->setCurrentBlock($column_name);
			$row = array_merge($row, $add);

			// add images fields if needs
			if (arrayIsOk($this->fields)) {
				foreach ($this->fields as $name=>$type) {
					if (substr($type,0,5)=='image' && $row['id']!='') {
						$row[$name]=$row['id'];
					}
					if (substr($type,0,4)=='file' && $row['id']!='') {
							$row[$name]=$row['id'].'_'.$row[$name];
					}	
				}
			}

			foreach ($row as $key=>$value) {
				if ($this->fields[$key] != '') {
					$this->setElement($key, $this->fields[$key]);
					$value = $this->makeElementView($value, $row['id']);
				}

					if (!arrayIsOk($value)) {
						$tpl->setVariable(array(
							strtoupper($key)	=> $value
						));
					} else {
						$tpl->setVariable($value);
					}
			}
			$tpl->parseCurrentBlock();

			$c++; // column parsed - new column

			// row ending
			if ($c == $cols) $tpl->touchBlock($column_separator_right_name);

			// row separator processing
			if (++$i%$cols == 0) {
				if (sizeof($arr) - $i > 0) {
					$tpl->touchBlock($row_separator_name);
				}
				$tpl->setCurrentBlock($row_name);
				$tpl->parseCurrentBlock();
				$c = 0;
				$r++;
			}
		} else {
//			$tpl->touchBlock($empty_name);
		}

		// empty columns parsing
		if ($i%$cols != 0) {
			while ($i++%$cols != 0) {

				// empty column separator
				if ($c != 0) $tpl->touchBlock($empty_separator_name);

				// empty column
				$tpl->touchBlock($empty_name);

				// new column
				$c++;
			}

			// table column separator right
			$tpl->touchBlock($column_separator_right_name);

			// make row
			$tpl->setCurrentBlock($row_name);
			$tpl->parseCurrentBlock();
		}

		// parse main block table & add caption variables
		$tpl->setCurrentBlock($table_name);
		if (isset($this->caption_variables)) {
			$tpl->setVariable($this->caption_variables);
		}
		$tpl->parseCurrentBlock();

		// remove caption & add variables
		$this->__clearAddVariables();
	}
	
	// DIFFERENT
	
	/**
	* @return string
	* @desc make paging
	*/
	function makePaging() {
		
		GLOBAL $app;
		// check paging
		
		if ($app->paging===false) {
			return '';
		}		
		
		$tpl = new template(TPL_PATH);		
		if (!isset($app->paging['template'])) {
			$tpl_name=$this->getSystemTemplate('paging'.$app->paging['type']);
		} else {
			$tpl_name = $app->paging['template'];
		}		
		
		$tpl->loadTemplatefile($tpl_name);
		
		//$app->load('paging1','inc');
		include SYS_ROOT.INIT_PATH.INC_PATH.'paging'.$app->paging['type'].'.inc.php';
		return $tpl->get();

		$app->clearPaging();

	}
	
	function makeNavigation($rows,$tpl_name,$block_name='navigation') {
		$total=sizeof($rows);
		$last=$rows[$total-1];
		unset($rows[$total-1]);

		$this->setCaption(array(
			'name'	=> $last['name']
		));
			
		return $this->makeList($rows,$tpl_name,$block_name);
	}	

} // end class Parser


$GLOBALS['parser'] = $parser = new parser();
?>