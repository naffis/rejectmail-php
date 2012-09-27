<?
// Class time
// v 3.0.

class Ttime {
	
	function insert(&$values) {
		GLOBAL $data;
		$name = $data->element_name;
		if (isset($values[$name.'_hour']) && isset($values[$name.'_minute'])) {	
			$rez = $values[$name.'_hour'].':'.$values[$name.'_minute'].':'.'00';
		} elseif (isset($values[$name])) {
			$rez = $values[$name];
		} else {
			$rez=false;	
		}
		
		return $rez;		
	}	
	
	function update(&$values) {
		GLOBAL $data;
		
		$name=$data->element_name;

		if (isset($values[$name.'_hour']) && isset($values[$name.'_minute'])) {	
			$rez = $values[$name.'_hour'].':'.$values[$name.'_minute'].':00';
		} else {
			$rez = false;
		}
		
		return $rez;			
	}		
	
	function delete() {
		
	}
	
	function makeFormElement($value=null) {
		GLOBAL $parser;
		GLOBAL $LIST_HOURS;			
		
		$form_params = $parser->element_params['form'].' '.$parser->fields_global_params;
		
		if (!isset($value) || $value=='') {
			$value=date('H:i');
		}
		
		$value_time=strtotime($value);
		$value=array();
		$value[hour]=date("H",$value_time);
		$value[minute]=date("i",$value_time);

		
		//HOUR
		$parser->tpl->setCurrentBlock("form_hour_row");
		$range = $LIST_HOURS;
		foreach ($range as $hour=>$hour_name) {
			$hour = sprintf("%02d", $hour);
			$parser->tpl->setVariable(array(
				'CAPTION'	=> $hour_name,
				'VALUE'		=> $hour,
				'SELECTED'	=> ($hour==$value[hour])?' selected':null				
			));
			$parser->tpl->parseCurrentBlock();
		}
		
		$parser->tpl->setCurrentBlock("form_hour");
		$parser->tpl->setVariable(array(
			'NAME'		=> $parser->element_name,
			'PARAMS'	=> $form_params
		));
		$parser->tpl->parseCurrentBlock();
		
		
		//MINUTE
		$parser->tpl->setCurrentBlock("form_minute_row");
		$range = range(0, 59);
		foreach ($range as $minute) {
			$minute = sprintf("%02d", $minute);
			$parser->tpl->setVariable(array(
				'CAPTION'	=> $minute,
				'VALUE'		=> $minute,
				'SELECTED'	=> ($minute==$value[minute])?' selected':null				
			));
			$parser->tpl->parseCurrentBlock();
		}
				
		$parser->tpl->setCurrentBlock("form_minute");
		$parser->tpl->setVariable(array(
			'NAME'		=> $parser->element_name,
			'PARAMS'	=> $form_params
		));
		$parser->tpl->parseCurrentBlock();
		
		
		$parser->tpl->setCurrentBlock("form");
		$parser->tpl->setVariable(array(
			'NAME'		=> $parser->element_name
		));
		$parser->tpl->parseCurrentBlock();
		return trim($parser->tpl->get());
	}
	
	function makeElementView($value=null) {
		GLOBAL $parser;
		$format=(isset($parser->element_params[format]))?$parser->element_params[format]:TIME_FORMAT;
		
		// get current time if value is null 
		if (!isset($value) || $value=='') {
			$value=date('H:i');
		}
		
		return formatTime($value,$format);
	}
	
}
?>