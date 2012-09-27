<?	
// Module Mail
// 18.04.2005
// by Gisma gis2002@inbox.ru
//require_once(INIT_PATH.MODULE_PATH.'Mail.mod/Pear.php');
//require_once(ROOT_PATH.INIT_PATH.MODULE_PATH.'Mail.mod/Mail.php');
//require_once(ROOT_PATH.INIT_PATH.MODULE_PATH.'Mail.mod/Mail/mime.php');
define('REJECTMAIL_TABLE','email');
define('REJECTMAIL_ATTACHMENTS','attachments');
define('REJECTMAIL_ACCOUNT','accounts');
define('REJECTMAIL_PREFIX','rejectmail_');
define('aREJECTMAIL_ATTACH_LIST','upload_files');
$LIST_ORDER = array(
	'ASC'		=> 'ASC',
	'DESC'		=> 'DESC'
);
$DATA_REJECTMAIL_DOMAIN_LIST = array(
	'table'	=> 'domains',
	'order'	=> 'id',
	'fields'	=> array(	
		'name'		=> 'input'
	)
);
$DATA_REJECTMAIL_ATTACHMENT = array(
	'table'		=>	'_x_',	
	'fields'	=>	array(			
		'attachment_filename' => 'input',
		'attachment_body'=> 'file_attachment'
		)
);

$FILE_ATTACHMENT = array(
	'path'		=> 'attachment/',
	'max_size'  => 10000

);
$DATA_REJECTMAIL = array(
	'table'	=>	REJECTMAIL_TABLE,
	'order'	=>  'email_id',
	'fields'=>	array(
		'email_id'		=>	'hidden',
		'email_name'	=>  'hidden',
		'email_to'		=>  array(
			'type'			=> 'input',
			'check'			=> 'email',			
			'form'			=>	'class="send" style="width:368px"'
			),
		'email_cc'		=>  array(
			'type'			=> 'input',			
			'check_msg'		=> 	'Please fill cc field',
			'form'			=>	'class="send" style="width:368px"'
		),
		'email_from'	=>  array(
			'type'			=>'input',
			'check'			=>'[A-z0-9_\.]',
			'form'			=>	'class="send"'
			),
		'email_domain'	=>  array(	
			'type'		=>	'list_def_domains',
			'form'		=>	'class="send" style="width:150px"'
			),
		'email_subject'	=>  array(
			'type'			=>'input',
			'form'			=>	'class="send" style="width:368px"'
			),
		'email_body'	=>  array(
			'type'			=> 'text',			
			'form'			=> 'class="mail"'
		),
		'email_header'	=>  'hidden', 
		'email_date'	=>  array(
			'name'	=> 'email_date',
			'type'	=> 'datetime',
			'min_year'	=> '1900',
			'max_year'	=> '2100'
			)
		)
);

// Paging info
$PAGING_REJECTMAIL = array (
	'type'				=> '2',
	'items_per_page'	=> '3', // if 0 - return all values
	'max_pages'			=> '200', // if 0 - show all pages
	'name'				=> 'Main.'
);

function rejectmail_sendmailform() {
	// vivodit formu
	GLOBAL $data,$parser,$tpl;	
	$data->set('rejectmail');		
		$result = $data->makeCustomForm(($_POST['rejectmail'] ==1)?$_POST:'','modules/sendmail','sendmail');		
		
	$attach_list = $parser->makeList($_SESSION[aREJECTMAIL_ATTACH_LIST],'modules/sendmail','AATTACHMENTS');		
	$result = $result.$attach_list;	
	$data->set('rejectmail');
	$result .= $data->makeCustomForm('','modules/sendmail','sendmail_part2');
	
	return $result;
}

function rejectmail_checkform()
{
	GLOBAL $data;
	$data->set('rejectmail_check');
	return $data->makeCustomForm('','modules/sendmail','getmail');
}

