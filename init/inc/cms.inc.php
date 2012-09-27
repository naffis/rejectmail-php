<?
// EDIT MODULE
function cmsEditSubmit($cond='', $back) {
	GLOBAL $data;
	GLOBAL $app;
	
	$values = array();

	foreach ($_POST as $key=>$val) {
		// T.k. v _POST mogut bit vlogennie massivi (type = listbox), to
		if (arrayIsOk($val)) {
			$values[$key] = stripslashes($key);
			foreach($val as $in_key => $in_val) {
				$values[$key.'_'.$in_val] = stripslashes($in_val);
			}
		}
		else {
			$values[$key] = stripslashes($val);
		}
	}
	$values['id']=$_POST['id'];
	$data->update($cond, $values);
	$app->jumpBack($back);
}


function cmsEdit($values,$get_array) {
	GLOBAL $data;
	
	// get HIDDEN values
	$params = array_merge($get_array);
	foreach ($params as $param=>$value) {
		if (preg_match("/^HIDDEN_(.+)$/",$param, $m)) {
			$values[$m[1]] = $value;
		}
	}
		
	return $data->makeForm($values);
	
}


class CmsTree {

	var $name, 	 			// tree name
		$node,				// node data array
		$node_name,
		$node_list_fields,
		$point,				// point data array
		$point_name,
		$point_list_fields,
		$tpl_name,
		$cl,				// Current Level
		$node_parents,		// curent node parents 
		$nesting_level,		// Nesting level for $cl
		$cl_mode,			// current level mode
							// 0 = empty 1 = nodes 2 = points 3 = both
		$max_level,			// mex nesting level					
		$node_first_field,	// node first list field
		$paging;			// paging handler


	function CmsTree($name) {
		GLOBAL $db;
		
		$this->name=$name;
		
		// Name tree array from conf file
		$data_tree = 'TREE_'.strtoupper($_SESSION['tree_name']);
		$tree = $GLOBALS[$data_tree];
		
		$this->node_name = $tree['node'];
		$this->point_name = $tree['point'];
		
		if($tree['tpl_name'] == ''){
			$this->tpl_name = 'cms/tree';
		} else {
			$this->tpl_name = $tree['tpl_name'];	
		}
		
		$this->max_level=$tree['max_level'];
		
		// get tree, node, point arrays
		$data_node_name = 'DATA_'.strtoupper($tree['node']);
		$this->node = $GLOBALS[$data_node_name];
		$data_point_name = 'DATA_'.strtoupper($tree['point']);
		$this->point = $GLOBALS[$data_point_name];
		
		// set Current Level if !exists.  Attention !!! Current Level = id !!! $cl = 1 - id ROOT'a
		if (!isset($_GET['cl']) || $_GET['cl'] < 1) {
			$this->cl = 1;
		} else {
			$this->cl = $_GET['cl'];
		}
		
		// get is cuurent level point or node (cl_mode)
		$is_node = $db->get("parent_id = $this->cl", 'id', $this->node['table']);
		$is_point = $db->get("parent_id = $this->cl", 'id', $this->point['table']);	
		if (!empty($is_node) && !empty($is_point)) {
			$this->cl_mode=3;	
		} elseif (!empty($is_node)) {
			$this->cl_mode=1;	
		} elseif (!empty($is_point)) {
			$this->cl_mode=2;	
		} else {
			$this->cl_mode=0;
		}	
		
		// get nesting level (vlozennost') for current $cl
		$this->node_parents = $db->get("id = ".$this->cl, 'parents', $this->node[table]); 
		$this->nesting_level = substr_count($this->node_parents, '>');	
		
		// set list fields
		$this->node_list_fields = (isset($this->node['list_fields']))?$this->node['list_fields']:'name';
		$this->point_list_fields = (isset($this->point['list_fields']))?$this->point['list_fields']:'name';

		// get node first list field (for jump, locations.. etc.)
		if (isset($this->node['list_fields'])) {
			$columns_array = explode(',', $this->node['list_fields']);
			$this->node_first_field = $columns_array[0];
		} else {
			$this->node_first_field = 'name';	
		}	
	}
	
