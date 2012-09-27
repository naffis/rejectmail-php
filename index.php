<?
require_once('init/init.php');
$CMailModule->ClearSettings();
$tpl->load(true,true);
$result = $db->select('','id desc limit 0,5','','articles');

$parser->parseList($result,'indexlist');
$app->output(array(
			'TITLE'	=> 'RejectMail.com - Free Public Send and Receive Email',
			'OPEN_COMMENT'	=> '<!--',
			'CLOSE_COMMENT'	=> '-->'
			));
?>