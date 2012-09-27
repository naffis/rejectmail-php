<?
function before_insert_people($cond,$values) {
	GLOBAL $app;
	
	if ($values['name']=='admin') {
		$app->setError('ERRROOORRR');	
		return false;
	}
	

}
?>