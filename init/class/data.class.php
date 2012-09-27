<?php
/*  
	$Header: /cvs_repository/lisk/engine/init/class/data.class.php,v 1.2 2005/02/10 14:13:41 andrew Exp $

	Class DATA
    v.3.0
    Wed Nov 17 13:39:37 EET 2004 - syntax fix
*/

class Data {
	var $name,				// current data name
		$table,				// current data DB table name
		$order,				// default ORDER BY ...

		$fields,			// array of fields (array name=>type)
		$fields_name,		// array of fields name
		$fields_params,		// array of fields params
		
		$check,				// array of fields checks

		$triggers,			// array of triggers associated with curent data

		$element_name,		// current element name
		$element_type,		// current element type
		$element_type_info,	// current element type info

		$list_fields,		// list fields - fields for CMS - list.php

		$buffer,			// object buffer - save any data

		$relations,			// array of relations with this data


		$zzz;


	/**
	* @return void
	* @desc Constructor
	*/
	function data() {


	}

	/**
	* @return void
	* @param string $name
	* @desc Set current data
	*/
	function set($name) {
		GLOBAL $app;
		$this->name = $name;

		$this->fields = array();
		$this->fields_name = array();
		$this->fields_params = array();

		//print 'DATA_'.strtoupper($name);
		$data = $GLOBALS['DATA_'.strtoupper($name)];
		//var_dump($data);
		// error if data $name if not exists
		if (!$data || !is_array($data)) {
			$app->raiseError("data <b>$name</b> is unknown");
		}

		$this->table = $data['table'];
		$this->order = $data['order'];

		// list fields
		if (isset($data['list_fields'])) {
			$this->list_fields = $data['list_fields'];
		}

		// get data fields info
		$fields=$data['fields'];

		$rez=array();
		foreach ($fields as $key=>$field) {
			if (arrayIsOk($field)) {
				// advanced field description

				// check is type isset
				if ($field['type']=='') $app->raiseError("Field type is undefined for data <b>$name</b> In data->set()");
				$rez[$key]=$field['type'];
				
				// get field name
				$fields_name[$key] = ($field['name']!='')?$field['name']:$key;

				foreach ($field as $param_key=>$value) {
					if ($param_key!='type' && $param_key!='name') {
						$this->fields_params[$key][$param_key]=$value;
					}
				}

			} else {
				// simple field description (just type)
				$rez[$key]=$field;
				$fields_name[$key]=$key;

			}
		}

		// set fields array
		$this->fields = $rez;

		// set fields names array
		$this->fields_name = $fields_name;

		// clear triggers array
		unset($this->triggers);
		// set triggers array
		if (isset($data['triggers']) && $data['triggers']!='') {
			$this->triggers = split(',',str_replace(' ','',$data['triggers']));
		}

		// relations
		if (isset($data['relations']) && arrayIsOk($data['relations'])) {
			$this->relations = $data['relations'];
		}
		
		// redefines ... 
		$this->reSet(INIT_NAME);

		// triggers initialization
		if (isset($this->triggers)) {
			GLOBAL $app;
			$app->load($this->name,'tger');
		}		
		// data debug
		$this->__debug('set',$name);

	}

	function reSet($name) {
		$name='redefine_'.$name;
		$data = $GLOBALS['DATA_'.strtoupper($this->name)];
		if (arrayIsOk($data[$name])) {
			foreach ($data[$name] as $key=>$field) {
				if (arrayIsOk($field)) {

					// check is type isset
					if ($field['type']=='') $app->raiseError("Field type is undefined for data <b>$name</b> In data->set()");
					$this->fields[$key]=$field['type'];

					// get field name
					$this->fields_name[$key] = ($field['name']!='')?$field['name']:$key;

					// clear old params
					$this->fields_params[$key]=array();
					
					// set new params
					foreach ($field as $param_key=>$value) {
						if ($param_key!='type' && $param_key!='name') {
							$this->fields_params[$key][$param_key]=$value;
						}
					}


				} else {
					// simple field description (just type)
					$this->fields[$key]=$field;
					$this->fields_name[$key]=$key;

				}
			}
		}
	}

	function setParserFields() {
		GLOBAL $parser;
		$parser->setFields($this->fields,$this->fields_name,$this->fields_params);
	}

