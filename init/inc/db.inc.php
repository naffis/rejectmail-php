<?

// TODO 
// createDump - parametr for drop table if exists !!!
// createDB - dobavit' proverku na exists DB
//
//
//
//
//

	define('DB_DATA_ONLY'  , 1);
	define('DB_STRUCT_ONLY', 2);
	define('DB_ALL'        , 3);

	define('DB_SQL_STRING'     , 1);
	define('DB_SQL_ARRAY'      , 2);
	define('DB_SQL_FILE'       , 3);


	//define('DB_SQL_CREATE_ONLY', 8);

	function createDump($options=DB_ALL, $return_type=DB_SQL_STRING, $tables='', $param='',$db_name='') {
		// $options - dump type
		//		1 - data only
		//		2 - structure only
		//		3 - data & structure
		// $return_type 
		//		1 - string
		//		2 - array
		//		3 - file
		// $tables - array of tables for dump (if empty - all tables)
		// $param - dumps' file name
		// $db_name - database name 
		
		GLOBAL $db;

		if ($db_name=='') $db_name=$db->dbname;

		$return=array();

		// if no tabels selected - creta list of tables
		if($tables=='') {
			$tables=array();
			$result=mysql_list_tables($db_name);
			while($row = mysql_fetch_array($result)) {
				$tables[]=$row[0];
			}
			mysql_free_result($result);
		}

		if(is_Array($tables) && count($tables)>0) {
			foreach($tables as $tbl) {
				$result=mysql_db_query($db_name,"SHOW CREATE TABLE $tbl"); 
				
				if (($options==DB_STRUCT_ONLY)||($options==DB_ALL)) {
					while($row = mysql_fetch_array($result)) {
						$return[]=$row[1].';';
					}
				}

				if (($options==DB_DATA_ONLY)||($options==DB_ALL)) {
					$result=mysql_db_query($db_name,"SELECT * FROM $tbl");
					$cont=array();
					
					while($row = mysql_fetch_array($result)) {
						$cont[]=$row;
					}
					
					if($cont) {
						foreach($cont as $row) {
							$k='(';
							$v='(';
							$f=0;
							if($row)
								foreach($row as $key=>$value)
									if($f)	
									{
										$v.="'$value', "; 
										$k.="$key, ";
										$f=0;
									} 
									else $f++;
							$v[strlen($v)-2]=')';
							$k[strlen($k)-2]=')';
							$return[]="INSERT INTO $tbl $k VALUES $v;";
						}
					}
				}  // data only
			} //for each tables
		} // is tables

		switch($return_type) {
			case DB_SQL_STRING:
				$ret_s='';
				foreach($return as $row)
					$ret_s.="$row\n";
				return $ret_s;
				break;
			case DB_SQL_ARRAY:
				return $return;
				break;
			case DB_SQL_FILE:
				if(!$param) {
					//ошибка если $param не задан
					echo 'Error in makeDump() : param not found';
					return false;
				}
				if(!($fd=fopen($param,'wb'))) 
				{
					echo 'Error in makeDump() : file not opened';
					return false;
				}
				foreach($return as $row)
					fputs($fd,"$row\n");
				fclose($fd);
				return true;
				break;
			default: 
				echo 'Error in makeDump() : return type is undefined or incorrect';
				return false;
		};
		return false;
	}
	
	function createDB($db_name) {
		// Create Databas
		// $db_name - new DB name 

		mysql_create_db($db_name);
		if(mysql_errno()) {	
			// DB not created
			echo 'Error in create_new_db() : db not created';
			return false;
		}
	}

	function insertDump($dump='',$file_name='',$db_name='') {
		// insert Dump
		// $dump - string or array that contain dump
		// if ($dump == '') beret'sia file name iz 
		// $file_name - dump file name
		// $db_name - DB name 
		// if ($db_name=='') using current DB ( $db->dbname )

		GLOBAL $db;

		if ($db_name=='') {
			$db_name=$db->dbname;
		}

		if ($dump=='' && $file_name=='') {
			echo 'Error. Both params can\'t be empty in insertDump()';
			return false;
		}

		if (isset($dump) && $dump!='') {
			//from $dump
			if (is_array($dump)) {
				// array
				foreach($dump as $sql) {
					mysql_db_query($db_name,$sql); 
					if(mysql_errno()) {
						echo 'Error in SQL exec';
						return false;
					}
				}
				return true;
			} else {
				// string
				mysql_db_query($db_name,$dump); 
				if(mysql_errno()) {
					echo 'Error in SQL exec';
					return false;
				}
				return true;			
			}
		} else {
			// from file 
			if(!($fd=fopen($file_name,'rb'))) {
				echo 'Error in insertDump : file not opened';
				return false;
			}

			while(!feof($fd)) {
				$str=fgets($fd);
				switch($str[0]) {
					case 'C':
						$qr=$str;
						while($str[0]!=')')
						{
							$str=fgets($fd);
							$qr.=$str;
						};
						$qr[strlen($qr)-2]='';
						mysql_db_query($db_name,$qr); 
						break;
					case 'I':
						$qr=$str;
						$qr[strlen($qr)-2]='';
						mysql_db_query($db_name,$qr); 
						break;
					default:;
				}
			}
			fclose($fd);
			return true;
		}
		return false;
	}

	/*******************************************************************************/
	/********************  Переносит часть одной базы в другую  ********************/
	/*******************************************************************************/
	/*  $options - возможность возможность скопировать/заменитьданные/и т.п        */
	/*  $src - что копируем, задаётся в виде:                                      */
	/*	  'db_name' => имя исходной базы данных                                    */
	/*	  'tables' => отображаемые таблицы, если не заданы то берутся все          */
	/*       't_name' => имя таблицы                                               */
	/*	     'filds' => имена полей, если не заданы то берутся все                 */
	/*  $dst - куда переносим задаётся в виде:                                     */
	/*	  'db_name' => имя результирующей базы данных                              */
	/*	  'tables' => отображаемые таблицы                                         */
	/*       't_name' => имя таблицы                                               */
	/*	     'filds' => имена полей                                                */
	/*    если какие-то из полей не заданы то беруться те же имена что и в $src    */
	/*******************************************************************************/
	function display($src,$dst,$options=DB_ALL) {
		foreach($src['tables'] as $tbl) {
			$this->setDBName($src['db_name']);
			$rows=$this->select('','','',$tbl);
			$this->setDBName($dst['db_name']);
			foreach($rows as $row) {
				$this->insert($row,$tbl);
			}
		}
	}
	




?>