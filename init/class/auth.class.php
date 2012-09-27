<?php
/*
	$Header: /cvs_repository/lisk/engine/init/class/auth.class.php,v 1.3 2005/02/10 17:06:28 andrew Exp $
	
    class Authorization
    scheme 2.0.2
*/

class Authorization {
    
    var $level_to_pass; // need access level of requested page
    
    // refer to auth.cfg.php array
    var $table, $default, $login_page, $logged_page, $jump_to_logged_page, $remove_auto_login, $sid_name, $pages;
    
    /**
    * @desc constructor
    */
    function Authorization() {    
        GLOBAL $app;
        
        $auth_name = 'AUTH_'.strtoupper(INIT_NAME);
        
        if (!$this->_checkStruct(@$GLOBALS[$auth_name])) {
            exit('auth struct error');
        }

        $this->table = $GLOBALS[$auth_name]['table'];
        $this->default = $GLOBALS[$auth_name]['default'];
        $this->login_page = $GLOBALS[$auth_name]['login_page'];
        $this->logged_page = $GLOBALS[$auth_name]['logged_page'];
        $this->jump_to_logged_page = $GLOBALS[$auth_name]['jump_to_logged_page'];
        $this->remove_auto_login = $GLOBALS[$auth_name]['remove_auto_login'];
        $this->sid_name = $GLOBALS[$auth_name]['sid_name'];
        $this->pages = $GLOBALS[$auth_name]['pages'];
        
        
        //pass thru login page
        if ($this->_getScriptExecuting() == $this->login_page ) {
            return true;
        }
         
        //remember where to go after login
        $this->_setUriRequested();
        
        $this->_debug('AUTH init', $auth_name, $auth_name);
        
        //define if authorization is required, wich level is required
        $this->level_to_pass = isset($this->pages[$this->_getScriptRequested()]) ? $this->pages[$this->_getScriptRequested()] : $this->default;
		
        if ($this->authorize()) {
            $this->_removeUriRequested();
        } else {
            $app->jump($this->login_page);
        }

        $this->_debug('AUTH check', 'end');
    }
    
    /**
    * @return boolean
    * @desc primary authorization method
    */
	function authorize() {
	    
		$this->_debug('AUTH check', 'start');
		
		GLOBAL $db;
		GLOBAL $app;		
		$sid = @$_SESSION[$this->sid_name];
		$try_auto_login = false;

		if ($sid == '') {
			$try_auto_login = true;
		} else {
		    
			$db->setTable($this->table);
			$res = $db->get(array('sid' => $sid));
			
			if ($res === false) {
				$try_auto_login = true;
			} else {
				// check access level
				if ($this->level_to_pass>$res['level']) {
					return false;
				}
                
				$app->user = $res;
				
				// update lastdate
				$db->update('id='.$res['id'], array('lastdate' => formatDateTimeNow(time())));
				return true;
			}
		}

		// check Autologin
		if ($try_auto_login) {
            return $this->_autoLogin();
		} else {
            return false;
		}
	}
	
	/**
    * @return boolean
    * @param $login string
    * @param $password string
    * @desc log user in 
    */
	function login($login, $password) {
        GLOBAL $app;
		GLOBAL $db;

		if ($login=='' || $password=='') {
			$app->setError(ERROR_LOGIN);
			return false;
		}

		$db->setTable($this->table);
		$res = $db->get(array('login' => $login, 'password' => $password));
		if ($res === false) {
			$app->setError(ERROR_LOGIN);
			return false;
		}

		$now = formatDateTimeNow();
		$sid = md5($res['login'].$now.'secure');

		// save autologin data
		if (@$_POST['autologin']>0) {
			$this->_setAutoLogin($res['login'], $res['password']);
		}

		// update data
		if (!isset($_SESSION['AUTH_last_login'])) {
			$db->update("id='{$res['id']}'", array('sid' => $sid, 'lastdate' => $now, 'lastlogin' => $now));
			$_SESSION['AUTH_last_login'] = $res['lastlogin'];
		} else {
			$db->update("id='{$res['id']}'", array('sid' => $sid, 'lastdate' => $now));
		}
		
		/*if (INIT_NAME!='cp') {
			GLOBAL $actionStatistics;
			$actionStatistics->set('user','login');
		}*/
		
		// previous visit info
		$_SESSION['AUTH_last_visit'] = $res['lastdate'];
		$_SESSION[$this->sid_name] = $sid;
        
		$app->user = $res;
		
		// make jump in case it's a login page auth starter, otherwise jumps are handled in constructor
		if ( $this->_getScriptExecuting() == $this->login_page ) {
            $app->jump($this->_getJumpUrl());
		}
		
		return true;
	}
	
	/**
    * @return void
    * @desc log user out
    */
	function logout() {
		$this->_removeUriRequested();
		
		if ($this->remove_auto_login) {
            $this->_removeAutoLogin();
		}
		
		$this->_destroy();
	}
	
    /* internal only methods  */
    
    /**
    * @return boolean
    * @param array $struct
    * @desc check if auth structure is valid
    */
    
    function _checkStruct($struct) {
        
        if (!arrayIsOk($struct)) {
            return false;
        }
        
        $required = array(
            'table', 'default', 'login_page', 'logged_page', 'jump_to_logged_page', 'sid_name', 'pages',
            'remove_auto_login',
        );
        
        $structOk = true;
        $structKeys = array_keys($struct);
        
        foreach($required as $v) {
            if (!in_array($v, $structKeys)) {
                $structOk = false;
                break;
            }
        }
        
        return $structOk;
    }
    
