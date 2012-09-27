<?
require_once('init/init.php');
function get_stripped_filename($filename){
	
	$aPathInfo =  pathinfo($filename);	
	$szResult = substr(basename($aPathInfo['basename'],'.'.$aPathInfo['extension']),0,15).'.'.$aPathInfo['extension'];	
	return $szResult;
	
}
$tpl->load(false,false);
switch ($action){
case 'attachment':
 	$attachment = $CMailModule->GetAttachment($attachment_id); 	
 	if ($attachment == NULL) {
 		$app->jump("index.php");
 	}	  		
	header('Content-type: application/octet-stream');	
	header('Content-Disposition: attachment; filename="'.basename($attachment['attachment_filename']).'"'); 				
 	print($attachment['attachment_body']);
	
break;
case 'headers':
	$aMail_info = $CMailModule->GetMail($email_id);
	
	// Check for nulevoy resultat
	if (!$aMail_info ) {
		$app->jump('index.php');
	}

	// output headers	
	header('Content-type: text/plain'); 	
 	header('Content-Transfer-Encoding: quoted-printable');
 	print('<pre>');
 	print htmlspecialchars($aMail_info['email_header']);	
 	print('</pre>');
		
break;	
case 'raw':
	$aMail_info = $CMailModule->GetMail($email_id);
	
	// Check for nulevoy resultat
	if (!$aMail_info ) {
		$app->jump('index.php');
	}

 	// output raw info
 	header('Content-type: text/plain'); 	
 	header('Content-Transfer-Encoding: quoted-printable');
 	print('<pre>');
 	print htmlspecialchars($aMail_info['email_header']."\r\n\r\n".$aMail_info['email_body']);
 	print('</pre>');
 	
break;
default:	
	$aMail_info = $CMailModule->GetMail($email_id);
	
	// Check for nulevoy resultat
	if (!$aMail_info ) {
		$app->jump('index.php');
	}

    // gets attacments
    
    $aAttachment = $CMailModule->GetAttachmentList($email_id);        
	
    if (gettype($aAttachment) == 'array') {
    	
	    $aRows = array();
	    foreach ($aAttachment as $key=>$value)
	    {		    		
	    	$aRows[$key]['ATTACHMENT_FILENAME'] = get_stripped_filename($value['attachment_filename']);
	    	$aRows[$key]['ATTACHMENT_ID'] = $value['attachment_id'];
	    	
	    }		
	    $szAttachment = $parser->makeList($aRows,'details_attachmentlist','attachmentlist');        
    }
    // sets_variables
    $tpl->setVariable(array(
    	'MAIL_NAME'		=>	htmlspecialchars($CMailModule->GetCurrentMailName()),
    	'DOMAIN_NAME'	=>	htmlspecialchars($CMailModule->GetCurrentMailDomain()),
    	'EMAIL_ID'		=>  htmlspecialchars($aMail_info['email_id']),
    	'EMAIL_DATE'	=>	htmlspecialchars($aMail_info['email_date']),
    	'EMAIL_FROM'	=>	htmlspecialchars($aMail_info['email_from']),
    	'EMAIL_TO'		=>	htmlspecialchars($aMail_info['email_to']),
    	'EMAIL_CC'		=>	htmlspecialchars($aMail_info['email_cc']),
    	'EMAIL_BODY'	=>	($aMail_info['email_body']),
    	'ATTACHMENTS'	=>	$szAttachment)
    );    
   
	$app->output(array(
			'TITLE'	=> 'RejectMail.com - Free Public Send and Receive Email',		
			'COMMENT2_IN'	=> '<!--',
			'COMMENT2_OUT'	=> '-->'
			));
	
}
?>