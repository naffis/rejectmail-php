<?php
/*  
	$Header: /cvs_repository/lisk/engine/init/class/db.class.php,v 1.2 2005/02/10 17:06:28 andrew Exp $	

	CLASS DB 
    v. 3.0
    Wed Nov 17 13:46:31 EET 2004 - syntax fix 
*/
define('ERROR_NOTABLE',		'No table was selected');

class database {
	var $table, // current table name
		$error, // error

		$dbname, // current DB name
		
		$locked, // array of locked tables
		
		$limit,
		//???
		$paging;
		//???
	
	/**
	* @return void
	* @desc constructor
	*/
	function database() {
		$this->locked = array();
		$this->__connect();
		
		$this->paging = array(
			'id'			=> 0,
			'type'			=> 0,
			'page'			=> 0,
			'page_count'	=> 0,
			'offset'		=> 0,
			'limit'			=> 0
		);
		
	}
	
	/**
	* @return void
	* @param string $dbname
	* @desc connect to db
	*/
	//sys
	function __connect($dbname = SQL_DBNAME) {
		mysql_connect(SQL_HOST, SQL_USER, SQL_PASSWORD)
		or die ('Could not connect');
		$this->setDBName($dbname);
	}

	/**
	* @return void
	* @param string $sql
	* @desc Process DB error
	*/
	//sys
	function __processError($sql) {
		$this->__logging($sql);
		$this->error = array('message' => mysql_error());
	}
	
	/*function __addslashes($value){
		if (!get_magic_quotes_gpc()){
			if (is_array($value)) {
				foreach ($value as $key => $val) {
					$value[$key] = addslashes($val);
				}
			} else {
				$value = addslashes($value);
			}
		}	
		return addslashes($value);
	}*/
	
	/**
	* @return boolean
	* @param string $dbname
	* @desc Process DB error
	*/
	function setDBName($dbname) {
		GLOBAL $start_time;
		$res=mysql_select_db($dbname);
		$this->dbname=$dbname;

		// set SQL debug info
		$GLOBALS['SQLS'][] = array(
			'sql'	=> 'set database \''.$dbname.'\'',
			'time'	=> getmicrotime()-$start_time
		);

		// error processing
		if (!$res) {
			$this->__processError($sql);
			return false;
		} else {
			return true;
		}
	}

	/**
	* @return void
	* @param string $table
	* @desc Set current Table
	*/
	function setTable($table) {
		$this->table = $table;
	}
	
	/**
	* @return void
	* @param int $from
	* @param int $quantity
	* @desc set limit 
	*/
	//sys?
	/*function setLimit($from,$quantity) {
		$this->limit = array(
			0	=> $from,
			1	=> $quantity
		);	
	}*/
	
	/**
	* @return int
	* @param string $table
	* @desc get table next autoincrement
	*/
	function getAutoIncrement($table='') {
		if ($table=='') {
			$table = $this->table;
		}
		
		$rows = $this->query("SHOW TABLE STATUS LIKE '$table'");	
		return $rows[0]['Auto_increment'];
	}
	
	/**
	* @return boolean
	* @param mixed $tables
	* @desc lock tables
	*/
	//sys
	function __lock($tables) {
		$locked = $this->locked;
		if (!is_array($tables)) {
			$tables = explode(',', $tables);
		}
		if (is_array($tables)) {
			$table_list = '';
			foreach ($tables as $table) {
				if ($table_list != '') {
					$table_list = ',';
				}
				$table_list .= " $table WRITE";
				if (!in_array($table, $this->locked)) {
					$locked[] = $table;
				}
			}
		} else {
			$table_list = " $tables WRITE";
			if (!in_array($table, $this->locked)) {
				$locked[] = $tables;
			}
		}
		$sql = "LOCK TABLES $table_list";

		//execute and save time for debug
		$start_time = getmicrotime();
		$res = mysql_query($sql);
		$GLOBALS['SQLS'][] = array(
			'sql'	=> $sql,
			'time'	=> getmicrotime()-$start_time
		);

		// error processing
		if (!$res) {
			$this->__processError($sql);
			return false;
		}

		$this->locked = $locked;
		return true;
	}
	