//////////////////////////////////////////////////////////////////////////
//////////////////////////Mail Module class///////////////////////////////
//////////////////////////////////////////////////////////////////////////
//
//  Main Functions
//  Account_Check_Inc - increments count of visits to current mailbox
//  ClearSettings - clears settings of object
//  ClearSearch - clears search variable
//  Get_Value - get database settings for object
//  GetAttachment - return attachment file by id
//   - return attachment list 
//  GetStatus - return current status of object 
//  GetCurrentMailDomain - return current mail domain-name
//  GetCurrentMailName - return current mail name
//  GetMail - get info about email by email_id
//  MakeRss - return RSS-code of mailbox
// 	PrepareHTML - prepare and parse html
//  Select - select list of message from mailbox
//  Set_Value - set database settings for object
//  SendSimpleMail - sends simple mail (plaint text witch attachments)
//  SetMailSettings - reset settings of object
//
//////////////////////////////////////////////////////////////////////////
class CMailModule
{
	var $sStatus; // peremennya hranit strokovoe oboznachenie statusa objecta
	var $sOrder; // peremennaya otvechaet za order viborki dannih v baze
	var $sName;  // imya yaschika
	var $sDomain; // domain name
	var $sSearch; // search string;
	var $nCheckedTimes;
	var $Mail;
	var $Mime;
	
/** Constructor
  * @return  void
*/	
function CMailModule()
{
	if (is_array($_SESSION)){
		// po defoltu vse znachenya pzapolnyautsya iz sessii
		$this->sName 		= $_SESSION[REJECTMAIL_PREFIX.'email_name'];
		$this->sDomain 		= $_SESSION[REJECTMAIL_PREFIX.'email_domain'];
		$this->sOrder 		= $_SESSION[REJECTMAIL_PREFIX.'email_order'];
		$this->nCheckedTimes = $_SESSION[REJECTMAIL_PREFIX.'count'];
		$this->sSearch 		= $_SESSION[REJECTMAIL_PREFIX.'email_search'];
		
	}
	
}	
/**
	Set mail settings search atributes, select messages order
	* @return void	
	* @param array input
	* @param string domain
	* @param string order
*/
function SetMailSettings($input)
{	
	if (is_array($input)){
		if (isset($input['email_name'])) {
			$this->sName =  $input['email_name'];
			 $_SESSION[REJECTMAIL_PREFIX.'email_name'] =  $input['email_name'];
		}
		if (isset($input['email_domain'])) {
			$this->sDomain = $input['email_domain'];	
			$_SESSION[REJECTMAIL_PREFIX.'email_domain'] =  $input['email_domain'];
		}
		
		if (isset($input['email_order'])) {
			$this->sOrder = $input['email_order'];
			$_SESSION[REJECTMAIL_PREFIX.'email_order'] =  $input['email_order'];
		}
		if (isset($input['email_search'])) {
			$this->sSearch = $input['email_search'];
			$_SESSION[REJECTMAIL_PREFIX.'email_search'] = $input['email_search'];
		}
	}
	
}	
/**
 * Selects maylov from base from base
 * 
 * @return array
 */	
function Select($nopaging=false)
{
	global  $db,$app,$PAGING_REJECTMAIL;						
	$result = $this->Get_Value(array("messages_per_page"));	
	$PAGING_REJECTMAIL['items_per_page'] = $result['messages_per_page'];		
	if (($this->sName == NULL) || ($this->sDomain == NULL)) { 
		$this->sStatus = 'Nothing to display';
		return NULL;
	}
	if ($this->sSearch == "") {
		$totalpages = $db->get(array(
			'`email_name`'	=>	$this->sName,
			'`email_domain`'	=>	$this->sDomain,
			),'count(email_id)','email');	
	} else {	
		$totalpages = $db->get("`email_name` = '".addslashes($this->sName)."' and `email_domain`='".addslashes($this->sDomain).
		"' and (`email_body` Like '%".addslashes($this->sSearch)."%' or `email_subject`Like '%".addslashes($this->sSearch)."%')",'count(email_id)',REJECTMAIL_TABLE);		

	}
	if ($nopaging == false) {
		$app->setPaging('rejectmail');			
		$app->setPagingTotal($totalpages);
	}
	if ($this->sSearch == "") {		
		$result = $db->select(array(
			'email_name'	=>	$this->sName,
			'email_domain'	=>	$this->sDomain,
			),addslashes($this->sOrder),"email_id,email_from,email_subject,email_date",REJECTMAIL_TABLE);	
	} else { 				
		$result = $db->select("`email_name` = '".addslashes($this->sName)."' and `email_domain`='".addslashes($this->sDomain).
		"' and (`email_body` Like '%".addslashes($this->sSearch)."%' or `email_subject`Like '%".addslashes($this->sSearch)."%')",'email_id','email_id,email_from,email_subject,email_date',REJECTMAIL_TABLE);		
	}	
	if (is_array($result)) {
		$this->sStatus = '';
	} else {
		$this->sStatus = 'Nothing to display';
	}	
	$this->Account_Check_Inc();
	return  $result;
}
/**
 * Get email from database 
 * @param int $nID
 * @return mixed
*/
function GetMail($nID) {		
	global $db;
	
	$aResult = $db->get(array('email_id' => $nID),'*',REJECTMAIL_TABLE);
		
	return $aResult;
}
/**
 * Get attachemnts from database for email by email_id
 * @param int $nID
 * @return mixed
*/
function GetAttachmentList($nID) {		
	global $db;
	
	// formiruem tablizu ssilok i imen 	
	
	$aResult = $db->select(array('email_id' => $nID),'attachment_id','attachment_id,attachment_filename',REJECTMAIL_ATTACHMENTS);	
	return $aResult;
}
/**
 * Get attachemnts from database by attachemn_ind
 * @param int $nID
 * @return mixed
*/
function GetAttachment($nID) {		
	global $db;	
	
	$aResult = $db->get(array('attachment_id' => $nID),'attachment_filename,attachment_body',REJECTMAIL_ATTACHMENTS,'attachment_body');				
	return $aResult;
}

/**
 * add mail to database
 * 
 * @param array aInput - values to add
 * @return mixed
 */	
function Insert($aInput)
{
	
	global $db;
	if ($aInput['email_date'] == NULL){		
		$aInput['email_date'] = date("Y:m:d H:i:s");
	}	
	$db->insert(array(

		'email_name'		=> substr($aInput['email_to'],strpos($aInput['email_to'],'@')),
		'email_domain'		=> $aInput['email_domain'],
		'email_from'		=> $aInput['email_from'],
		'email_to'			=> $aInput['email_to'],
		'email_cc'			=> $aInput['email_cc'],
		'email_subject'		=> $aInput['email_subject'],
		'email_body'		=> $aInput['email_body'],
		'email_header'		=> $aInput['email_header'],
		'email_date'		=> $aInput['email_date']
		),'email');	
}

/**
 * deletes mail from email database
 *
 * 
 * @return mixed
 */	
function Delete()
{
}
/**
 * sends mail to recipient list
 *
 * 
 * @return mixed
 */	

/**
 * sends mail to recipient list
 *
 * 
 * @return mixed
 */	
function SendMail()
{
}
/**
 * makes RSS document 
 *
 * 
 * @return string
 */	

/**
 * makes RSS document 
 *
 * 
 * @return string
 */	
function MakeRSS()
{
	//
	@header("Content-Type: text/xml; charset=iso-8859-1");
	$result = '<?xml version="1.0" ?>'."\n";
	$result .= "<rss version=\"2.0\">\n";	
	$result .= "<channel>\n";
	$result .= "<title> Message list for ".htmlspecialchars($this->sName).'@'.htmlspecialchars($this->sDomain)." </title>\n";	
	$result .= "<link>".htmlspecialchars($_SERVER['SERVER_NAME']).htmlspecialchars($_SERVER['REQUEST_URI'])."</link>\n";
	$result .= "<language>en</language>";
	$result .= "<description> Account checked {$this->nCheckedTimes} times</description>";
	$aRows = $this->Select(true);
	if ($aRows) {
		$i = 0;
		foreach ($aRows as $key=>$value) {
			$result .= "<item>\n";
			$result .= "<title> ". htmlspecialchars($value['email_from']) ." </title>\n";
			$result .= "<description> Date :{$value['email_date']} Subject : ".htmlspecialchars($value['email_subject'])." </description>\n";
			$result .= "<pubDate> {$value['email_date']}</pubDate>\n";
			$result .= "</item>\n";
			$i ++;
		}
	} else {
	//	print "<item>\n	<title>Sorry</title>\n<description>mailbox is empty</description> </item>";
	}
	$result .= "</channel>\n";
	$result .= "</rss>\n";
	return $result;
}

/**
	* Sends simple mail 
	* @param array aParametres
	* @return void
*/
function SendSimpleMail($aParametres){
	$hdrs = array(
		'CC '	=>	$aParametres['email_cc'],
		'From'	=>	$aParametres['email_from'].'@'.$aParametres['email_domain'],		
		'Subject'=> $aParametres['email_subject']);		
	$text = $aParametres['email_body'];
	$to = $aParametres['email_to'];
	include(SYS_ROOT."mail/index.php");
	//$data->set("rejectmail");	
	
	//$this->Mime->setTXTBody('55555555555555555555555555');
	//$this->Mime->setHTMLBody('');
	/*$hdrs = array(
		'CC '	=>	$aParametres['email_cc'],
		'From'	=>	$aParametres['email_from'].'@'.$aParametres['email_domain'],		
		'Subject'=> $aParametres['email_subject']);	
	$this->Mime->setTXTBody($aParametres['email_body']);
	if (isset($_SESSION[aREJECTMAIL_ATTACH_LIST]) && (gettype($_SESSION[aREJECTMAIL_ATTACH_LIST]) == 'array'))
		var_dump($_SESSION[aREJECTMAIL_ATTACH_LIST]);
		foreach ($_SESSION[aREJECTMAIL_ATTACH_LIST] as $value) {
			if ($value['FILENAME'] != NULL) {
				@rename($value['TMP_NAME'],SYS_ROOT.FILE_PATH.'/attachment/'.$value['FILENAME']);
				$this->Mime->addAttachment(SYS_ROOT.FILE_PATH.'/attachment/'.$value['FILENAME']);
				@unlink(SYS_ROOT.FILE_PATH.'/attachment/'.$value['FILENAME']);
			}
		}*/
	/*$body = $this->Mime->get();
	$hdrs = array(
		'From'		=>	'gisma@rejectmail.com',
		'Subject'	=>	'555');*/
	//$to = $aParametres['email_to'].'@'.$aParametres['email_domain'];
/*	$to = 'gisma2002@yahoo.com';
		
	$mail =&Mail::factory('mail'); 	
	var_dump($hdrs);
	var_dump($body);
	$mail->send($to, $hdrs,$body ); 
	unset($_SESSION[aREJECTMAIL_ATTACH_LIST]);		*/
}
/////////////////////////////////////////////////////////////////////////////////
//////////////////////////////   Additional functions   /////////////////////////
/////////////////////////////////////////////////////////////////////////////////
/**
	* Clears all settings 
	* @return void
*/
function ClearSettings(){
	$this->nCheckedTimes = 0;
	$this->sSearch = '';
	$this->sDomain = '';
	$this->sName = '';
	$this->sOrder = '';	
	$_SESSION[REJECTMAIL_PREFIX.'email_name'] =  '';
	$_SESSION[REJECTMAIL_PREFIX.'email_domain'] = '';		
	$_SESSION[REJECTMAIL_PREFIX.'email_order'] =  '';
	$_SESSION[REJECTMAIL_PREFIX.'email_search'] = '';

}
/**
	* clear search variable and enable to show message list, but not search list
	* @return void
	* @param  void
*/

function ClearSearch(){
	$this->sSearch = '';
	$_SESSION[REJECTMAIL_PREFIX.'email_search'] = '';
	return ;
}
/**
	* get a specific values from config_value
	* @return array of values
	* @param  array $values
*/
function Get_Value($values){
	global $db;	
	if (!is_array($values))
		return array();	
	$result = array();	
	
	foreach ($values as $value){
		$result[$value] = $db->get('`cond` = "'.$value.'"','value','config_value');		
	}
	
	return  $result;
}
/**
	* parse and prepare html tags 
	* @return string 
	* @param int  $email_id
*/

function PrepareHTML($email_id){
	global $db;
	$sResult = '';
	$aMail_info = $this->GetMail($email_id);
	if (is_array($aMail_info)) {
		
		$sResult = $aMail_info['email_body'];
		$sResult = preg_replace("/<script([\s]+src=['\"]?[A-z0-9;\\:_\/\.,]+['\"]|[\s]+language=['\"]?[A-z0-9;:_\.,]+['\"]){0,2}\s*>/",'',$sResult);
		$sResult = preg_replace("/<[\s]*\/script[\s]*>/",'',$sResult);		
		preg_match_all("/<[\s]*img[^>]*src=([\"']?([A-z09\.,:;]+)[\"'])[^>]*>/",$sResult,$aResultFindingImages);		
		foreach ($aResultFindingImages[2] as $key ) {
			$nID = $db->get(array(
				'email_id'		=>	$email_id,
				"attachment_filename like "
			),'attachment_id',REJECTMAIL_ATTACHMENTS);				
			$sResult = str_replace('src='.$aResultFindingImages[1][key($aResultFindingImages[1])],'src=details.php?action=attachment&attachment_id='.$nID,$sResult);
		}
	}
	return $sResult;
}

/**
	* set a specific values from config_value
	* @return void
	* @param  array $values
*/

function Set_Value($values){
	global $db;
	if (!is_array($values))
		return array();
	$result = array();
	foreach ($values as $key	=> $value){						
		$db->update('cond="'.addslashes($key).'"',array(
			'value '		=>	$value
			),'config_value'
		);
	}
}

function Account_Check_Inc(){
	
	global $db;
	
	// get account info
	
	$account =  $this->sName.'@'.$this->sDomain;
	$result = $db->select(array(
		"account"	=>	$account),"","",REJECTMAIL_ACCOUNT);					
	
	if 	(!$result) {		
		// sozdanie novoy zapisi
		$db->insert(array(
			"account"		=>	$account,
			'count'			=>  1,
			'session_id'	=> session_id()),REJECTMAIL_ACCOUNT);			
		$this->nCheckedTimes = 1;
		
	} else {	
		if ($result[0]['session_id'] != session_id())	{
			// uvelichenie schetchika 		
			
			$db->update('id = '.$result[0]['id'],array(
				'count'			=> $result[0]['count'] + 1 ,
				'session_id'	=> session_id()),REJECTMAIL_ACCOUNT );
			
			$this->nCheckedTimes = $result[0]['count'] + 1;
		} else {
			$this->nCheckedTimes = $result[0]['count'];
		}
	}
	$_SESSION[REJECTMAIL_PREFIX.'count'] = $this->nCheckedTimes;
}
/** 
	* Vozvrashaet strokovoe oboznachenie statusa objekta
	* @return string
*/
function GetStatus(){
	return $this->sStatus;	
}
function GetCurrentMailName() {
	return $this->sName;
}
function GetCurrentMailDomain() {
	return $this->sDomain;
}
}
$CMailModule = new CMailModule();
$domain_list = $CMailModule->Get_Value(array('domainslist_order'));
global $db;
$aRows = $db->select('','name '.$domain_list['domainslist_order'],'name','domains');
//////////////////////////////////
$values =	$CMailModule->Get_Value(array('domainslist_order'));
$DATA_REJECTMAIL_CHECK = array(
	'table'	=> REJECTMAIL_TABLE,
	'order'	=> 'name '.$sOrder,
	'fields'=> array(
		'email_name'		=>	array(
			'type'		=>	'input',
			'form'		=>	'type="text" class="send" style="width:150px"'),
		'email_domain'		=>  array(
			'type'		=>	'list_def_domains',	
			'form'		=>	'class="send" style="width:120px"'
		)
	)
	);
$LIST_DOMAINS = array();
foreach ( $aRows as $key=>$value){
	$LIST_DOMAINS[$value] = $value;
}
unset($domain_list);
?>