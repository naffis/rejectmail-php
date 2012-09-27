<?php
/*
	$Header: /cvs_repository/lisk/engine/init/class/cp.class.php,v 1.2 2005/02/10 13:58:15 andrew Exp $
	
	CLASS CP control panel
	v. 3.0
*/

class ControlPanel {
	var $app,
		$db,
		$tpl;

	/**
	 * objects referencies init
	 *
	 * @param object <application> $app
	 * @return void
	 */
	function cp(&$app) {
		$this->db = &$app->db;
		$this->tpl = &$app->tpl;
		$this->app = &$app;
	}
	
	/**
	 * make cp menu1
	 *
	 * @param array $menu
	 * @return void
	 */
	function menu1($menu) {
		GLOBAL $app;

		$menu_tpl = new template(TPL_PATH);
		$menu_tpl->loadTemplateFile("cms/cp",true,true);
		if (is_array($menu)) {
			$did=0;
			$dids=sizeof($menu);
			foreach ($menu as $name=>$link) {
				if ($link == '') {
					$dids--;
					continue;
				}
				$did++;
				if ($did!=$dids) {
					$menu_tpl->setCurrentBlock("button1");
					$menu_tpl->setVariable(array(
						'NAME1'	=> strtoupper($name),
						'LINK1'	=> $link,
						'DID1'	=> $did
					));
					$menu_tpl->parseCurrentBlock();
				} else {
					$menu_tpl->setCurrentBlock("button2");
					$menu_tpl->setVariable(array(
						'NAME2'	=> strtoupper($name),
						'LINK2'	=> $link,
						'DID2'	=> $did
					));
					$menu_tpl->parseCurrentBlock();
				}
			}

			if ($dids==0) {
				$menu_tpl->setCurrentBlock("empty");
				$menu_tpl->setVariable(array(
					'ZEROW'	=> 150
				));
				$menu_tpl->parseCurrentBlock();
			}

			$menu_tpl->setCurrentBlock('menu1');
			$menu_tpl->parseCurrentBlock();

			$app->cp['menu1'] = $menu_tpl->get();
		}
	}
	
	/**
	 * make cp menu2
	 *
	 * @param array $menu
	 * @return void
	 */
	
	function menu2($menu) {
		GLOBAL $app;

		$menu_tpl = new template(TPL_PATH);
		$menu_tpl->loadTemplateFile("cms/cp",true,true);
		if (is_array($menu) && sizeof($menu)!=0) {
			foreach ($menu as $name=>$link) {
				if ($link == '') {
					$menu_tpl->setCurrentBlock("button6");
					$menu_tpl->setVariable(array(
						'NAME6'	=> strtoupper($name)
					));
					$menu_tpl->parseCurrentBlock();
				} else if ($link!='hidden') {
					$menu_tpl->setCurrentBlock("button3");
					$menu_tpl->setVariable(array(
						'NAME3'	=> strtoupper($name),
						'LINK3'	=> $link
					));
					$menu_tpl->parseCurrentBlock();
				}

				$menu_tpl->setCurrentBlock('menu2_temp');
				$menu_tpl->parseCurrentBlock();
			}
		} else {
			$menu_tpl->setCurrentBlock("empty2");
			$menu_tpl->setVariable(array(
				'VAR'	=> 0
			));
			$menu_tpl->parseCurrentBlock();
		}

		$menu_tpl->setCurrentBlock('menu2');
		$menu_tpl->parseCurrentBlock();
		$app->cp['menu2'] = $menu_tpl->get();
	}
	
	/**
	 * make cp title
	 *
	 * @param string $time
	 * @return void
	 */
	
	function title($title) {
		GLOBAL $app;
		$app->cp['title'] = strtoupper(str_replace('_',' ',$title));
	}
	
