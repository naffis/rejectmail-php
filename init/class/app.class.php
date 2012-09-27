<?php 
/*  
	$Header: /cvs_repository/lisk/engine/init/class/app.class.php,v 1.2 2005/02/10 13:14:34 andrew Exp $
	
	Class Application
    v 3.0.1
    Wed Nov 17 13:34:47 EET 2004 - syntax fix
    Mon Nov 22 17:40:33 EET 2004 -  WarHead tpl eval implemented
*/

/**
 * get time with microseconds
 *
 * @return float 
 */
function getmicrotime() {
    list($usec, $sec) = explode(' ', microtime());
    return ((float)$usec + (float)$sec);
}

/**
 * stripes slashes from $value if magic quotes is used
 *
 * @param mixed $value
 * @param mixed $nonstrip define variables which can't be stripped 
 * @return mixed
 */
function _stripslashes($value,$nonstrip = '') {
		
	if (get_magic_quotes_gpc()) {
		if (is_array($value)) {
			foreach ($value as $key => $val) {
				if (!strstr($nonstrip,$key)) {									
					if((is_array($val)) and (!array_key_exists($nonstrip,$val))) {
						$value[$key] = _stripslashes($val);
					} else $value[$key] = stripslashes($val);
				} else {
					$value[$key] = $val;
				}
			}
		} else {
			if ($nonstrip == NULL)
				$value = stripslashes($value);
		}
	}
	return $value;
}


class Application {
	var $global_template,   // global template name
		$error,				// app error array
		$user,				// cur user info
		$paging;			// paging handler

	/**
	* @return Application Application object
	* @desc Constructor - set global pathes, error reporting and maximum script execution time; register $_POST, $_GET, $_SESSION variables as $_GLOBALS.
	*/
	function Application() {

		// set timer ON for global execute time
		$GLOBALS['start_time'] = getmicrotime();

		// set up global template
		$this->global_template = 'global';

		// create empty array of app errors
		$this->error = array();

		// set GET, POST, SSESION variables global & strip slashes
		if (is_array($_POST)and(sizeof($_POST) > 0)) {
			foreach ($_POST as $key=>$val) {
				$val = _stripslashes($val);
				$_POST[$key]=$val;
				$GLOBALS[$key] = $val;
			}
		}		
		if (is_array($_GET)and(sizeof($_GET) > 0)) {
			foreach ($_GET as $key=>$val) {
				$val = _stripslashes($val);
				$_GET[$key]=$val;
				$GLOBALS[$key] = $val;
			}
		}
		if (is_array($_SESSION)and(sizeof($_SESSION) > 0)) {
			foreach ($_SESSION as $key=>$val) {
				$val = _stripslashes($val);
				$_SESSION[$key]=$val;
				$GLOBALS[$key] = $val;
			}
		}

		//
		$this->user=false;

		// load CP if needed
		if (defined('CP_CLASS') && CP_CLASS==1) {
			$this->load('cp','class');
		}

		// set time limit & error level
		error_reporting(ERROR_LEVEL);
		set_time_limit(TIME_LIMIT);

		// define global tpl path
		if (ROOT_PATH!='./' && ROOT_PATH!='') {
			define('GLOBAL_TPL_PATH',	'../'.ROOT_PATH.'tpl/global/');
		} else {
			define('GLOBAL_TPL_PATH', 'global/');
		}

		$this->paging = false;

	} //end constructor Application()

	/**
	 * @desc Desctructor - Update statistics, free resources.
	 */
	function destroy() {
		GLOBAL $db;
		GLOBAL $actionStatistics;

		// stat action
		if (defined('STAT_ACTION') && STAT_ACTION == 1) {
			$actionStatistics->save();
		}

		$db->disconnect();
		exit;
	}

	/**
	 * @desc Set Global Template name
	 * @param string new global template name
	 *
	 */
	function setGlobalTemplate($name) {
		if ($name=='0') {
			$name=GLOBAL_TPL_PATH.$name;
		}
		$this->global_template = $name;
	}

	/**
	* @param string $name
	* @desc Set paging
	*/
	function setPaging($name) {		
		$full_name = strtoupper('paging_'.$name);		
		$this->load('paging','cfg');		
		$paging = $GLOBALS[$full_name];

		if (!arrayIsOk($paging)) {
			$this->raiseError("Paging <b>$name</b> is unknown. Please check paging.cfg.php file.");
		} else {
			$this->paging = $paging;
			$this->paging['name'] = $name;
		}
	}