	function nodeSort($parent, $rows, $cl, $first_node_field_name) {
		STATIC $rez;
		if (arrayIsOk($rows)) {
			foreach($rows as $row) {
				if ($row['parent_id']==$parent) {
					$rez[$row['id']] = str_repeat("&nbsp;",substr_count($row['parents'],">")*2).$row[$first_node_field_name];
					$this->nodeSort($row['id'], $rows, $cl, $first_node_field_name);
				}
			}
		}
		return $rez;
	}			

	function makeJumpField($params='') {
		GLOBAL $parser;
		GLOBAL $db;

		$rows = $db->select('', '', '', $this->node['table']);
		$arr = $this->nodeSort(0, $rows, $this->cl, $this->node_first_field);
		$parser->setFieldsGlobalParams($params);
		$parser->setElement('cl','list_arr',$arr);
		$jump = $parser->makeFormElement($this->cl);
		
		return $jump;
	}
	
	function makeLocation() {
		GLOBAL $db;
		
		$submenu = array();
		$submenu[] = 'Current location :';
		$parents = str_replace('<', '', $this->node_parents);
		$parents = explode('>', $parents);
		
		foreach ($parents as $row) {
			if (isset($row) && $row != '') {
				$sub_query .= $row.',';
			}
		}
		
		$sub_query = substr($sub_query, 0, -1);
		if ($sub_query != '') {
			$locnames = $db->query("SELECT $this->node_first_field FROM ".$this->node['table']. " WHERE id in ($sub_query)");
			$i = 0;
			foreach ($parents as $row) {
				if (isset($row) && $row != "") {
					$submenu["tree.php?cl=$row&back=$setback"] = $locnames[$i][$this->node_first_field];
				}
				$i++;
			}
		}
		$submenu['hidden'] = $db->get('id='.$this->cl,$this->node_first_field,$this->node['table']);
		
		return $submenu;
		
	}
	
	function deleteSelected($arr) {
		GLOBAL $data;
		
		if ($this->cl_mode==1) {
			$data->set($this->node_name);
		} else if ($this->cl_mode==2) {
			$data->set($this->point_name);
		}
		
		// eto tut vse dlia nodes poka !!!
		foreach ($arr as $key => $value) {
			if (substr($key, 0, 2) == "r_") {
				$id = substr($key, 2);
				//$db->delete("parents like '%".$id."%'", $node[table]);
				//$db->delete("parents like '%".$id."%'", $point[table]);
				$cond = "id=$id";
				$data->delete($cond);
			}		
		}
			

		
	}
	
} // end class CmsTree


function cmsAddSubmit($post_array,$back) {
	GLOBAL $data;
	GLOBAL $app;
	
	$values = array();
	foreach ($post_array as $key=>$val) {
		// T.k. v _POST mogut bit vlogennie massivi (type = listbox), to
		if (arrayIsOk($val)) {
			$values[$key] = stripslashes($key);
			foreach($val as $in_key => $in_val) {
				$values[$key.'_'.$in_val] = stripslashes($in_val);
			}
		}
		else {
			$values[$key] = stripslashes($val);
		}
	}
	$data->insert($values);
	$app->jumpBack($back);	
}

function cmsAdd($get_array) {
	GLOBAL $data;
	
	// looking for HIDDEN fields and it's values
	foreach ($get_array as $param=>$value) {
		if (preg_match("/^HIDDEN_(.+)$/",$param, $m)) {
			$values[$m[1]] = $value;
		}
	}
	
	return $data->makeForm($values);	
	
}

function cmsListSubmit($back) {
	GLOBAL $app;
	GLOBAL $data;
	
	foreach ($_POST as $key=>$value) {
		if (substr($key,0,2)=="r_") {
			$id=substr($key,2);
			$cond="id=$id";
			$data->delete($cond);
		}		
	}
	$app->jumpBack($back);	
}

