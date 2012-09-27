<?
// Class datetime
// v 3.0.

class Tdatetime {
	
	function update(&$values) {
		GLOBAL $data;
		
		$name=$data->element_name;
		if (isset($values[$name.'_year']) && isset($values[$name.'_month']) && isset($values[$name.'_day']) && isset($values[$name.'_hour']) && isset($values[$name.'_minute'])) {	
			$rez = $values[$name.'_year'].'-'.$values[$name.'_month'].'-'.$values[$name.'_day'].' '.$values[$name.'_hour'].':'.$values[$name.'_minute'].':00';
		} else {
			$rez = false;
		}
		
		return $rez;			
	}	
	
	function delete() {
		
	}	

	function insert(&$values) {
		GLOBAL $data;
		$name = $data->element_name;
		// if we have in values field with element name - uset it 
		if (isset($values[$name])) {
			$rez = $values[$name];			
		} else { // else use composite string from date, month etc...
			$rez = $values[$name.'_year'].'-'.$values[$name.'_month'].'-'.$values[$name.'_day'].' '.$values[$name.'_hour'].':'.$values[$name.'_min'].':'.'00';
		}
		return $rez;		
	}
	
	function makeFormElement($value=null) {
		GLOBAL $parser;
		
		$tpl_name=$parser->cur_tpl_name;
		
		$parser->setElement($parser->element_name,'date');
		$date=$parser->makeFormElement($value);		
		
		$parser->setElement($parser->element_name,'time');
		$time=$parser->makeFormElement($value);		
		
		$parser->loadTemplate($tpl_name);
		$parser->tpl->parseVariable(array(
			'DATE'	=> $date,
			'TIME'	=> $time
			),'form');
			
		$parser->setElement($parser->element_name,'datetime');
		
		return trim($parser->tpl->get());		
	}
	
	function makeElementView($value) {
		GLOBAL $parser;

		$format=(isset($parser->element_params[view_format]))?$parser->element_params[view_format]:DATE_FORMAT.' '.TIME_FORMAT;
		
		// get current time if value is null 
		if (!isset($value) || $value=='') {
			$value=date('Y-m-d H:i:s');
		}
		
		return formatDatetime($value,$format);	
	}	

	
}



?>