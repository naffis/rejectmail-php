<?php
// Class category
// v 1.0

/*
TODO
    - makeListFormElement() don't allow to set parent to itself, how??
        in update() this is fixed
*/

class Tcategory {

    var $delimiter='>',
        $view_root=false,
        
        $chooseble = ' style="background-color:#FF4492" ', // nu tipa vot
        
        $object_type;   // 1 we work with node, 2 with point
	
	function update(&$values) {
		
		GLOBAL $parser;
		GLOBAL $db;
		GLOBAL $app;
		GLOBAL $data;

		$element_params = $data->fields_params[$data->element_name];
        
		$tree = $GLOBALS['TREE_'.strtoupper($data->element_type_info)];
		
		if (!arrayIsOk($tree)) {
		    $app->raiseError('Category type error. <b>TREE_'.strtoupper($parser->element_type_info).'</b> not found. Tcategory->update(values)');
		}
		
		$node = $tree['node'];
		$point = $tree['point'];
		
		if ($data->name==$node) {
            $this->object_type = 1;
		}
		if ($data->name==$point) {
            $this->object_type = 2;
		}
		
		$cross_field = (isset($element_params['cross_field']))?$element_params['cross_field']:'name';
		
		//get old record, to determine what was previous parent_id
		$previous = $db->get("id={$values['id']}",'',$data->table);
		
		if(arrayIsOk($previous)) {
		
    		$node_table = $GLOBALS['DATA_'.strtoupper($node)]['table'];
			$node_order = $GLOBALS['DATA_'.strtoupper($node)]['order'];
			$point_table= $GLOBALS['DATA_'.strtoupper($point)]['table'];

			// get array we need 
			$arr = $this->makeCategoryArray($node_table, $node_order, $point_table, $cross_field, 0, 0);
    		
    		$new_value = $previous['parent_id'];
    		
    		if ($this->object_type==1) { //node
                if($values['parent_id']>0 && $arr[$values['parent_id']]['points'] == 0 ) { 
                    
                    //don't allow to set parent to itself
                    if ($values['id'] != $values['parent_id']) {
                        $new_value = $values['parent_id'];
                    }
                }
                
    		} else { //point
                if($values['parent_id']>0 && $arr[$values['parent_id']]['subdirs'] == 0) {
                    $new_value = $values['parent_id'];
                }
    		}
    		
    		//update parents
    		$parents = $this->makeParentsStr($new_value, $arr);
    		
    		//set new parents into &$values !
    		$values['parents'] = $parents;
    		
    		$sql = "UPDATE {$data->table} SET parent_id=$new_value, parents='$parents' WHERE id={$values['id']}";
//    		echo $sql;
    		$db->query($sql);
    		
    		//array with updated record
    		$arr2 = $this->makeCategoryArray($node_table, $node_order, $point_table, $cross_field, 0, 0);
    		
    		if ($this->object_type==1) { //node
    			
	    		//update children parents
	    		$children = $this->getNodeChildren($previous['id'], $arr2 );
	    		if (arrayIsOk($children)) {
	    			foreach($children as $k=>$v) {
	    				
	    				$parents_children = $this->makeParentsStr($v['parent_id'], $arr2);
	    				//update nodes
	    				$sql = "UPDATE $node_table SET parents='$parents_children' WHERE parent_id={$v['parent_id']}";
	    				$db->query($sql);
	    				/*//update points, no need in case we have points only in deepest nodes
	    				$sql = "UPDATE $point_table SET parents='$parents_children' WHERE parent_id={$v['parent_id']}";
	    				$db->query($sql);*/
	    			}
	    		}
	    		
	    		//update  points parents
	    		$sql = "UPDATE $point_table SET parents='$parents<{$values['id']}>' WHERE parent_id={$values['id']}";
				$db->query($sql);
				
    		} else { //point
    			
    		}
    		
    		return $new_value;
		}
		
		exit('Tcategory->update() previous row error!');
	}
	
	function delete(&$values) {
		GLOBAL $parser;
		GLOBAL $db;
		GLOBAL $app;
		GLOBAL $data;
		
		$element_params = $data->fields_params[$data->element_name];
        
		$tree = $GLOBALS['TREE_'.strtoupper($data->element_type_info)];
		
		if (!arrayIsOk($tree)) {
		    $app->raiseError('Category type error. <b>TREE_'.strtoupper($parser->element_type_info).'</b> not found. Tcategory->delete(values)');
		}
		
		$node = $tree['node'];
		$point = $tree['point'];
		
		if ($data->name==$node) {
            $this->object_type = 1;
		}
		if ($data->name==$point) {
            $this->object_type = 2;
		}
		
		$cross_field = (isset($element_params['cross_field']))?$element_params['cross_field']:'name';
		$node_table = $GLOBALS['DATA_'.strtoupper($node)]['table'];
		$node_order = $GLOBALS['DATA_'.strtoupper($node)]['order'];
		$point_table= $GLOBALS['DATA_'.strtoupper($point)]['table'];
		
		if ($this->object_type==1 ) {
			if (arrayIsOk($values)) {
				$old_data = $data->name;
				
				foreach($values as $row_to_delete) {
					if(arrayIsOk($row_to_delete)) {
						$data->set($point);
						$data->delete("parents LIKE '%<{$row_to_delete['id']}>%'");
						$data->set($node);
						$data->delete("parents LIKE '%<{$row_to_delete['id']}>%'");
					}
					
				}
				
				$data->set($old_data);
				return true;
				
			} else {
				return false;
			}
		} else {
			//do nothing
		}
	}
	