function cmsList($columns,$order,$order_type,$cond,$tpl_name,$block_name='dynamic_list') {
	GLOBAL $data;
	GLOBAL $parser;
	GLOBAL $app;
	GLOBAL $db;
	
	GLOBAL $back;
	GLOBAL $setback;
	
	$type=$data->name;
	
	// if isset list fields
	if (isset($data->list_fields) && !isset($columns)) {
		$columns = $data->list_fields;	
	}
	
	// get columns as array
	if (isset($columns)) {
		$columns_array = explode(',', $columns);
	} else {
		$columns_array = array(1=>'name');	
	}
	
	// create select fields list
	$sql_select_fields='id';
	foreach ($columns_array as $field) {
		if (substr($data->fields[$field],0,5)!='image') {
			$sql_select_fields.=', '.$field;	
		} else {
			$sql_select_fields.=', id as '.$field;
			$image_field = $field;
		}		
	}	

	
	// get columns caption
	foreach ($columns_array as $field) {
		$sort_field= ($field!=$image_field)?$field:'id';
		if ($_GET['order']!=$field) {
			$columns_caption[$field]=array(
				'element'	=> $data->fields_name[$field],
				'sort'		=> 0,
				'sort_over'	=> 1,
				'sort_click'=> $app->addGetVariable(array(
					'order'			=> $sort_field,
					'order_type'	=> 1
				))
			);
		} else {
			
			$order_over = ($_GET['order_type']==1)?2:1;
			$order = intval($_GET['order_type']);
			
			$columns_caption[$field]=array(
				'element'	=> $data->fields_name[$field],
				'sort'		=> $order,
				'sort_over'	=> $order_over,
				'sort_click'=> $app->addGetVariable(array(
					'order'			=> $sort_field,
					'order_type'	=> $order_over
				))
			);		
		}	
	}
	
	
	
	// make DB select
	
	$query_order = ($_GET['order']!='')?$_GET['order']:$data->order;
	
	if ($query_order=='default') {
		$query_order=$data->order;
	}
	
	if ($_GET['order_type']==2) {
		$query_order=str_replace('DESC','',$query_order);
		$query_order.=' DESC';
	}
	
	$rows = $db->select($cond,$query_order,$sql_select_fields,$data->table);
	
	$data->setParserFields();
	// set add variables - type & back
	$parser->setAddVariables(array(
		'type'	=> $type,
		'back'	=> $setback
	));
	


	
	// make sort button for default (ORDER BY oder) sort
	if ($_GET['order_type']=='') $_GET['order_type']=1;
	
	if ($_GET['order']=='' || $_GET['order']=='default') {
		$order_over = ($_GET['order_type']==1)?2:1;	
	
		$def_sort = $_GET['order_type'];
		$def_sort_over = $order_over;
		$def_sort_click=$app->addGetVariable(array(
			'order'			=> 'default',
			'order_type'	=> $order_over
		));
	} else {
		$def_sort = 0;
		$def_sort_over = 1;
		$def_sort_click=$app->addGetVariable(array(
			'order'			=> 'default',
			'order_type'	=> 1
		));
	}
	
	$parser->list_columns_add_variables = array(
		'DEF_SORT'		=> $def_sort,
		'DEF_SORT_OVER'	=> $def_sort_over,
		'DEF_SORT_CLICK'=> $def_sort_click
	);
	
	$parser->setListColumns($columns_caption);
	
	if (isset($rows) && is_array($rows)) {
		return $parser->makeDynamicList($rows,$tpl_name,$block_name);
	}	
	
}

class CmsCrossList {
	
	var $name,
		$cross_field,
		$child,
		$parent;
			
			
	function CmsCrossList($name='') {
		
		if ($name!='') {
			$_GET['type']=$name;	
		}
		// set type to session
		if (isset($_GET['type'])) {
			$_SESSION['cross_list_name'] = $_GET['type'];
		}	
		
		$this->name=$_SESSION['cross_list_name'];
		
		$cross_list_name='CROSS_LIST_'.strtoupper($_SESSION['cross_list_name']);
		$cross_list=$GLOBALS[$cross_list_name];
		
		$this->parent=$cross_list['parent'];
		$this->child=$cross_list['child'];
		$this->cross_field=$cross_list['cross_field'];						
			
	}
	
