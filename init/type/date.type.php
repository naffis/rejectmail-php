<?
// Class date
// v 3.0.

class Tdate {
	
	function update(&$values) {
		GLOBAL $data;
		$name=$data->element_name;
		if (isset($values[$name.'_year']) && isset($values[$name.'_month']) && isset($values[$name.'_day'])) {	
			$rez = $values[$name.'_year'].'-'.$values[$name.'_month'].'-'.$values[$name.'_day'];
		} else if (isset($values[$name])) {
			$rez = $values[$name];
		} else {
			$rez = false;
		}
		
		return $rez;	
	}	

	function delete() {
		
	}	
	
	function insert(&$values) {
		GLOBAL $data;
		
		$name=$data->element_name;

		if (isset($values[$name.'_year']) && isset($values[$name.'_month']) && isset($values[$name.'_day'])) {	
			$rez = $values[$name.'_year'].'-'.$values[$name.'_month'].'-'.$values[$name.'_day'];
		} else if (isset($values[$name])) {
			$rez = $values[$name];		
		} else {			
			$rez = false;
		}
		
		return $rez;			
	}
	
	function makeFormElement($value=null) {
		GLOBAL $parser;
		
		$form_params = $parser->element_params['form'].' '.$parser->fields_global_params;

		
		// get current time if value is null 
		if (!isset($value)) $value=date('Y-m-d');
		$value_time=strtotime($value);
		
		$value=array();
		$value['year']=date("Y",$value_time);
		$value['month']=date("m",$value_time);
		$value['day']=date("d",$value_time);
		
		// Get Year Range
		$curr_year = date('Y');
		$year_range=(isset($parser->element_params['year_range']))?$parser->element_params['year_range']:100;
		if(isset($parser->element_params['min_year'])&&isset($parser->element_params['max_year'])&&$parser->element_params['min_year']<$parser->element_params['max_year']) {
			$min_year = $parser->element_params['min_year'];
			$max_year = $parser->element_params['max_year'];
		} elseif(isset($parser->element_params['min_year'])) {
			$min_year = $parser->element_params['min_year'];
			$max_year = $parser->element_params['min_year']+$year_range;
		} elseif(isset($parser->element_params['max_year'])) {
			$min_year = $parser->element_params['max_year']-$year_range;
			$max_year = $parser->element_params['max_year'];
		} else {
			$min_year = $curr_year-$year_range;
			$max_year = $curr_year+$year_range;
		}
			
		$parser->tpl->setCurrentBlock("form_year_row");
		$range = range($min_year, $max_year);
		foreach ($range as $year) {
			$parser->tpl->setVariable(array(
				'CAPTION'	=> $year,
				'VALUE'		=> $year,
				'SELECTED'	=> (isset($value['year']) && $year==$value['year'])?" selected":null				
			));
			$parser->tpl->parseCurrentBlock();
		}
		
		$parser->tpl->setCurrentBlock("form_year");
		$parser->tpl->setVariable(array(
			'NAME'		=> $parser->element_name,
			'PARAMS'	=> $form_params
		));
		$parser->tpl->parseCurrentBlock();
		
		//MONTH
		GLOBAL $LIST_MONTH;
		$parser->tpl->setCurrentBlock("form_month_row");
		foreach ($LIST_MONTH as $key=>$caption) {
			$parser->tpl->setVariable(array(
				'CAPTION'	=> $caption,
				'VALUE'		=> $key,
				'SELECTED'	=> (isset($value['month']) && $key==$value['month'])?" selected":null				
			));
			$parser->tpl->parseCurrentBlock();
		}
		
		$parser->tpl->setCurrentBlock("form_month");
		$parser->tpl->setVariable(array(
			'NAME'		=> $parser->element_name,
			'PARAMS'	=> $form_params
		));
		$parser->tpl->parseCurrentBlock();
		
		//DAY
		$parser->tpl->setCurrentBlock("form_day_row");
		$range = range(1, 31);
		foreach ($range as $day) {
			$day = sprintf("%02d", $day);
			$parser->tpl->setVariable(array(
				'CAPTION'	=> $day,
				'VALUE'		=> $day,
				'SELECTED'	=> (isset($value['day']) && $day==$value['day'])?" selected":null				
			));
			$parser->tpl->parseCurrentBlock();
		}
			
		$parser->tpl->setCurrentBlock("form_day");
		$parser->tpl->setVariable(array(
			'NAME'		=> $parser->element_name,
			'PARAMS'	=> $form_params
		));
		$parser->tpl->parseCurrentBlock();				
			
		$parser->tpl->setCurrentBlock("form");
		$parser->tpl->setVariable(array('NAME'	=> $parser->element_name));
		$parser->tpl->parseCurrentBlock();
		return trim($parser->tpl->get());
	}
	
	function makeElementView($value) {
		GLOBAL $parser;
		$format=(isset($parser->element_params['format']))?$parser->element_params['format']:DATE_FORMAT;

		// get current date if value is null 
		if (!isset($value)) $value=date('Y-m-d');
		
		return formatDate($value, $format);
	}	

	
}



?>