	/**
	* @return void
	* @param string $name data name
	* @param string $type data type
	* @desc Set data element
	*/
	function setElement($name,$type) {
		GLOBAL $app;
		if (substr($type,0,6) == 'image_') {
			$type_info=substr($type,6);
			$type='image';
		} else
		if (substr($type,0,5) == 'file_') {
			$type_info=substr($type,5);
			$type='file';
		} else
		if (substr($type,0,5) == 'list_') {
			$type_info=substr($type,5);
			$type='list';
		} else
		if (substr($type,0,5) == 'prop_') {
			$type_info=substr($type,5);
			$type='prop';
		} else
		if (substr($type,0,8) == 'listbox_') {
			$type_info=substr($type,8);
			$type='listbox';
		} else
		if (substr($type,0,6) == 'radio_') {
			$type_info=substr($type,6);
			$type='radio';
		} else 
		if (substr($type,0,9) == 'category_') {
			$type_info=substr($type,9);
			$type='category';
		}

		$app->load($type,'type');

		$this->element_name = $name;
		$this->element_type = $type;
		$this->element_type_info = $type_info;
	}

	/**
	* @return void
	* @param unknown $action
	* @param unknown $params
	* @param unknown $rez
	* @desc Put debug information
	*/
	//sys
	function __debug($action,$params='',$rez='') {
		if (defined('DEBUG') && DEBUG==1) {
			$GLOBALS['DATA_DEBUG'][]=array(
				'action'	=> $action,
				'params'	=> $params,
				'rez'		=> $rez
			);
		}
	}

	/**
	* @return array 		Array of selected rows.
	* @param string $cond 	Condition for select
	* @param string $order 	Selection order
	* @param array $fields 	Array of fields to select
	* @desc Make SELECT with given condition and order for current data
	*/
	function select($cond='', $order='', $fields='') {
		GLOBAL $db;

		// set order
		if ($order == '') {
			$order = $this->order;
		}

		if (arrayIsOk($fields)) {
			foreach ($fields as $name=>$type) {
				$rez.=$name.',';
			}
			$fields = substr($rez,0,-1);
		}

		// set DB table
		$db->setTable($this->table);

		$select_fields = '';
		
		// add id to field list
		if ($fields != '') {
			$select_fields = explode(',',$fields);
			if (!in_array('id',$select_fields)) {
				$select_fields[]='id';
			}
		}

		// get DB select
		$rows = $db->select($cond, $order, $select_fields);

		return $rows;
	}

	/**
	* @return array 		Row of data
	* @param string $cond 	Condition for select
	* @param string $order 	Selection order
	* @desc Get one row for selected data.
	*/
	function get($cond, $fields='') {
		GLOBAL $db;
		GLOBAL $parser;

		$db->setTable($this->table);

		// add id to fields list
		$select_fields = '';
		if ($fields != '') {
			$fields = 'id, '.$fields;
			$select_fields = explode(',',$fields);
		}

		$row = $db->get($cond, $fields);

		return $row;
	}

	/**
	* @return boolean Result of trigger fuction execution. if there's no such trigger, then always return true.
	* @param string $name
	* @param string $cond
	* @param unknown $data
	* @desc Run specified trigger
	*/
	//sys
	function __runTrigger($name,$cond=null,$data=null) {
		if (is_array($this->triggers) && in_array($name,$this->triggers)) {
			$func_name=$name.'_'.$this->name;
			eval('$trigger_result='.$func_name.'($cond,$data);');
			if (!isset($trigger_result)) $trigger_result=true;
			return $trigger_result;
		} else {
			return true;
		}
	}

	/**
	* @return unknown
	* @param string $cond
	* @param array $values Array of new trigger values (field_name=>value)
	* @param unknown $flags
	* @desc Enter description here...
	*/
	function update($cond, $values, $flags='') {
		GLOBAL $db;
		$db->setTable($this->table);

		$update_data = array(); // array with data to SQL update
		$this->buffer=$cond;

		foreach ($this->fields as $name=>$type) {
			// skip ID field
			if ($name == 'id') continue;
			
			if($type=='category') {
				$update_data[$name] = $values[$name];
			} else {

				// HEZ zachem eto bilo... strannoo..
				// andrew skazal nado chto bi pustim ne zabivalo bazu... hez...
				$this->setElement($name,$type);
				if (!isset($values[$name])) continue;
	
				eval('$object = new T'.$this->element_type.'();');
				$rez = $object->update($values);
				if ($rez!==false) {
					$update_data[$name]=$rez;
				}
			}
		}

		// execute before update trigger
		$tger_rez = $this->__runTrigger('before_update',$cond,$update_data);

		// DB update
		if ($tger_rez===false) {
			return false;
		}
		
		foreach ($this->fields as $name=>$type) {
			if($type=='category') {
				$this->setElement($name,$type);
				if (!isset($values[$name])) continue;
	
				eval('$object = new T'.$this->element_type.'();');
				$rez = $object->update($values);
				if ($rez!==false) {
					$update_data[$name]=$rez;
				}
			}
		}
		
		if (isset($category_field) ) {
			eval('$object = new Tcategory()');
			$rez = $object->update($values);
			if ($rez!==false) {
				$update_data[$category_field] = $rez;
			}
		}

		$db->update($cond, $update_data);

		$this->__runTrigger('update',$cond,$update_data);
	}