	function clearPaging() {
		$this->paging = false;
	}

	/**
	 * Manually sets total items of paging
	 *
	 * @param int $value
	 * @return void
	 */
	function setPagingTotal($total='') {
		
		$this->paging['total_items'] = $total;		
	}
	
	/**
	 * return total items
	 *
	 * @return int
	 */
	function getPagingTotal() {
		return @$this->paging['total_items'];
	}

	/**
	* @return string current url with get parameters
	* @desc Return current url
	*/
	function getCurUrl() {
		$rez = $this->getBaseName();
		if ($_SERVER['QUERY_STRING'] != '') {
			$rez .= '?'.$_SERVER['QUERY_STRING'];
		}
		return $rez;
	}

	/**
	* @return string script filename
	* @desc Return current script filename only
	*/
	function getBaseName($url='cur') {
		if ($url=='cur') $url = $_SERVER['PHP_SELF'];
		return basename($url);
	}

	/**
	 * Enter description here...
	 *
	 * @param array $variable
	 * @return string
	 */
	function addGetVariable($variable) {
		if (arrayIsOk($variable)) {
			// create url with get variables that are not in $variable array
			$return=$this->getBaseName().'?';
			if (arrayIsOk($_GET)) {
				foreach ($_GET as $key=>$value) {
					if ((!in_array($key,array_keys($variable))) && strpos($_SERVER['QUERY_STRING'],$key.'=')!==false) {
						$return.=$key.'='.$value.'&';
					}
				}
			}

			foreach ($variable as $key=>$value) {
				$return.=$key.'='.$value.'&';
			}
			$return=substr($return,0,-1);

			return $return;
		} else {
			if ($variable!='') $this->raiseError('$app->addGetVariable require ARRAY !!!');
			return $this->getCurUrl();
		}
	}

	// ========================== ERROR METHODS ============================

	/**
	 * @param array|string $error - error message string or array of errors
	 * @desc Set error, that will be parsed next time app->output is called
	 */
	function setError($error) {
		if (!is_array($error)) {
			$_SESSION['SYS_app_error'][]=$error;
		} else {
			$_SESSION['SYS_app_error']+=$error;
		}
	}

	/**
	 * @desc Raise LISK internal error. Application die.
	 * @param string $error - error message
	 */
	function raiseError($error) {
		echo "<h1>ERROR</h1><br>".$error;
		$this->destroy();
	}

	/**
	 * @desc Parse error(s) to the template, clear error(s).
	 */
	//sys
	function __parseError() {
		GLOBAL $tpl;
		GLOBAL $parser;
        $error_result = '';
		foreach ($this->error as $error_msg) {
			$error_result .= $error_msg.'\n\r';
		}

		$err_arr=array(
			'ERROR_MESSAGE'	=> $error_result
		);

		$error = $parser->makeView($err_arr, GLOBAL_TPL_PATH.'error','error');

		$tpl->setVariable(array(
			'ERROR'	=> $error
		));

		unset($_SESSION['SYS_app_error']);
	}

	// =========================== LOAD METHODS ====================================

	/**
	 * @desc Load module file(s) by using function loadModule().
	 * @param string $module_name - name of module
	 * @param string $module_type - type of file (def, cfg, class, func, inc, 3ger)
	 * @see loadModule()
	 */
	function load($module_name, $module_type='def') {
		if ($module_type=='def') {
			if (file_exists(SYS_ROOT.INIT_PATH.CFG_PATH.$module_name.'.cfg.php')) {
				$this->__loadModule($module_name, 'cfg');
			}
			if (file_exists(SYS_ROOT.INIT_PATH.CLASS_PATH.$module_name.'.class.php')) {
				$this->__loadModule($module_name, 'class');
			}
			if (file_exists(SYS_ROOT.INIT_PATH.INC_PATH.$module_name.'.inc.php')) {
				$this->__loadModule($module_name, 'inc');
			}
			if (file_exists(SYS_ROOT.INIT_PATH.FUNC_PATH.$module_name.'.func.php')) {
				$this->__loadModule($module_name, 'func');
			}
		} else {
			$this->__loadModule($module_name,$module_type);
		}
	}

