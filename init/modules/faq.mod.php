<?
// FAQ module v. 2.0

define('FAQ_TYPE',1);
// FAQ_TYPE
// 1 - simple faq 
// 2 - advanced

if (FAQ_TYPE ==1) {	
	$DATA_FAQ = array (
		'table'		=> 'faq',
		'order'		=> 'oder',
		'fields'	=> array (
			'id'		=> 'hidden',
			'oder'		=> 'hidden',
			'question'	=> 'text_small',
			'answer'	=> 'text_small'	
		),
		'list_fields'	=> 'question'
	);	
	
} else {
	
	$GLOBALS['DATA_FAQ_SECTION'] = array (
		'table'		=> 'faq_sections',
		'order'		=> 'oder',
		'fields'	=> array (
			'id'		=> 'hidden',
			'parent_id'	=> 'category_faq',
			'parents'	=> 'hidden',
			'oder'		=> 'hidden',
			'name'		=> 'input'
		)
	);	
		
	$GLOBALS['DATA_FAQ'] = array (
		'table'		=> 'faq',
		'order'		=> 'oder',
		'fields'	=> array (
			'id'		=> 'hidden',
			'parent_id'	=> 'category_faq',
			'parents'	=> 'hidden',
			'oder'		=> 'hidden',
			'question'	=> 'text_small',
			'answer'	=> 'text_small'	
		),
		'list_fields'	=> 'question'
	);		
	
	$GLOBALS['TREE_FAQ'] = array(
		'name'		=> 'FAQs',
		'max_level'	=> 1,
		
		'node'		=> 'faq_section',
		'point'		=> 'faq'
	);	
	
	
}


/**
 * on success returns html code of parsed faq
 *	
 * @return string
 */
function makeFaqList() {
	GLOBAL $data;
	GLOBAL $parser;
	
	if (FAQ_TYPE==2) {
		$data->set('faq_section');
		$sections=$data->select('id!=1');
		if (arrayIsOk($sections)) {
			foreach ($sections as $key=>$section) {
				$data->set('faq');
				$faqs=$data->makeList("parent_id={$section['id']}",'modules/faq','faq_list');
				$sections[$key]['faqs']=$faqs;		
			}
		}
		$data->set('faq_section');
		$data->setParserFields();
		return $parser->makeList($sections,'modules/faq','faq_section_list');
	}	
	
}

/**
 * on success returns html code of parsed faq
 *	
 * @return string
 */
function makeFaqFullList() {
	GLOBAL $data;
	GLOBAL $parser;
	
	if (FAQ_TYPE==2) {
		$data->set('faq_section');
		$sections=$data->select('id!=1');
		if (arrayIsOk($sections)) {
			foreach ($sections as $key=>$section) {
				$data->set('faq');
				$faqs=$data->makeList("parent_id={$section['id']}",'modules/faq','faq_full_list');
				$sections[$key]['faqs']=$faqs;		
			}	
		}
		$data->set('faq_section');
		$data->setParserFields();
		return $parser->makeList($sections,'modules/faq','faq_full_section_list');
	}
	
}


function faqParse($block_name='faq') {
	GLOBAL $data;
	
	$data->set('faq');
	$data->parseList('',$block_name);
}

?>