	function makeView() {
		GLOBAL $data;
		GLOBAL $db;
		GLOBAL $parser;
		
		$data->set($this->parent);
		
		// Get first field name for parent list 
			// if isset list fields
			if (isset($data->list_fields)) {
				$columns = $data->list_fields;	
			}
			// get columns as array
			if (isset($columns)) {
				$columns_array = explode(',', $columns);
			} else {
				$columns_array = array(0=>'name');	
			}
			$parent_column=$columns_array[0];
		
		$parent_table = $data->table;
		
		$parent_list=cmsList($parent_column,$_GET['order'],$_GET['order_type'],$cond,'cms/cross_list', 'dynamic_list');
		
		$data->set($this->child);
		
		$child_table=$data->table;
		
		$parser->setCaption(array(
			'caption_name'	=> formatCaption($parent_column)
		));
		
		$query = "SELECT p.$parent_column as element, c.id, '".$this->parent."' as parent_type, '".$this->child."' as type, p.id as parent_id, 0 as back FROM $parent_table p, $child_table c WHERE p.id=c.$this->cross_field ORDER BY c.".$data->order;
		$rows = $db->query($query);
		
		$child_list = $parser->makeList($rows,'cms/cross_list','child_list');
		
		$parser->loadTemplate('cms/cross_list');
		
		$parser->tpl->parseVariable(array(
			'child_list'	=> $child_list,
			'parent_list'	=> $parent_list
		),'cross_list');
		
		return $parser->tpl->get();		
		
		
	}
	
	
	function addItem($back=0) {
		GLOBAL $db;
		GLOBAL $data;
		GLOBAL $app;
		
		$data->set($this->child);
		
		if (!$db->get($this->cross_field."=".$_GET['id'], 'id', $data->table)) {
			$insert_array = array(
				$this->cross_field	=> $_GET['id']
			);
			$data->insert($insert_array);
		} else {
			$app->setError('Error. This item already in database!');
		}
		
		$app->jumpBack($back);
		
	}
	
	function submit($back) {
		GLOBAL $app;
		GLOBAL $data;
		GLOBAL $db;
		
		$cross_field = $this->cross_field;
		$action=$_POST['subaction'];
		$data->set($this->child);
		
		/* add several items */
		if ($action=="add_items") {
			foreach ($_POST as $key => $name) {
				if (substr($key,0,2) == "r_") {
					$idd = substr($key, 2);
					$cond = "$cross_field='".$idd."'";
	
					if (!$db->get($cond, 'id', $data->table)) {
					
						$insert_array = array(
							$cross_field	=> $idd
						);
						$data->insert($insert_array);
					}
				}
			}
			$app->jumpBack($setback);
		}
	
		// Delete all selected hot items
		if ($action == "group_delete") {
			foreach ($_POST as $key => $value) {
				if (substr($key, 0, 2) == "r_") {
					$id = substr($key, 2);
					$cond = "id=$id";
					$data->delete($cond);
				}		
			}
			$app->jumpBack($setback);
		}
		
	}	
	
}

// redeveloped by War 07-06-2004
function cmsEditPaging($filename,$name,$back) {
	GLOBAL $parser;
	GLOBAL $app;

	$array_name = 'PAGING_'.strtoupper($name);

	if ($_POST['action'] == 'submit') {
		saveConfList($filename,$array_name,$_POST);
		$app->jumpBack($back);
	}
		
	$list_arr=loadConfList($filename,$array_name);
//	showArray($list_arr);

	$tpl_name=$parser->getSystemTemplate('form');
	$parser->loadTemplate($tpl_name);

	$tpl=$parser->tpl;
	foreach($list_arr as $key=>$row) {
		$name=$row['name'];
		if ($name=='type' || $name=='name') continue;
		$value=stripslashes($row['value']);
		$comment=$row['comment'];
		$caption=ucwords(str_replace('_',' ',$name));

		$parser->setElement($name,'input');

		$tpl->setCurrentBlock('form_row');
		$tpl->setVariable(array(
			'CAPTION'=>$caption,
			'FIELD'=>$parser->makeFormElement($value).BR.$comment
		));
		$tpl->parseCurrentBlock();
	}
	$tpl->setCurrentBlock('form');
	$tpl->parseCurrentBlock();

	$app->cp['page'].=$tpl->get();
	
	return true;
}

