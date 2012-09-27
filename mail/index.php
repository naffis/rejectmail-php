<?
include( SYS_ROOT.'mail/'.'Mail.php'); 
include( SYS_ROOT.'mail/'.'Mail/mime.php'); 


$crlf = "\r\n"; 
//'Message-ID'				=>'<456623445.23440050414133606@inbox.ru>'	,


$mime = new Mail_mime($crlf); 

$mime->setTXTBody($text); 
//var_dump($_SESSION['upload_files']);
if (isset($_SESSION['upload_files']) && (gettype($_SESSION['upload_files']) == 'array'))	
		foreach ($_SESSION['upload_files'] as $key => $value) {
			//var_dump($value);
			if ($value['FILENAME'] != NULL) {
				//var_dump($value);
				@rename($value['TMP_NAME'],SYS_ROOT.FILE_PATH.'/attachment/'.$value['FILENAME']);
				$mime->addAttachment(SYS_ROOT.FILE_PATH.'/attachment/'.$value['FILENAME']);
				@unlink(SYS_ROOT.FILE_PATH.'/attachment/'.$value['FILENAME']);
				$_SESSION['upload_files'][$key] = NULL;
			}
		}
		
		
$body = $mime->get();
$hdrs = $mime->headers($hdrs);

$mail =&Mail::factory('mail'); 

$mail->send($to, $hdrs,$body ); 


?>