	/**
	 * @desc Load module. This function used by load().
	 * @param string $module_name - module name
	 * @param string $module_type - type (def, cfg, class, func, inc, 3ger)
	 * @see load()
	 */
	//sys
	function __loadModule($module_name, $module_type) {
		switch ($module_type) {
			case 'cfg':
				include_once SYS_ROOT.INIT_PATH.CFG_PATH.$module_name.'.cfg.php';
				$arr = get_defined_vars();
				unset($arr['GLOBALS']);
				unset($arr['this']);
				unset($arr['name']);
				unset($arr['mod_type']);
				$GLOBALS+=$arr;
				break;
			case 'class':
				include_once SYS_ROOT.INIT_PATH.CLASS_PATH.$module_name.'.class.php';
				break;
			case 'func':
				include_once SYS_ROOT.INIT_PATH.FUNC_PATH.$module_name.'.func.php';
				break;
			case 'inc':
				include_once SYS_ROOT.INIT_PATH.INC_PATH.$module_name.'.inc.php';
				break;
			case 'mod':
				include_once SYS_ROOT.INIT_PATH.MODULE_PATH.$module_name.'.mod.php';
				$arr = get_defined_vars();
				unset($arr['GLOBALS']);
				unset($arr['this']);
				unset($arr['name']);
				unset($arr['mod_type']);
				$GLOBALS+=$arr;
				break;
			case 'tger':
				include_once SYS_ROOT.INIT_PATH.TGER_PATH.$module_name.'.tger.php';
				break;
			case 'type':
				include_once SYS_ROOT.INIT_PATH.TYPE_PATH.$module_name.'.type.php';
				break;
			default:
				$this->raiseError('Error in loadModule. Unknown type '.$module_type);
				break;
		}
	}

// =========================== JUMP & BACK FUNCTIONS ==========================

	/**
	 * @desc Jump to specified url
	 * @param string $url target url
	 */
	function jump($url) {
		GLOBAL $tpl;
		$tpl->makeDebug();
		header('Location: '.$url);
		$this->destroy();
	}

	/**
	 * @desc Set Back Url with specified depth's level
	 * @param int $level - depth's level
	 * @param string $query - addtional GET parameters
	 */
	function setBack($level=0, $query='') {
		if ($query != '') {
			if ($_SERVER['QUERY_STRING'] != '') {
				$query = '?'.$_SERVER['QUERY_STRING'].'&'.$query;
			}
			$_SESSION['SYS_'.INIT_NAME.'_back_'.$level] = $_SERVER['PHP_SELF'].$query;
		} else {
			if ($_SERVER['QUERY_STRING'] != '') {
				$query = '?'.$_SERVER['QUERY_STRING'];
			}
			$_SESSION['SYS_'.INIT_NAME.'_back_'.$level] = $_SERVER['REQUEST_URI'];
		}
	}

	/**
	 * @desc Get Back Url with specified depth's level
	 * @param int $level - depth's level
	 * @return string back url
	 */
	function getBack($level=0) {
		if (!isset($_SESSION['SYS_'.INIT_NAME.'_back_'.$level])) return '';
		else return $_SESSION['SYS_'.INIT_NAME.'_back_'.$level];
	}

	/**
	 * @desc Jump to Back Url with specified depth's level
	 * @param int $level - depth's level
	 */
	function jumpBack($level=0) {
		$back_url=$this->getBack($level);
		if ($back_url!='') {
			$this->jump($back_url);
		}
	}

	/**
	* @param unknown $object
	* @param unknown $action_type
	* @param unknown $object_id
	* @param unknown $quantity
	* @desc Call $statistic->setStat()
	*/
	function setStat($object, $action_type, $object_id='', $quantity = 1) {
		GLOBAL $statistic;
		$statistic->setStat($object, $action_type, $object_id, $quantity);
	}