	/**
	 * make cp submenu
	 *
	 * @param array $menu
	 * @return void
	 */
	function submenu($menu,$delimeter=" <img src='img/main/delim.gif' width='3' height=18  align='absmiddle'> ") {
		GLOBAL $app;
		$menu_tpl = new template(TPL_PATH);
		$menu_tpl->loadTemplateFile("cms/cp",true,true);
		if (is_array($menu) && sizeof($menu)!=0) {
			$did=0;
			$dids=sizeof($menu);
			foreach ($menu as $link=>$name) {
				$did++;
				if ($link!='hidden') {
					$menu_tpl->setCurrentBlock("button4");
					$menu_tpl->setVariable(array(
						'NAME4'	=> $name,
						'LINK4'	=> $link
					));
					$menu_tpl->parseCurrentBlock();
				} else {
					$menu_tpl->setCurrentBlock("button5");
					$menu_tpl->setVariable(array(
						'NAME5'	=> $name
					));
					$menu_tpl->parseCurrentBlock();
				}
				if ($did!=$dids) {
					$menu_tpl->setCurrentBlock("delimeter");
					$menu_tpl->setVariable(array(
						'DELIMETER'	=> $delimeter
					));
				}

				$menu_tpl->setCurrentBlock('menu3');
				$menu_tpl->parseCurrentBlock();
			}
		}


		$app->cp['menu3'] = $menu_tpl->get();
	}
	
	/**
	 * make cp menu1
	 *
	 * @param array $menu
	 * @return void
	 */
	function calendar($script, $year, $month, $day) {
		$tpl = new template(TPL_PATH);
		$tpl->loadTemplatefile('def/calendar',true,true);
		$tpl->setVariable(array(
			'SCRIPT'	=> $script,
			'YEAR'		=> $year,
			'MONTH'		=> $month-1,
			'DAY'		=> $day
		));
		$tpl->parseCurrentBlock();
		return $tpl->get();
	}

/****************** CONF FILE RELATED **************************/
	
	/**
	 * load $LIST_{} from file
	 *
	 * @param string $filename
	 * @param string $listname
	 * @return array
	 */
	function loadConfList($filename,$listname) {
		// $filename - name of conf file
		// $list name - name of array in conf file (LIST_MONTHS for example);
		$fp=fopen(INIT_PATH.CONF_PATH.$filename,'r');
		$line=-1;
		$list_arr = array();
		if($fp) {
			while(!feof($fp)) {
				$buffer=fgets($fp);
				$line++;
				if( !isset($start) && preg_match("/\s*\\$".strtoupper($listname)."\s*=/",$buffer,$matches) ) {
					$start=$line;
				}
				if(isset($start)  && !isset($end) && preg_match("/.*'(\d+)'\s*=>\s*'(.*)(')|(',)$/",$buffer,$matches) ) {
					$list_arr[]=array(
							'id'	=> $matches[1],
							'name'	=> stripslashes($matches[2])
					);
				}
				if( isset($start)  && isset($end) ) {
					//$file_2[$line]=$buffer;
				}
				if(isset($start)  && !isset($end) && preg_match("/.*\);.*/",$buffer,$matches) ) {
					$end=$line;
				}
				if( !isset($start)  && !isset($end) ) {
					//$file_1[$line]=$buffer;
				}
			}

			fclose($fp);
			return $list_arr;

		} else {
			exit('can\'t open  to read '.INIT_PATH.CONF_PATH.$filename);
			return false;
		}
	}