	function makeParentsStr($start_from, $treeArr) {
		
		while ($start_from!=0) {
            $parents .= "<{$treeArr[$start_from]['id']}>";
            $start_from = $treeArr[$start_from]['parent_id'];
		}
		//convert parents into array, and reverse it to be in right order
		$new_parents = array_reverse(treeToArray($parents));
		
		//convert parents array into string
		$parents = '';
		if (arrayIsOk($new_parents)) {
            foreach($new_parents as $p) {
                $parents .= "<$p>";
            }
		}
		return $parents;
	}
	
	function getNodeChildren($id, $treeArr) {
		static $rez = array();
		foreach($treeArr as $k=>$v) {
			if ($treeArr[$k]['parent_id']==$id) {
				$rez[$k] = $v;
				$this->getNodeChildren($v['id'], $treeArr );
			}
		}
		return $rez;
	}
	
	function insert(&$values) {
	    
	    GLOBAL $parser;
		GLOBAL $db;
		GLOBAL $app;
		GLOBAL $data;

		$element_params = $data->fields_params[$data->element_name];
        
		$tree = $GLOBALS['TREE_'.strtoupper($data->element_type_info)];
		
		if (!arrayIsOk($tree)) {
		    $app->raiseError('Category type error. <b>TREE_'.strtoupper($parser->element_type_info).'</b> not found. Tcategory->insert(values)');
		}
		
		$node = $tree['node'];
		$point = $tree['point'];
		
		if ($data->name==$node) {
            $this->object_type = 1;
		}
		if ($data->name==$point) {
            $this->object_type = 2;
		}
		
		$cross_field = (isset($element_params['cross_field']))?$element_params['cross_field']:'name';
		
		$node_table = $GLOBALS['DATA_'.strtoupper($node)]['table'];
		$node_order = $GLOBALS['DATA_'.strtoupper($node)]['order'];
		$point_table= $GLOBALS['DATA_'.strtoupper($point)]['table'];

		// get array we need 
		$arr = $this->makeCategoryArray($node_table, $node_order, $point_table, $cross_field, 0, 0);

		//update parents
		
		$parents = '';
		$start_from = $values[$data->element_name];
		while ($start_from!=0) {
            $parents .= "<{$arr[$start_from]['id']}>";
            $start_from = $arr[$start_from]['parent_id'];
		}
		//convert parents into array, and reverse it to be in right order
		$new_parents = array_reverse(treeToArray($parents));
		
		//convert parents array into string
		$parents = '';
		if (arrayIsOk($new_parents)) {
            foreach($new_parents as $p) {
                $parents .= "<$p>";
            }
		}
		
		if ($values[$data->element_name]==0) {
			$app->raiseError('Tcategory->insert() '.$data->element_name.'='.$values[$data->element_name]);
		}
		
		//set new parents into &$values !
		$values['parents'] = $parents;
        
		return $values[$data->element_name];		
	}

