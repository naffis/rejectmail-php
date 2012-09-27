<?php 
	function random(){
		return rand(0,10000);
	}
	function generate_spam_check() {
		global $CChecker;
		$CChecker->Generate();
	}
	class SPAM_CHECKER {
		function Check($spam_checker) {
			if ($spam_checker == $_SESSION['SPAM_CHECKER_TEXT']) {
				$bResult = true;
			}	 else {
				$bResult = false;
			}
			$_SESSION['SPAM_CHECKER_TEXT'] = substr(md5(time()),4,6);					
			return $bResult;
		}
		function Generate() {			
			$im = imagecreate(100,50);
			$background_color = imagecolorallocate($im, 200, 200, 200);
			$text_color = imagecolorallocate($im, 233, 14, 91);
			
			
			$text_color = imagecolorallocate($im, 233, 14, 91);
			$_SESSION['SPAM_CHECKER_TEXT'] = substr(md5(time()),4,6);					
			
			for ($i = 0;$i < rand(15,20);$i++) {
				ImageLine($im, rand(0,100), rand(0,50), rand(0,100), rand(0,50), imagecolorallocate($im, rand(100,150),rand(100,150), rand(100,150))); 
			}
			
			$i = 1;
						
			for ($i=0;$i < strlen($_SESSION['SPAM_CHECKER_TEXT']);$i++) {
				
				$text_color = imagecolorallocate($im, rand(10,50),rand(10,50), rand(10,50));
				
				imagettftext($im, rand(12,15), rand(0,20),15 + $i * 15,rand(20,35), $text_color, "arial.ttf",		
					$_SESSION['SPAM_CHECKER_TEXT'][$i]);
			}	
			header("Content-type: image/jpg");  			
			imagejpeg($im);
			imagedestroy($im);
		}
	}
	$CChecker = new SPAM_CHECKER();
	
?>