	/**
	 * save $list_arr into file
	 *
	 * @param string $filename
	 * @param string $listname
	 * @param array $list_arr
	 * @return boolean
	 */
	function saveConfList($filename,$listname,$list_arr) {
		// $filename - name of conf file
		// $listname - name of list array in conf file
		// $list_arr - znachenie massiva kot. nuzno sohranit'
		// $file_1 - data before list_arr
		// $file_2 - data after list_arr
		$file_1 = $file_2 = array();

		//first read $filename and update $listname
		$fp=fopen(INIT_PATH.CONF_PATH.$filename,'r');
		$line=-1;
		if($fp) {
			while(!feof($fp)) {
				$buffer=fgets($fp);
				$line++;
				if( !isset($start) && preg_match("/\s*\\$".strtoupper($listname)."\s*=/",$buffer,$matches) ) {
					$start=$line;
				}
				if(isset($start)  && !isset($end) && preg_match("/.*'(\d+)'\s*=>\s*'(.*)(')|(',)$/",$buffer,$matches) ) {
					//do nothing
				}
				if( isset($start)  && isset($end) ) {
					$file_2[$line]=$buffer;
				}
				if(isset($start)  && !isset($end) && preg_match("/.*\);.*/",$buffer,$matches) ) {
					$end=$line;
				}
				if( !isset($start)  && !isset($end) ) {
					$file_1[$line]=$buffer;
				}
			}

			fclose($fp);

		} else {
			exit('can\'t open  to read '.INIT_PATH.CONF_PATH.$filename);
			return false;
		}

		//second save new $filename
		$fp=fopen(INIT_PATH.CONF_PATH.$filename,'w');
		if($fp) {
			foreach($file_1 as $item) {
				fwrite($fp,$item);
			}

			$qty=count($list_arr);
			if($qty>0) {
				fwrite($fp,'$'.strtoupper($listname)." = array(\n" );
				foreach($list_arr as $key=>$item) {
					$i++;
					$item_id = addslashes( $item['id'] );
					if($i==$qty) {
						fwrite($fp,"\t'".$item_id."' => '".addslashes($item['name'])."'\n");
					} else {
						fwrite($fp,"\t'".$item_id."' => '".addslashes($item['name'])."',\n");
					}
				}
				fwrite($fp,");\n");
			}
			foreach($file_2 as $item) {
				fwrite($fp,$item);
			}
			fclose($fp);
			return true;
		} else {
			exit('can\'t open  to write '.INIT_PATH.CONF_PATH.$filename);
			return false;
		}
	}

	/**
	 * load defined constants
	 *
	 * @param string $filename
	 * @param string $defname
	 * @return array
	 */
	function loadDefined($filename,$defname) {
		$fp=fopen(INIT_PATH.CONF_PATH.$filename,'r');
		$line=-1;
		$list_arr = array();
		$what=strtoupper($defname);
		if($fp) {
			while(!feof($fp)) {
				$buffer=fgets($fp);
				$line++;

				if( !isset($start) && preg_match("/\s*define\(\'".$what."\'\s*,\s*\'(.+)\'\s*\)\s*;/",$buffer,$matches) ) {
					$start=$line;
					$value=$matches[1];
				}
			}
			if(isset($value)) {
				$def_arr = array(
					'id'	=> $defname,
					'value'	=> stripslashes($value)
				);
			}
			fclose($fp);
			return $def_arr;

		} else {
			exit('can\'t open  to read '.INIT_PATH.CONF_PATH.$filename);
			return false;
		}
	}
	
	/**
	 * save defined constants
	 *
	 * @param string $filename
	 * @param string $defname
	 * @param string $def_value
	 * @return boolean
	 */
	function saveDefined($filename,$defname,$def_value) {

		$what=strtoupper($defname);
		// $file_1 - data before $defname
		// $file_2 - data after $defname
		$file_1 = $file_2 = array();

		//first load file
		$fp=fopen(INIT_PATH.CONF_PATH.$filename,'r');
		$line=-1;
		$list_arr = array();
		$what=strtoupper($defname);
		if($fp) {
			while(!feof($fp)) {
				$buffer=fgets($fp);
				$line++;

				if( isset($start) ) {
					$file_2[$line]=$buffer;
				}

				if( !isset($start) && preg_match("/\s*define\(\'".$what."\'\s*,\s*\'(.+)\'\s*\)\s*;/",$buffer,$matches) ) {
					$start=$line;
					//$value=$matches[1];
				}

				if( !isset($start) ) {
					$file_1[$line]=$buffer;
				}

			}
			fclose($fp);

		} else {
			exit('can\'t open  to read '.INIT_PATH.CONF_PATH.$filename);
			return false;
		}

		//save edited file
		$fp=fopen(INIT_PATH.CONF_PATH.$filename,'w');
		if($fp) {
			foreach($file_1 as $item) {
				fwrite($fp,$item);
			}

			fwrite($fp,'define(\''.$what."',  '".addslashes($def_value)."');\n" );

			foreach($file_2 as $item) {
				fwrite($fp,$item);
			}
			fclose($fp);
			return true;
		} else {
			exit('can\'t open  to write '.INIT_PATH.CONF_PATH.$filename);
			return false;
		}
	}
}

$GLOBALS['cp'] = $cp = new ControlPanel();
?>