	/**
	* @return boolean
	* @desc unlock tables
	*/
	//sys
	function __unlock() {
		if (isset($this->locked) && (sizeof($this->locked) == 0)) {
			return true;
		}
		$sql = 'UNLOCK TABLES';
		//execute and save time for debug
		$start_time = getmicrotime();
		$res = mysql_query($sql);
		$GLOBALS['SQLS'][] = array(
			'sql'	=> $sql,
			'time'	=> getmicrotime()-$start_time
		);

		// error processing
		if (!$res) {
			$this->__processError($sql);
			return false;
		}

		$this->locked = array();
		return true;
	}

	/**
	* @param string $sql
	* @desc store sql error for debug
	*/
	//sys
	function __logging($sql) {
		$GLOBALS['SQLS'][sizeof($GLOBALS['SQLS'])-1]['error'] = mysql_error();
	}
	
	/**
	* @param array $params
	* @param string $table
	* @desc insert record
	*/
	function insert($params, $table='') {
		//select table
		if ($table == '') {
			if ($this->table == '') {
				//error TABLE NOT SELECTED
				$this->error = array('message' => ERROR_NOTABLE);
				return false;
			}
			$table = $this->table;
		}
		
		// lock table
		if (!in_array($table, $this->locked)) {
			$this->__lock($table);
		}

		// build sql
		$sql = "insert into $table set ";
		foreach ($params as $field=>$value) {
			$quotes=true;
			// do not take $value in ' ' if it's sql
			if (substr($value,0,4)=='sql:') {
				$quotes = false;
			}
			$value = addslashes($value);
			if ($quotes) {
				$sql .= "$field='$value',";
			} else {
				$value=substr($value,4);
				$sql .= "$field=$value,";
			}
		}
		$sql = substr($sql,0,-1);
	
		
		//$this->query
		//execute and save time for debug		
		$start_time = getmicrotime();
		//print $sql;	
		$res = mysql_query($sql);
		$GLOBALS['SQLS'][] = array(
			'sql'	=> $sql,
			'time'	=> getmicrotime()-$start_time
		);

		// error processing
		if (!$res) {
			$this->__processError($sql);
			$this->__unlock();
			return false;
		}

		$id = mysql_insert_id();
		$this->__unlock();
		return $id;
	}	

	/**
	* @param string $cond
	* @param array $params
	* @param string $table
	* @desc update records
	*/
	function update($cond, $params, $table='') {
		//select table
		if ($table == '') {
			if ($this->table == '') {
				$this->error = array('message' => ERROR_NOTABLE);
				return false;
			}
			$table = $this->table;
		}

		// lock table
		if (!in_array($table, $this->locked)) {
			$this->__lock($table);
		}

		//build sql
		$sql = "update $table set ";
		foreach ($params as $field=>$value) {
			$quotes=true;
			// do not take $value in ' ' if it's sql
			if (substr($value,0,4)=='sql:') {
				$quotes = false;
			}
			$value = addslashes($value);
			if ($quotes) {
				$sql .= "$field='$value',";
			} else {
				$value=substr($value,4);
				$sql .= "$field=$value,";
			}
		}

		$sql = substr($sql,0,-1);
		if ($cond != '') {
			$sql .= " where $cond";
		}
	
		//execute and save time for debug
		$start_time = getmicrotime();
		//print $sql;
		$res = mysql_query($sql);
		$GLOBALS['SQLS'][] = array(
			'sql'	=> $sql,
			'time'	=> getmicrotime()-$start_time
		);
		
		// error processing
		if (!$res) {
			$this->__processError($sql);
			$this->__unlock();
			return false;
		}

		$this->__unlock();
		return true;
	}

	/**
	* @param string $cond
	* @param string $table
	* @desc delete records
	*/	
	function delete($cond, $table='') {
		//select table
		if ($table == '') {
			if ($this->table == '') {
				$this->error = array('message' => ERROR_NOTABLE);
				return false;
			}
			$table = $this->table;
		}
		
		if (!in_array($table, $this->locked)) {
			$this->__lock($table);
		}

		//build sql
		$sql = "delete from $table";
		if ($cond != '') {
			if (is_array($cond)) {
				$sql .= ' where ';
				foreach ($cond as $field=>$value) {
					$value = addslashes($value);
					$sql .= "$field='$value' and ";
				}
				$sql = substr($sql,0,-5);
			} else {
				$sql .= " where $cond";
			}
		}
		
		//execute and save time for debug
		$start_time = getmicrotime();
		$res = mysql_query($sql);
		$GLOBALS['SQLS'][] = array(
			'sql'	=> $sql,
			'time'	=> getmicrotime()-$start_time
		);

		// error processing
		if (!$res) {
			$this->__processError($sql);
			$this->__unlock();
			return false;
		}

		$this->__unlock();
		return true;
	}
	
