<?
require_once('init/init.php');
//$parser->setAddVariables(array('TITLE'		=> 'Form'));
$tpl->load(false,false);
$CMailModule->ClearSettings();;
$data->set('text_blocks');
$aResult = $data->get('id=1');
$text = $aResult['text'];
$tpl->setVariable(array(
	'TEXT'		=>		$text
	));
$app->output(array(
			'TITLE'	=> 'RejectMail.com - Free Public Send and Receive Email',
			'OPEN_COMMENT'	=> '<!--',
			'CLOSE_COMMENT'	=> '-->'
			));
?>