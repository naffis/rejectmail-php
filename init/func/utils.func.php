<?
// Format utilities function
// v 3.0

function back() {
	GLOBAL $app;
	$referrer = $_SERVER['HTTP_REFERER'];
	if ($app->getBaseName($referrer)!=$app->getCurUrl()) {
		$_SESSION['system_link_back']=$referrer;
	}
	return $_SESSION['system_link_back'];
}

function listToHash($list, $keyName, $valueName) {
	$hash = array();
	if (arrayIsOk($list)) {
		foreach($list as $item) {
			$hash[$item[$keyName]] = $item[$valueName];
		}
	}
	return $hash;
}

function treeToArray($parents) {
	$parents=str_replace('<','',$parents);
	$rez =split('>',$parents);
	unset($rez[sizeof($rez)-1]);
	return $rez;
}

function treeToIn($parents) {
	$str=implode(',',treeToArray($parents));
	return '('.$str.')';
}

function treeToNavigation($id,$tree_name) {
	GLOBAL $db;
	if ($id=='') $id=1;
	$tree=$GLOBALS['TREE_'.strtoupper($tree_name)];
	$node=$GLOBALS['DATA_'.strtoupper($tree['node'])];
	$parents=$db->get("id=$id",'parents',$node['table']);
	$parents=treeToIn($parents."<$id>");
	$names=$db->select("id IN $parents",'id','id,name',$node['table']);	
	return $names;
}


function getAge($birth_date) {
	return floor((time()-strtotime($birth_date))/(60*60*24*365.25));
}

// ========================================
// 
// ========================================


function dateDifference($date1,$date2,$rez_in='days') {
	$date1_s = strtotime($date1);
	$date2_s = strtotime($date2);
	$dif = $date1_s-$date2_s;
	$rez_sign=($dif>0)?-1:1;
	$dif=abs($dif);
	switch ($rez_in) {
		case 'days':
			$result = round($dif/86400);
		break; 
	}
	return ($rez_sign*$result);
}


/**
 * @return string formatted with blank
 * @param string $str - string to format.
 * @param int $cnt - interval for formatting
 * @param char $separator - char for end of line
 * @desc Format time time interval (split and add 'secunde(s)',
'minute(s)' etc.)
*/
function formatStrSpaces($str, $cnt, $separator = '') {
        
        // if strlen($str) <= $cnt do nothing
        if (strlen($str) <= $cnt) return $str;
        
        $return = '';
        
        while (strlen($str) > $cnt) {
                
                // Get pierce of $str
                $temp = substr($str, 0, $cnt);
                
                // Finding $separator
                if (!empty($separator) && ($pos = strrpos($temp,$separator)) !== false) {
                        $pos++;
                        $return .= substr($temp, 0, $pos)." ";
                        $str = substr($temp, $pos).substr($str, $cnt);
                }
                else {
                        $return .= $temp." ";
                        $str = substr($str, $cnt);
                }
        }

        // If nothing to add, delete last blank
        if (empty($str)) $return = substr($return, 0, -1);
        
        return $return.$str;
}

function formatDebug($arr) {
	ob_start();
	print_r($arr);
	$val_r = ob_get_contents();
	ob_end_clean();	
	return nl2br($val_r);
}

function arrayIsOk($arr=null) {
	if (isset($arr) && is_array($arr) && sizeof($arr)>0) {
		return true;
	} else {
		return false;
	}	
}

function formatCaption($name) {
	return ucwords(str_replace('_', ' ', $name));
}

/**
 * @return string Formatted current date
 * @desc Get current date and return it formatted
*/
function formatDateNow() {
	return date("Y-m-d");
}

/**
 * @return string Formatted current time
 * @desc Get current time and return it formatted
*/
function formatTimeNow() {
	return date("H:i:s");
}

/**
 * @return string Formatted current date and time
 * @desc Get current date and time and return it formatted
*/
function formatDateTimeNow($time='def') {
	if ($time=='def') {
		$time = time();
	}
	return date("Y-m-d H:i:s",$time);
}

/**
 * @return string 	Formatted date
 * @param int $date Date wich must be formatted
 * @param string $format Date format
 * @desc Return formatted date
*/
function formatDate($date, $format = '') {
	if ($format == '') {
		$format = DATE_FORMAT;
	}
	return date($format ,strtotime($date));
}

/**
 * @return string 	Formatted date
 * @param int $date Date wich must be formatted
 * @param string $format Date format
 * @desc Return formatted date without day
*/
function formatMonth($date, $format = '') {
	if ($format == '') {
		$format = "F Y";
	}
	return date($format ,strtotime($date));
}

/**
 * @return string 	Formatted time
 * @param int $time Time wich must be formatted
 * @param string $format Time format
 * @desc Return formatted time
*/
function formatTime($time, $format = '') {
	if ($format == '') {
		$format = TIME_FORMAT;
	}
	return date($format ,strtotime($time));
}

/**
 * @return string 	Formatted date and time
 * @param int $date Date and time wich must be formatted
 * @param string $format Date and time format
 * @desc Return formatted date and time
*/
function formatDatetime($date, $format='') {
	if ($format == '') {
		$format = DATE_FORMAT.' '.TIME_FORMAT;
	}
	return date($format ,strtotime($date));
}

/**
 * @return string Formatted price
 * @param float $price Price which must be formatted
 * @desc Format price
*/
function formatPrice($price) {
	if ($price == '') $price = 0;
	return sprintf("%.2f", $price);
}

/**
 * @return string formatted file date
 * @param int $size File size which must be formatted
 * @desc Format file size (add thousand separators and Kb, Mb etc.)
*/
function formatFileSize($size) {
	if ($size<1024) {
		return $size.' bytes';
	} elseif ($size<1048567) {
		$size=round($size/1024,2);
		return $size.' KB';
	} else {
		$size=round($size/1048567,2);
		return $size.' MB';
	}
}

/**
 * @return string Formatted time interval
 * @param int $time Time interval in seconds.
 * @desc Format time time interval (split and add 'secunde(s)', 'minute(s)' etc.)
*/
function formatTimeLength($time) {
	if ($time<60) {
		$return = $time.' second(s)';
	} else if ($time<3600) {
		$seconds=$time%60;
		$minutes=($time-$seconds)/60;
		$return = $minutes.' minute(s), '.$seconds.' second(s)';
	} else if ($time<86400) {
		$time=round($time/60);
		$minutes=$time%60;
		$hours=round($time/60);
		$return = $hours.' hour(s), '.$minutes.' minute(s)';
	} else {
		$time=round($time/3600);
		$hours=$time%24;
		$days=round($time/24);
		$return = $days.' day(s), '.$hours.' hour(s)';
	}
	return $return;
}



function tpl_eval($matches) {
   eval(' $result = ' . preg_replace('@\$([A-Za-z][A-Za-z0-9_]+)@sm', '$GLOBALS[\'\1\']', $matches[1]).';');
   return preg_replace_callback(EVAL_REG_EXP, 'tpl_eval', $result);
}



?>