	/**
	* @return array
	*
	* @param string $cond
	* @param string $order
	* @param order fields
	* @param string $table
 	* @param mixed $nonstrip define variables which can't be stripped 	
	* @desc select records
	*/
	function select($cond='', $order='', $fields='', $table='',$nonfields = '') {
		
		GLOBAL $app;
		//define type of return value
		if ($fields == '') {
			$fields = '*';
			$return = array();
		} else {
			if (arrayIsOk($fields)) {
				$fields = implode(',', $fields);
				$return = array();
			} else {
				if (strpos($fields,',')!==false) {
					$return = array();
				} else {
					$return = '';
				}
			}
		}	

		//select table
		if ($table == '') {
			if ($this->table == '') {
				//error TABLE NOT SELECTED
				$this->error = array('message' => ERROR_NOTABLE);
				return false;
			}
			$table = $this->table;
		}
		
		
		$sql = "SELECT $fields FROM $table";
		
		// add cond to SQL 
		if ($cond != '') {
			if (arrayIsOk($cond)) {
				$sql .= " WHERE ";
				foreach ($cond as $field=>$value) {
					$value = addslashes($value);
					$sql .= "$field='$value' AND ";
				}
				$sql = substr($sql,0,strlen($sql)-5);
			} else {
				$sql .= " WHERE $cond";
			}
		}
		
		// add order to SQL		
		if ($order != '' ) {
			$sql .= " order by $order";
		}
		
		// add Paging
		//var_dump($app->paging);
		if (arrayIsOk($app->paging) && $app->paging['items_per_page'] != 0 && !isset($app->paging['queried'])) {
			
			$offset = 0; // SQL "LIMIT" from value 
			
			$items_per_page = $app->paging['items_per_page'];									
			$pcp = intval($_GET['pcp']);// $_GET[pcp] - paging current page
//			$items = $this->get($cond, 'count(id)', $table); // total number of items
			
			$items = $app->getPagingTotal() ? $app->getPagingTotal() : $this->get($cond, 'count(id)', $table); // total number of items			
			// get number of pages
			$pages = round($items/$items_per_page);						
			if ($pages*$items_per_page < $items) {
				$pages++;
			}
			if ($pcp >= $pages) {
				$pcp = $pages - 1;
			}
				
			if ($pages > 1) {
				$offset = $pcp*$items_per_page;
			}
				
			$app->paging['cur_page'] = $pcp;			
			$app->paging['pages']	= $pages;
			
			$app->paging['offset']= $offset;			
			$app->paging['items']	= $items;			
			$app->paging['queried']	= true;				
			$sql.=" LIMIT $offset, $items_per_page";			
		} elseif (isset($this->limit) && arrayIsOk($this->limit)) {
			$sql.=' LIMIT '.$this->limit[0].', '.$this->limit[1];
			unset($this->limit);			
		}
		
		//execute and save time for debug
		$start_time = getmicrotime();
		
		//print $sql;
		$res = mysql_query($sql);
		$GLOBALS['SQLS'][] = array(
			'sql'	=> $sql,
			'time'	=> getmicrotime()-$start_time
		);
		
		if (!$res) {
			//error MYSQL ERROR
			$this->__logging($sql);
			$this->error = array('message' => mysql_error());
			return false;
		}
		//var_dump(mysql_fetch_array($res, MYSQL_ASSOC));
		//fetch result
		if (is_array($return)) {						
			//
			while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {								
				$return[] = _stripslashes($row,$nonfields);
			}
			if (sizeof($return) == 0) {
				$return = false;
			}
		} else {
			//$row = mysql_fetch_array($res, MYSQL_ASSOC);
			//$return = $row[$fields];
			while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
				$return[] = _stripslashes($row[$fields],$nonfields);
			}
			if ($return == '') {
				$return = false;
			}
		}
		
		mysql_free_result($res);
		
