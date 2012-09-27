<?
require_once('init/init.php');

/**
Delaet viborku messages v baze dannih
  * @return void
  	
*/
function Message_List(){
	// delaet viborku iz bazi dannih
// select vsem pisem chto prishli 
global  $tpl,$CMailModule,$parser,$app;

$aResult = $CMailModule->Select();

if (is_array($aResult)){
	$i = 0;
	foreach ( $aResult as $key=>$value){										
		
		$aRows[$i] =  array(			
			'ROW_CLASS'		=>  ($i %2==0 ? 'class="bgmail"':''),
			'EMAIL_ID'		=> 	$value['email_id'],
			'EMAIL_DATE'	=>	$value['email_date'],
			'EMAIL_FROM'	=>	$value['email_from'],
			'EMAIL_SUBJECT'	=>  $value['email_subject']);
		$i++;
	}	
	
	$paging = $parser->makePaging();	
	//print $paging.'x';
	$list = $parser->makeList($aRows,'mailbox_list','message_list');
	// zdes vivod resultov	
	
	$tpl->setVariable(array(
		'PAGING'		 => $paging,
		'LIST'			 => $list,
		'ACCOUNT_CHECKED'=> $CMailModule->nCheckedTimes
		)
	);
} else {
	// zdes vivodytsya spisok eli resultov netu
	$tpl->setVariable(array(
		"NO_MESSAGES_IN"			=>		"",
		'NO_MESSAGES_OUT'			=>		""));
}
return ;
}

/**
 * function add/remove DESC to order links
 * @return  void 
*/
function SetupOrderLinks() {
	global $CMailModule,$tpl;
	$result  = array();
	
	if ($CMailModule->sOrder == 'email_date' ) {
		$result['ORDER_DATE'] = 'email_date DESC';
	} else {
		$result['ORDER_DATE'] = 'email_date';
	}
	
	if ($CMailModule->sOrder == 'email_from' ) {
		$result['ORDER_FROM'] = 'email_from DESC';
	} else {
		$result['ORDER_FROM'] = 'email_from';
	}
	
	if ($CMailModule->sOrder == 'email_subject' ) {
		$result['ORDER_SUBJECT'] = 'email_subject DESC';
	} else {
		$result['ORDER_SUBJECT'] = 'email_subject';
	}
	
	$tpl->setVariable($result);
}
/// Main programm nah

switch ($action) {
 	case "makeRSS":
 		$CMailModule->SetMailSettings($_REQUEST);
		print $CMailModule->MakeRSS();	
 		break;  	
 	case "clearsearch": 	 	
 		$CMailModule->ClearSearch(); 		
 		$tpl->load(true,true);
		// ustanovka parametrovà		
		$CMailModule->SetMailSettings($_REQUEST);
		$tpl->setVariable(array(
			"NO_MESSAGES_IN"			=>		"<!--",
			'NO_MESSAGES_OUT'			=>		"-->")
		);

		Message_List();
		SetupOrderLinks();
		$tpl->setVariable(array(		
			'STATUS'		=> $CMailModule->GetStatus(),
			'MAIL_NAME'		=> $CMailModule->sName,
			'DOMAIN_NAME'	=> $CMailModule->sDomain,
			'ACCOUNT_CHECKED'=> $CMailModule->nCheckedTimes)	);
			
		$app->output(array(
					'TITLE'	=> 'Form',
					'RSS'	=> '<link rel="alternate" type="application/rss+xml" title="Rejectmail : message list" href="http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'?email_name='.$CMailModule->sName.'&email_domain='.$CMailModule->sDomain.'&action=makeRSS" />'
					)); 	 	
 		break;
 	default:
		$tpl->load(true,true);
		// ustanovka parametrovà				
		$CMailModule->SetMailSettings($_REQUEST);
		$tpl->setVariable(array(
			"NO_MESSAGES_IN"			=>		"<!--",
			'NO_MESSAGES_OUT'			=>		"-->")
		);
		Message_List();
		SetupOrderLinks();		
		$tpl->setVariable(array(		
			'STATUS'		=> $CMailModule->GetStatus(),
			'MAIL_NAME'	=> $CMailModule->sName,
			'DOMAIN_NAME'	=> $CMailModule->sDomain,
			'ACCOUNT_CHECKED'=> $CMailModule->nCheckedTimes)	);		
		$app->output(array(
					'TITLE'	=> 'RejectMail.com - Free Public Send and Receive Email',
					'RSS'	=> '<link rel="alternate" type="application/rss+xml" title="Rejectmail : message list" href="http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'?email_name='.$CMailModule->sName.'&email_domain='.$CMailModule->sDomain.'&action=makeRSS" />'
					));
 	
 		break;
 } 

?>	 