    /**
    * @return void
    * @param void
    * @desc remember URI requested the authorization
    */
    
    function _setUriRequested() {

        if ($this->_getScriptExecuting()!=$this->login_page
            && $this->_getScriptExecuting()!=$this->_getScriptRequested()
        ) {
            $_SESSION['AUTH_uri_requested'] = $_SERVER['REQUEST_URI'];
            $_SESSION['AUTH_script_requested'] = basename($_SERVER['SCRIPT_FILENAME']);
        }

    }
    
    /**
    * @return string
    * @param void
    * @desc get full URI requested the authorization
    */
    
    function _getUriRequested() {
        return $_SESSION['AUTH_uri_requested'];
    }
    
    /**
    * @return string
    * @param void
    * @desc get filename only of script requested the authorization
    */
    
    function _getScriptRequested() {
        return @$_SESSION['AUTH_script_requested'];
    }
    
    /**
    * @return string
    * @param void
    * @desc get script execing authorization
    */
    
    function _getScriptExecuting() {
        return basename($_SERVER['SCRIPT_FILENAME']);
    }
    
    /**
    * @return void
    * @param void
    * @desc unset requested URI
    */
    
    function _removeUriRequested() {
        $_SESSION['AUTH_uri_requested'] = '';
        $_SESSION['AUTH_script_requested'] = '';
        unset($_SESSION['AUTH_uri_requested']);
        unset($_SESSION['AUTH_script_requested']);
    }
    
    /**
    * @return void
    * @param void
    * @desc unset session auth sid_name
    */
    
    function _destroy() {
        $_SESSION[$this->sid_name] = '';
        unset($_SESSION[$this->sid_name]);
        unset($_SESSION['AUTH_last_login']);
        unset($_SESSION['AUTH_last_date']);
    }
    
    /**
    * @return void
    * @param void
    * @desc add auth action into debug array
    */
    
    function _debug($action, $params, $rez='') {
		if (defined('DEBUG') && DEBUG==1) {
			$GLOBALS['AUTH_DEBUG'][]=array(
				'action'	=> $action,
				'params'	=> $params,
				'rez'		=> $rez
			);
		}
	}
	
	/**
    * @return boolean
    * @param void
    * @desc log member in if autologin is set
    */
    
	function _autoLogin() {
	    GLOBAL $db;
	    
	    if ($this->issetAutoLogin()) {
            $db->setTable($this->table);
			
            list($login, $password) = $this->_getAutoLogin();
            $res = $db->get(array('login' => $login));

			if ($res!==false && $password==md5($res['password'].'security')) { 
				
			    if ($this->login($res['login'],$res['password'])) {
				
    				// check access level
    				if ($this->level_to_pass>$res['level']) {
    					$this->_debug('AUTH autoLogin', $login.' - '.$password, 'False - not enough user access level');
    					return false;
    				}
    				
    				$this->_debug('AUTH autoLogin', $login.' - '.$password, 'True');
    				return true;
    				
			    } else {
                    return false;			    
                    
			    }
			} else {
				$this->_debug('AUTH autoLogin', $login.' - '.$password, 'False - no login found or password is incorrect');
				return false;
			}
		} else {
		    
		    if($this->level_to_pass==0) {
                return true;
		    } else {
                $this->_debug('AUTH autoLogin', '', 'False - no login and/or password was found');
                return false;
		    }
		}
	   
	}
	
	/**
    * @return boolean
    * @param void
    * @desc check if autologin data is set
    */
    
	function issetAutoLogin() {
		if (isset($_COOKIE[$this->sid_name.'login']) && isset($_COOKIE[$this->sid_name.'password'])) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
    * @return void
    * @param $login string
    * @param $password string
    * @desc store user autologin data
    */
    
	function _setAutoLogin($login, $password) {
        $tm = 31536000;
        setcookie($this->sid_name.'login', $login, time()+$tm);
        setcookie($this->sid_name.'password', md5($password.'security'), time()+$tm);
        $this->_debug('AUTH auto', '_setAutoLogin', $login.' '.$password);
	}
	
	/**
    * @return array
    * @param void
    * @desc get user autologin data
    */
    
	function _getAutoLogin() {
	   return array( 
	       $_COOKIE[ $this->sid_name.'login' ],
	       $_COOKIE[ $this->sid_name.'password' ],
	   );
	}
	
	/**
    * @return void
    * @param void
    * @desc unset user autologin data
    */
    
	function _removeAutoLogin() {
		$tm = 31536000;
		setcookie($this->sid_name.'login', '' , time()-$tm);
		setcookie($this->sid_name.'password', '' , time()-$tm);
		unset($_COOKIE[$this->sid_name.'login']);
		unset($_COOKIE[$this->sid_name.'password']);
		$this->_debug('AUTH _removeAutoLogin','remove cookies');
	}
	
	/**
    * @return string
    * @desc get url to jump to on user successful login
    */
    
	function _getJumpUrl() {
        
        if ($this->jump_to_logged_page) {
            $jumpTo = $this->logged_page;
        } else {
            $jumpTo = $this->_getUriRequested();
            if(!strlen($jumpTo)) {
                $jumpTo = $this->logged_page;
            }
        }
        
        return $jumpTo;
	}
}

$GLOBALS['auth'] = $auth = new Authorization();
?>