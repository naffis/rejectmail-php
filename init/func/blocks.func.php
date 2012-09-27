<?
function banner() {
	global $db,$parser,$IMAGE_BANNER;
	$aBanner = $db->get("id=1",'','banners');
	
	$aBanner['path'] = FILE_PATH.$IMAGE_BANNER['path'].'1_2';
	$aBanner['link1'] = $aBanner['link'];	
	
	$szResult = $parser->makeView($aBanner,'global/banner','banner');
	
	return $szResult;
}
function content($id) {
	GLOBAL $db;
	return $db->get("id='$id'",'text','content');	
}
?>