/*
//	$list_arr = loadConfList($filename,$array_name);
		
//		print_r($list_arr);
		
		$list_arr_type = $_SESSION['list_arr_'.$type];
		
		
		foreach($list_arr as $key=>$item) {
			if( $item['id'] == $_GET['id'] ) {
				$values['id'] 	= $item['id'];
				$values['name'] = $item['name'];
				$values['list_arr_item_key'] = $key;
					
			}
		}
		if( isset($list_arr_type) && is_array($list_arr_type) && count($list_arr_type)>0 ) {
			$new_list_arr_type = $_SESSION['list_arr_'.$type];
		} else {
			$new_list_arr_type['id'] 	= 'hidden';
			$new_list_arr_type['name'] 	= 'input'; 
		}
		$new_list_arr_type['list_arr_item_key'] 	= 'hidden';
		
		//$fields[] 0- visible edit, 1- hidden
		$fields = $parser->makeFormElements($new_list_arr_type ,$values);
		$app->cp[page].=$parser->makeForm($fields[0],$fields[1]);
		
	//defined constant related	
	} else {
		$defname = substr($type,4);
		if ($action == 'submit') {
			$cp->saveDefined($filename,$_POST[id],$_POST[value]);
			$app->jumpBack($back);
		}
		
		$def_arr = $cp->loadDefined($filename,$defname);
		if ($def_arr) {
			$fields=$parser->makeFormElements( array('id'=>'hidden', 'value'=>'input') , $def_arr);
			$app->cp[page].=$parser->makeForm($fields[0],$fields[1]);
		}

	}
	
}
*/

	// redeveloped by War 07-06-2004
	function loadConfList($filename,$array_name) {
		// $filename - name of conf file
		// $array_name - name of array in conf file (LIST_MONTHS for example);
		$file_path=ROOT_PATH.INIT_PATH.$filename;
		$handle=fopen($file_path,'r');
		$content=fread($handle, filesize($file_path));
		fclose($handle);
		if (preg_match('/\n\s*\$'.$array_name.'\s*=\s*array\s*\(\s*\n((.|\n)*?)\)\s*;\s*?\n/',$content,$matches)) {
			$arr_str=$matches[1];
//			echo nl2br($arr_str).BR;
			if (preg_match_all('/\s*([\'\"]?)(.+?)(?!\\\)\1\s*=>\s*([\'\"]?)(.+?)(?!\\\)\3(.*)\n/i',$arr_str,$matches,PREG_SET_ORDER)) {
//				showArray($matches);
				foreach ($matches as $key=>$match) 	if ($match[2]!='') {
					$return[$key]['name']=$match[2];
					$return[$key]['value']=stripslashes($match[4]);
					if (preg_match('/.*\/\/\s*([^\r]*)/',$match[5],$comment)) $return[$key]['comment']=$comment[1];
				}
				return $return;
			}
		}
		return false;
	}

	// redeveloped by War 07-06-2004
	function saveConfList($filename,$array_name,$values) {
		// $filename - name of conf file
		// $array_name - name of array in conf file (LIST_MONTHS for example);
		// $values - $_POST
		$file_path=ROOT_PATH.INIT_PATH.$filename;
		$handle=fopen($file_path,'r');
		$content=fread($handle, filesize($file_path));
		fclose($handle);
		
		$old_values=loadConfList($filename,$array_name);

		$array="\$$array_name = array (";
		if ($old_values) {
			$last=count($old_values)-1;
			foreach ($old_values as $key=>$value) {
				$array.="\r\n\t'".addslashes($value['name'])."' => '".(isset($values[$value['name']])?addslashes($values[$value['name']]):addslashes($value['value']))."'".($key==$last?"":",").(isset($value['comment'])?" // ".$value['comment']:"");
			}
		}
		$array.="\r\n);";

		$content=preg_replace('/(\s*)\$'.$array_name.'\s*=\s*array\s*\(\s*\n(.|\n)*?\)\s*;(\s*?\n)/','${1}'.$array.'${3}',$content);

		$handle=fopen($file_path,'w');
		$content=fwrite($handle, $content);
		fclose($handle);
		return true;
	}

function cmsCrossListSubmit($action, $back, $cross_field) {
	GLOBAL $app;
	GLOBAL $data;
	GLOBAL $db;

	/* add several items */
	if ($action=="add_items") {
		foreach ($_POST as $key => $name) {
			if (substr($key,0,2) == "r_") {
				$idd = substr($key, 2);
				$cond = "$cross_field='".$idd."'";

				if (!$db->get($cond, 'id', $data->table)) {

					$insert_array = array(
						$cross_field	=> $idd
					);
					$data->insert($insert_array);
				}
			}
		}
		$app->jumpBack($setback);
	}

	// Delete all selected hot items
	if ($action == "group_delete") {
		foreach ($_POST as $key => $value) {
			if (substr($key, 0, 2) == "r_") {
				$id = substr($key, 2);
				$cond = "id=$id";
				$data->delete($cond);
			}
		}
		$app->jumpBack($setback);
	}

}


?>