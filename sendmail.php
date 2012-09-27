<?
function get_stripped_filename($filename){
	
	$aPathInfo =  pathinfo($filename);	
	$szResult = substr(basename($aPathInfo['basename'],'.'.$aPathInfo['extension']),0,15).'.'.$aPathInfo['extension'];	
	return $szResult;
	
}
require_once('init/init.php');

$CMailModule->ClearSettings();
//$parser->setAddVariables(array('TITLE'		=> 'Form'));
if ((isset($_POST['send_x'])) && ($CChecker->Check($spam_checker))){	
	// esli forma otoslana, to poslat dobavit v bazu i otoslat			
	$CMailModule->SendSimpleMail($_POST);		
	$tpl->load(true,true);	
	
	$aCC = split(',',$email_cc);
	if (gettype($aCC) == 'array') {
		foreach ($aCC as $key=>$value) {		
			$a_CC[$key]['_CC'] = $value;
		}		
	} else {
		$a_CC[0] = $_POST['email_from'];
	}
	$szCC = $parser->MakeList($a_CC,'sendmail','sendedññ');	
	$tpl->setVariable(array(
		'TO'		=>	$email_to,		
		'CC'		=>	$szCC
		));
	$app->output(array(
		'TITLE'	=> 'Your mail has been sended',
		'OPEN_COMMENT'	=> '<!--',
		'CLOSE_COMMENT'	=> '-->'
		));
	
} else {	
	
	if (((isset($_POST['send_x'])) && (!$CChecker->Check($spam_checker)))) {
		$app->setError('You entered an incorrect verification code. Please try again');	
	}	
	if (!isset($_SESSION[aREJECTMAIL_ATTACH_LIST])) { 
		$_SESSION[aREJECTMAIL_ATTACH_LIST] = array(1);
	}			
	
	if ($_FILES['file']['name'] != null) {		
		$nNewPosition = count($_SESSION[aREJECTMAIL_ATTACH_LIST]) + 1;
		move_uploaded_file($_FILES['file']['tmp_name'],SYS_ROOT.'files/attachment/'.basename($_FILES['file']['tmp_name']));
		$_SESSION[aREJECTMAIL_ATTACH_LIST][$nNewPosition]['FILENAME'] = $_FILES['file']['name'];
		$_SESSION[aREJECTMAIL_ATTACH_LIST][$nNewPosition]['FILENAME_OBREZ'] =  get_stripped_filename($_FILES['file']['name']);
		$_SESSION[aREJECTMAIL_ATTACH_LIST][$nNewPosition]['KEY'] = $nNewPosition;
		$_SESSION[aREJECTMAIL_ATTACH_LIST][$nNewPosition]['TMP_NAME'] = SYS_ROOT.'files/attachment/'.basename($_FILES['file']['tmp_name']);
	}
	if ($_FILES['file2']['name'] != NULL) {				
		$nNewPosition = count($_SESSION[aREJECTMAIL_ATTACH_LIST]) + 1;
		move_uploaded_file($_FILES['file2']['tmp_name'],SYS_ROOT.'files/attachment/'.basename($_FILES['file2']['tmp_name']));
		$_SESSION[aREJECTMAIL_ATTACH_LIST][$nNewPosition]['FILENAME'] = $_FILES['file2']['name'];
		$_SESSION[aREJECTMAIL_ATTACH_LIST][$nNewPosition]['FILENAME_OBREZ'] =  get_stripped_filename($_FILES['file2']['name']);
		$_SESSION[aREJECTMAIL_ATTACH_LIST][$nNewPosition]['KEY'] = $nNewPosition;
		$_SESSION[aREJECTMAIL_ATTACH_LIST][$nNewPosition]['TMP_NAME'] = SYS_ROOT.FILE_PATH.'/attachment/'.basename($_FILES['file2']['tmp_name']);
	}
	if (($delete != NULL) && ($delete_key != NULL)) {
		@unlink($_SESSION[aREJECTMAIL_ATTACH_LIST][$delete_key]['TMP_NAME']);
		unset($_SESSION[aREJECTMAIL_ATTACH_LIST][$delete_key]);
		
	}	
	$tpl->load(true,true);
	
	$tpl->setVariable(array('NOOP'	=>	''));	
	$app->output(array(
			'TITLE'	=> 'RejectMail.com - Free Public Send and Receive Email',
			'OPEN_COMMENT'	=> '<!--',
			'CLOSE_COMMENT'	=> '-->'
			));
	
}

?>