	/**
	* @return int / boolean
	* @param array $values Array of new trigger values (field_name=>value)
	* @desc Insert into database
	*/
	function insert($values) {
		GLOBAL $db;

		$db->setTable($this->table);

		$insert_data = array(); // array with data to SQL insert

		foreach ($this->fields as $name=>$type) {
			$this->setElement($name,$type);
			if ($values[$name]=='' && $this->fields_params[$name]['def_value']!='') {
				$values[$name]=$this->fields_params[$name]['def_value'];
			}

			eval('$object = new T'.$this->element_type.'();');

			$rez=$object->insert($values);
			if ($rez!==false) {
				$insert_data[$name]=$rez;
			}
		}

		$rez = $this->__runTrigger('before_insert','',$values);

		if ($rez === false) {
			return false;
		}

		$id = $db->insert($insert_data);

		$this->__runTrigger('insert',$id,$insert_data);

		if (isset($filelist) && is_array($filelist)) {
			foreach ($filelist as $name=>$obj) {
				$filename = $this->saveFile($obj, $id, $name);
				if (isset($filename)) {
					$updates[$name] = $filename;
				}
			}
		}

		if (isset($updates) && is_array($updates)) {
			$db->update("id='$id'", $updates);
		}

		return $id;
	}
	
	/**
	* @return boolean
	* @param string $cond
	* @desc delete data
	*/
	function delete($cond) {
		GLOBAL $db;

		$values = $db->select($cond, '', '', $this->table);

		foreach ($this->fields as $name=>$type) {
			$this->setElement($name,$type);	
			
			if($this->element_type=='category') {
				continue;
			}
			
			eval('$object = new T'.$this->element_type.'();');
			$rez=$object->delete($values);
		}

		$rez = $this->__runTrigger('before_delete',$cond,$values);

		if ($rez === false) {
			return false;
		}
		

		foreach ($this->fields as $name=>$type) {
			$this->setElement($name,$type);	
			
			if ($this->element_type=='category') {
				eval('$object = new Tcategory();');
				$rez = $object->delete($values);
				if ($rez === false) {
					return false;
				}
			}
		}
		
		$db->delete($cond, $this->table);
		
		$this->__runTrigger('delete',$cond,$values);
		
	}

	/*************************** FORM METHODS ************************************/
	
	/**
	* @return string
	* @param array $values 
	* @param string $tpl_name 
	* @param string $block_name 
	* @desc make HTML form
	*/
	function makeForm($values = '', $tpl_name='system', $block_name='form') {
		GLOBAL $parser;

		if (!arrayIsOk($values) && $values!='') {
			// get values if condition
			$values = $this->get($values);
		}

		$this->setParserFields();

		return $parser->makeForm($this->fields, $values, $tpl_name, $block_name);
	}
	
	/**
	* @return void
	* @param array $values
	* @param string $block_name 
	* @desc parse HTML form
	*/
	function parseForm($values = '', $block_name='form') {
		GLOBAL $parser;

		if (!arrayIsOk($values) && $values!='') {
			// get values if condition
			$values = $this->get($values);
		}

		$this->setParserFields();

		$parser->parseForm($this->fields, $values, $block_name);
	}


	/**
	* @return string
	* @param array $values 
	* @param string $tpl_name 
	* @param string $block_name 
	* @desc make custom HTML form
	*/
	function makeCustomForm($values='', $tpl_name, $block_name='form') {
		GLOBAL $parser;

		if ($values!='' && !arrayIsOk($values)) {
			$values=$this->get($values);
		}

		// set fields_name in parser
		$this->setParserFields();
		return $parser->makeCustomForm($this->fields, $values, $tpl_name,  $block_name);
	}
	
