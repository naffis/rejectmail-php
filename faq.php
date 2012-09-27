<?
require_once('init/init.php');

$CMailModule->ClearSettings();

$tpl->load(true,true);
$aRows = $db->select('','','','faq');
$i = 0;

foreach ($aRows as $key=>$value ){	
	$aRows[$key]['anchor'] = 'xxx'.$i;	
	$i++;
}

$parser->parseList($aRows,'linklist');
$parser->parseList($aRows,'questionlist');
$app->output(array(
			'TITLE'	=> 'RejectMail.com - Free Public Send and Receive Email',
			'OPEN_COMMENT'	=> '<!--',
			'CLOSE_COMMENT'	=> '-->'
			));
?>