		return $return;
	}
	
	/**
	* @return array or value 
	* @param string $cond
	* @param mixed $fields
	* @param string $table
	* @desc get oner row/value
	*/
	function get($cond='', $fields='', $table='',$nonstrip='') {
		//define type of return value
		
		if (($fields == '')or($fields == '*')) {
			$fields = '*';
			$return = array();
		} else {
			if (arrayIsOk($fields)) {
				$fields = implode(',', $fields);
				$return = array();
			} else {
				if (strpos($fields,',')!==false) {
					$return = array();
				} else {
					$return = '';
				}
			}
		}
		
		//select table
		if ($table == '') {
			if ($this->table == '') {
				//error TABLE NOT SELECTED
				$this->error = array('message' => ERROR_NOTABLE);
				return false;
			}
			$table = $this->table;
		}
		//
		//build sql		
		$sql = "select $fields from $table";		
		if ($cond != '') {
			if (is_array($cond)) {
				$sql .= " where ";
				foreach ($cond as $field=>$value) {
					$value = addslashes($value);
					$sql .= "$field='$value' and ";
				}
				$sql = substr($sql,0,strlen($sql)-5);
			} else {
				$sql .= " where $cond";
			}
		}
		//
		//execute and save time for debug
		$start_time = getmicrotime();
		//print $sql;
		
		$res = mysql_query($sql);	
		//print mysql_error();
		$GLOBALS['SQLS'][] = array(
			'sql'	=> $sql,
			'time'	=> getmicrotime()-$start_time
		);		
		//
		if (!$res) {
			
			//error MYSQL ERROR
			$this->__logging($sql);
			$this->error = array('message' => mysql_error());
			return false;
		}
		//fetch result
		
		if (is_array($return)) {			
			$return = _stripslashes(mysql_fetch_array($res, MYSQL_ASSOC),$nonstrip);
			if (!is_array($return)) {
				$return = false;
			}
		} else {
			
			$row = mysql_fetch_array($res, MYSQL_ASSOC);						
			// by War 07-12-2004
			if (preg_match('/,/',$fields)) $return = _stripslashes(@$row,$nonstrip);
			else {							
				$return = _stripslashes(@$row[$fields],$nonstrip);
			}					
			if ($return == '') {
				$return = false;
			}
		}
		//
		mysql_free_result($res);
		
		return $return;
	}
	
	/**
	* @return mixed
	* @param string $sql
	* @desc run custom SQL query
	*/
	
	function query($sql) {
		//execute and save time for debug
		$start_time = getmicrotime();
		
		$res = mysql_query($sql);
		$GLOBALS['SQLS'][] = array(
			'sql'	=> $sql,
			'time'	=> getmicrotime()-$start_time
		);
		//
		if (!$res) {
			//error MYSQL ERROR
			$this->__logging($sql);
			$this->error = array('message' => mysql_error());
			return false;
		}
		$sql_type = strtolower(substr(trim($sql),0,5));
		
		
		
		if ($sql_type=='inser') {
			$return = mysql_insert_id();
		}

		if ($sql_type=='selec' || $sql_type == 'show ') {
			//fetch result
			while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
				$return[] = _stripslashes($row);
			}
			if (sizeof($return) == 0) {
				$return = false;
			}
			//
			mysql_free_result($res);
		}
		return (isset($return))?$return:true;
	}
	
	/**
	* @return void
	* @desc disconnect from db
	*/
	function disconnect() {
		$this->__unlock();
		mysql_close();
	}
	

	/**
	* @return array
	* @param int $id
	* @param string $sql
	* @param int $page
	* @desc paging query
	*/
	function pagingQuery($id, $sql, $page=0) {
		$offset = 0;
		$row = $this->get("id='$id'", array('paging_type','list_limit'), 'paging');
		$type = $row['paging_type'];
		$limit = $row['list_limit'];
		
		if ($limit == 0) {
			return $this->query($sql);
		}
		
		$page = sprintf('%d', $page);
		
		preg_match("/^select\s.+\sfrom\s(.+?)(order\sby\s.+)*$/", strtolower($sql), $matches);
		$res = $this->query("select count(".$this->table.".id) as cnt from ".$matches[1]);
		if (sizeof($res) == 1) {
			$count = $res[0]['cnt'];
		} else {
			$count = sizeof($res);
		}
		
		$page_count = round($count/$limit);
		if ($page_count*$limit < $count) {
			$page_count++;
		}
		
		if ($page > $page_count) {
			$page = $page_count;
		}
		
		if ($page_count > 1) {
			$offset = $page*$limit;
		}
		
		$this->paging = array(
			'id'			=> $id,
			'type'			=> $type,
			'page'			=> $page,
			'page_count'	=> $page_count,
			'offset'		=> $offset,
			'limit'			=> $limit,
			'count'			=> $count
		);
		
//		echo "$sql limit $offset, $limit";
		return $this->query("$sql limit $offset, $limit");
	}
}

// Initializing $db class object
$GLOBALS['db'] = $db = new database();
?>