	function makeListFormElement($arr, $value, $element_params=null) {
		GLOBAL $parser;
		$form_params = @$parser->element_params['form'].' '.$parser->fields_global_params;
		$parser->tpl->setCurrentBlock('form_list_row');
		foreach ($arr as $key=>$item) {
			if (is_array($item)) {
				$key=$item['id'];
				$name=$item['name'];
				$points=$item['points'];
				$subdirs=$item['subdirs'];
			}
			
			$chooseble=''; 
			if ($this->object_type==1) {
                // working with node structure    
			    if ($points==0 /*&& $item['id']!=$value*/) { //don't allow to set parent to itself how??
                    $chooseble=$this->chooseble; 
			    } else {
                    $key=0;
			    }
			} else {
			    // working with items
			    if ($subdirs!=0) {
                    $key=0;
			    } else {
                    $chooseble=$this->chooseble;   
			    }
			}
			
			$parser->tpl->setVariable(array(
			    'CHOOSEBLE' => $chooseble,
				'CAPTION'	=> $name,
				'VALUE'		=> $key,
				'SELECTED'	=> (strlen($value) && $key==$value)?' selected':null
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
		GLOBAL $data;

		$element_params = $parser->element_params;

		$tree = $GLOBALS['TREE_'.strtoupper($parser->element_type_info)];
		
		if (!arrayIsOk($tree)) {
		    $app->raiseError('Category type error. <b>TREE_'.strtoupper($parser->element_type_info).'</b> not found. In parser->makeFormElements()');
		}
		
		$node = $tree['node'];
		$point = $tree['point'];
		
		if ($data->name==$node) {
            $this->object_type = 1;
		}
		if ($data->name==$point) {
            $this->object_type = 2;
		}
		
		$cross_field = (isset($element_params['cross_field']))?$element_params['cross_field']:'name';
		
		$node_table = $GLOBALS['DATA_'.strtoupper($node)]['table'];
		$node_order = $GLOBALS['DATA_'.strtoupper($node)]['order'];
		$point_table= $GLOBALS['DATA_'.strtoupper($point)]['table'];

		// get array we need to go home (23.12.2204)
		$arr = $this->makeCategoryArray($node_table, $node_order, $point_table, $cross_field, 0, 1);
		
		if (arrayIsOk($arr)) { 
			// add additional values
			if (arrayIsOk(@$element_params['add_values'])) {
				$arr = array_merge_recursive($element_params['add_values'],$arr);
			}
			$this->makeListFormElement($arr, $value);
		}	
		
		
		
		return trim($parser->tpl->get());
	}
	
	function makeCategoryArray($node_table, $node_order, $point_table, $cross_field, $parent_id=0, $mode=0) {
	   GLOBAL $data;
	   GLOBAL $db;
	   
	   if ($mode == 1) {
	   	
	   	//point
		if ($this->object_type==2) {
			$sql = "
SELECT
    n.id,n.parent_id,n.parents,n.$cross_field, COUNT(n2.id) AS subdirs
FROM 
	$node_table as n
LEFT JOIN
	$node_table as n2 ON n2.parent_id=n.id
WHERE
	n.id>0	
GROUP BY
	n.id
ORDER BY
	n.$node_order";
		//node
		} else {
			$sql = "
SELECT
    n.id,n.parent_id,n.parents,n.$cross_field, COUNT(p1.id) AS points
FROM 
	$node_table as n 
LEFT JOIN
	$point_table as p1 ON p1.parent_id=n.id
WHERE
	n.id>0	
GROUP BY
	n.id
ORDER BY
	n.$node_order";
		}
	   	
	   } else {
			$sql = "
SELECT
    n.id,n.parent_id,n.parents,n.$cross_field, COUNT(p1.id) AS points, COUNT(n2.id) AS subdirs
FROM 
	$node_table as n 
LEFT JOIN
	$point_table as p1 ON p1.parent_id=n.id
LEFT JOIN
	$node_table as n2 ON n2.parent_id=n.id
WHERE
	n.id>0	
GROUP BY
	n.id
ORDER BY
	n.$node_order";
	   }
		
		
		$rows = $db->query($sql);
		$arr = $this->nodeSort($parent_id, $rows, $cross_field);
		
		return $arr;
	}
	
	function nodeSort($parent, $rows, $first_node_field_name) {
		STATIC $rez;
		foreach($rows as $row) {
			if ($row['parent_id']==$parent) {
			    $row['name'] = str_repeat("&nbsp;",substr_count($row['parents'],">")*2).$row[$first_node_field_name];
				$rez[$row['id']] = $row;
				$this->nodeSort($row['id'], $rows, $first_node_field_name);
			}
		}
		return $rez;
	}		
	
	

	
	function makeElementView($value) {
	    
		GLOBAL $parser;
		GLOBAL $db;
		GLOBAL $app;
		GLOBAL $data;

		$element_params = $parser->element_params;
		$tree = $GLOBALS['TREE_'.strtoupper($parser->element_type_info)];

		if (!arrayIsOk($tree)) {
		    $app->raiseError('Category type error. <b>TREE_'.strtoupper($parser->element_type_info).'</b> not found. In category.type.php->makeElementView()');
		}
		
		$cross_field = (isset($element_params['cross_field']))?$element_params['cross_field']:'name';
		
		$node = $tree['node'];
		$point = $tree['point'];
		
		$old_data = $data->name;
		$data->set($node);

        $parents = $db->get("id=$value",'parents',$data->table).'<'.$value.'>';
        $parents_ids = treeToArray($parents);
        
		$parent_in = implode(',', $parents_ids);
        $rows = $db->select("id IN ($parent_in)",'',"id,$cross_field", $data->table);
        
        //sort result by known parents
		$new_rows = array();
		$hash  = listToHash($rows, 'id', $cross_field);
        foreach($parents_ids as $v) {
        	if (isset($hash[$v])) {
        		$new_rows[$v] = $hash[$v];
        	}
        }

        if ($this->view_root===false) {
			reset($new_rows);
			unset($new_rows[key($new_rows)]); //unset first
        }
        
        //back from hash to array
        $rows = array_values($new_rows);
        
        $data->set($old_data);

        $rez=(arrayIsOk($rows))?implode($this->delimiter,$rows):'';
        return $rez;
	}
	
}

?>