	/**
	* @return string
	* @param array $values
	* @param string $block_name 
	* @desc parse custom HTML form
	*/
	function parseCustomForm($values='', $block_name='form') {
		GLOBAL $parser;

		if ($values!='' && !is_array($values)) {
			$values=$this->get($values);
		}

		// set fields in parser
		$this->setParserFields();
		$parser->parseCustomForm($this->fields, $values, $block_name);
	}
	
	/**
	* @return string
	* @param string $cond
	* @param string $fields 
	* @param string $tpl_name 
	* @param string $block_name 
	* @desc make data view
	*/
	function makeView($cond, $fields='', $tpl_name='system', $block_name='view') {
		GLOBAL $parser;
		$this->setParserFields();
		if(arrayIsOk($cond)) {
			$arr = $cond;
			$tpl_name = $fields;	
			$block_name = $tpl_name;
		} else {
			$arr = $this->get($cond, $fields);
		}
		if (isset($arr) && is_array($arr)) {
			return $parser->makeView($arr, $tpl_name, $block_name);
		}
	}
	
	/**
	* @return void
	* @param string $cond
	* @param string $fields
	* @param string $block_name 
	* @desc parse data view
	*/
	function parseView($cond, $fields='', $block_name='view') {
		GLOBAL $parser;
		$this->setParserFields();
		if(arrayIsOk($cond)) {
			$arr = $cond;
			$block_name = $fields;	
		} else {
			$arr = $this->get($cond, $fields);
		}
		if (isset($arr) && is_array($arr)) {
			$parser->parseView($arr, $block_name);
		}
	}
	
	/**
	* @return string
	* @param string $cond
	* @param mixed $fields 
	* @param string $tpl_name 
	* @param string $block_name 
	* @desc make data dynamic view
	*/
	function makeDynamicView($cond='', $fields='', $tpl_name='system', $block_name='dynamic_view') {
		GLOBAL $parser;
		$arr = $this->get($cond, $fields);
		$this->setParserFields();
		return $parser->makeDynamicView($arr, $tpl_name, $block_name);
	}
	
	/**
	* @return void
	* @param string $cond
	* @param mixed $fields
	* @param string $block_name 
	* @desc parse data dynamic view
	*/
	function parseDynamicView($cond='', $fields='', $block_name='dynamic_view') {
		GLOBAL $parser;
		$arr = $this->get($cond, $fields);
		$this->setParserFields();
		$parser->parseDynamicView($arr, $block_name);
	}
	
	/**
	* @return string
	* @param string $cond
	* @param string $tpl_name
	* @param string $block_name 
	* @desc make data list
	*/
	function makeList($cond='', $tpl_name='system', $block_name='list') {
		GLOBAL $db;
		GLOBAL $parser;

		$db->setTable($this->table);
		$rows=$this->select($cond);
		$this->setParserFields();

		return $parser->makeList($rows, $tpl_name, $block_name);
	}
	
	/**
	* @return void
	* @param string $cond
	* @param string $block_name 
	* @desc parse data list
	*/
	function parseList($cond='', $block_name='list') {
		GLOBAL $db;
		GLOBAL $parser;

		$db->setTable($this->table);
		$rows=$this->select($cond);

		$this->setParserFields();

		$parser->parseList($rows, $block_name);
	}
	
	/**
	* @return string
	* @param string $cond
	* @param array $fields 
	* @desc make data dynamic list
	*/
	function makeDynamicList($cond='', $fields=null) {
		GLOBAL $parser;
		if (!isset($fields)) {
			$fields = $this->fields;
		}
		$arr = $this->select($cond, $this->order, $fields);
		if (arrayIsOk($arr)) {
			$parser->setListColumns($this->fields_name);
			$this->setParserFields();

			return $parser->makeDynamicList($arr);
		}
	}
	
	/**
	* @return void
	* @param string $cond
	* @param int $cols 
	* @param string $block_name 
	* @desc parse data table
	*/
	function parseTable($cond='', $cols, $block_name='table') {
		GLOBAL $db;
		GLOBAL $parser;

		$db->setTable($this->table);
		$rows=$this->select($cond,$this->order);
		$this->setParserFields();
		$parser->parseTable($rows, $cols, $block_name);
	}

	function makeTable($cond='',$cols, $tpl_name='system', $block_name='table') {
		GLOBAL $db;
		GLOBAL $parser;
		
		$db->setTable($this->table);
		$rows=$this->select($cond);
		$this->setParserFields();
		
		return $parser->makeTable($rows, $cols, $tpl_name, $block_name);
	}

}

$GLOBALS['data'] = $data = new Data();
?>