	/**
	* @return boolean true -- if email was seccesfully sended, false -- otherwise
	* @param string $email_name email type name
	* @param array $email_values hash of email values
	* @desc Send email.
	*/
	function sendMail($email_name, $email_values=null) {
		GLOBAL $db;

		// get email
		$email = $db->get("name='$email_name'",'','email');

		// check email name if exists
		if ($email === false) {
			$this->raiseError("Email $email_name is not found");
		}

		$recipients		= trim($email['recipients']);
		$subject		= trim($email['subject']);
		$from_header	= trim($email['from_header']);
		$body			= $email['body'];

		// add NL2BR to body if body is plain text
		if ($body == strip_tags($body)) {
			//$body = nl2br($body);
		}

		// add email values to email
		if (isset($email_values) && is_array($email_values)) {
			foreach ($email_values as $key=>$value) {
				$recipients = str_replace('%'.strtoupper($key).'%',$value,$recipients);
				$subject = str_replace('%'.strtoupper($key).'%',$value,$subject);
				$from_header = str_replace('%'.strtoupper($key).'%',$value,$from_header);
				$body = str_replace('%'.strtoupper($key).'%',$value,$body);
			}
		}

		$body = str_replace('%HTTP_ROOT%','http://'.$_SERVER[HTTP_HOST].HTTP_ROOT,$body);

		// get recipients array
		$recipients_arr = explode(',', $recipients);

		// create email header
		$header = '';
		if ($email['content_type_header'] == 1) {
			$header .= "MIME-version: 1.0\r\n";
			$header .= "Content-type: text/html; charset=iso-8859-1\r\n";
		}

		if ($from_header != '') {
			$header .= "From: $from_header\r\n";
		}

		// debug
		if (defined('DEBUG') && DEBUG == 1) {
			$GLOBALS['email_debug'][$email_name] = array(
				'recipients'	=> $recipients,
				'subject'		=> $subject,
				'body'			=> $body,
				'header'		=> $header
			);
		}

		// send mail
		foreach ($recipients_arr as $to) {
			$to = trim($to);
/*			echo "<br>to = $to 
				<br>subject = $subject 
				<br>body = $body
				<br>header = $header
				";*/
			@mail(
				$to,
				$subject,
				$body,
				$header
			);
		}
		return true;
	}

	// ========================== SYSTEM FUNCTIONS ===============================

	/**
	 * @desc Blocks - execute block functions
	 * @param string $page - page html code
	 * @returns string new html code with results of block functions
	*/
	function __blocks($page) {
		// <<<MENU>>>
		preg_match_all("/<<<([1234567890A-Z?\_]+?)>>>/ms", $page, $regs);

		$blocks=array();
		if (0 != count($regs[1])) {
	        foreach ($regs[1] as $k => $var) {
				$par='';
				$var_old=$var;
				$fqs=strpos($var,'?');
				if ($fqs!==false) {
					$var=substr($var,0,$fqs);
					$par=substr($var_old,$fqs+1);
				}

				$var=strtolower($var);
				$str='$func_rez=$var($par);';
				eval($str);
				$blocks[$regs[0][$k]]=$func_rez;
			}
		}

		foreach ($blocks as $k=>$var) {
			$page=str_replace($k,$var,$page);
		}
		return $page;
	}

	/**
	 * @param array $additional - array additional variables to global template
	 * @desc Otput - finalize app and  make page output
	 */
	function output($additional = '') {
		GLOBAL $tpl;
		GLOBAL $cp;					
		$page = $tpl->get();
		unset($tpl->blocklist['__global__']);
		$tpl->free();

		$tpl->loadTemplatefile($this->global_template, true, true);
		// слитие добавочных переменных				
		$additional = array_merge($additional,array('PAGE' => $page));
		$tpl->setVariable( $additional);
		
		// control panel
		if (defined('CP_CLASS') && CP_CLASS=='1') {
			if (!isset($this->cp['menu1'])) $cp->menu1(array());
			if (!isset($this->cp['menu2'])) $cp->menu2(array());
            
			$tpl->setVariable(array(
				'TITLE'	=> isset($this->cp['title'])?$this->cp['title']:null,
				'MENU1'	=> $this->cp['menu1'],
				'MENU2'	=> $this->cp['menu2'],
				'MENU3'	=> isset($this->cp['menu3'])?$this->cp['menu3']:null,
				'PAGE2'	=> isset($this->cp['page'])?$this->cp['page']:null
			));
		}

		if (isset($_SESSION['SYS_app_error']) && is_array($_SESSION['SYS_app_error'])) {
			$this->error+=$_SESSION['SYS_app_error'];
		}

		if ($this->error && is_array($this->error)) {
			$this->__parseError();
		}

		// execute block functions
		$page=$this->__blocks($tpl->get());
		
		// stat user's surfing... 
		if (defined('STAT_VISIT') && STAT_VISIT == 1) {
			$this->load('visitstat','mod');
			GLOBAL $statistic;
			$page.=$statistic->getJS();
		}		

		// show page
		$tpl->show($page);

		// destroy application
		$this->destroy();

	}

} // end class Application

//what is is???
if (!defined('RIGHT')) {
	if (!defined('MAIN')) {
		session_start();
	}
}

$GLOBALS['app'] = $app = new application();
?>